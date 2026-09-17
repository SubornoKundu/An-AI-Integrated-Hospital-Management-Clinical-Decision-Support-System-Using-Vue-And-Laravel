<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorCategory;
use App\Models\Vital;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class AiController extends Controller
{
    private function patient()
    {
        return Auth::guard('patient')->user();
    }

    public static function checkApiStatus(): array
    {
        $apiKey = config('services.groq.key');
        if (empty($apiKey) || $apiKey === 'your-groq-key-here') {
            return ['connected' => false, 'reason' => 'GROQ_API_KEY is not set in .env'];
        }
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get('https://api.groq.com/openai/v1/models');

            if ($response->successful()) {
                return ['connected' => true];
            }
            if (in_array($response->status(), [401, 403])) {
                return ['connected' => false, 'reason' => 'API key is invalid or expired'];
            }

            return ['connected' => false, 'reason' => 'API not reachable (HTTP '.$response->status().')'];
        } catch (ConnectionException $e) {
            return ['connected' => false, 'reason' => 'No internet connection'];
        } catch (\Exception $e) {
            return ['connected' => false, 'reason' => 'Connection error: '.$e->getMessage()];
        }
    }

    private function callGroq(string $prompt, int $maxTokens = 1500): array
    {
        $payload = [
            'model' => 'openai/gpt-oss-120b',
            'temperature' => 0,
            'max_completion_tokens' => $maxTokens,
            'reasoning_effort' => 'low',
            'include_reasoning' => false,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are a medical AI assistant. Always respond with valid JSON only. No markdown, no explanation, no extra text — only the JSON object.'],
                ['role' => 'user',   'content' => $prompt],
            ],
        ];

        return $this->sendGroqChat($payload);
    }

    /**
     * Shared HTTP call + retry + JSON-extraction logic for Groq chat
     * completion calls.
     */
    private function sendGroqChat(array $payload): array
    {
        $apiKey = config('services.groq.key');
        if (empty($apiKey) || $apiKey === 'your-groq-key-here') {
            return ['__error__' => 'API Not Connected — GROQ_API_KEY is not set in .env'];
        }

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', $payload);

            if (in_array($response->status(), [401, 403])) {
                return ['__error__' => 'API Not Connected — API key is invalid or expired'];
            }
            if ($response->status() === 429) {
                sleep(5);
                $response = Http::timeout(45)
                    ->withHeaders(['Authorization' => 'Bearer '.$apiKey, 'Content-Type' => 'application/json'])
                    ->post('https://api.groq.com/openai/v1/chat/completions', $payload);
            }
            if (! $response->successful()) {
                if ($response->status() === 429) {
                    return ['__error__' => 'Our AI assistant is handling a lot of requests right now. Please wait about 20 seconds and try again.'];
                }
                $msg = $response->json()['error']['message'] ?? 'API error (HTTP '.$response->status().')';

                return ['__error__' => $msg];
            }

            $raw = trim($response->json()['choices'][0]['message']['content'] ?? '');
            $raw = preg_replace('/^```json\s*/i', '', $raw);
            $raw = preg_replace('/\s*```$/i', '', $raw);
            $start = strpos($raw, '{');
            $end = strrpos($raw, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $raw = substr($raw, $start, $end - $start + 1);
            }
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data) || empty($data)) {
                return ['__error__' => 'AI returned an invalid response. Please try again.'];
            }

            return $data;
        } catch (ConnectionException $e) {
            return ['__error__' => 'API Not Connected — No internet connection'];
        } catch (\Exception $e) {
            return ['__error__' => 'API Not Connected — '.$e->getMessage()];
        }
    }

    /**
     * Primes Whisper with the Bengali/English medical vocabulary this
     * feature actually needs. Whisper's optional transcription `prompt`
     * biases the model toward vocabulary it contains, which measurably
     * helps with words that sound alike but mean different things —
     * Bengali "পায়ে" (paye, "in the leg/foot") vs "গায়ে" (gaye, "on the
     * body") differ by a single consonant and are an easy swap for the
     * model. Listing both here as real alternatives gives it a better
     * chance of picking the one that was actually said. This still runs
     * on the audio's acoustics, not the prompt text, so it reduces this
     * class of mishearing without eliminating it — no prompt substitutes
     * for clearer audio, and Whisper can still get it wrong.
     */
    private const VOICE_TRANSCRIPTION_PROMPT = 'রোগী তার উপসর্গ হিসেবে বলছেন মাথাব্যথা, জ্বর, কাশি, সর্দি, গলা ব্যথা, বুকে ব্যথা, পেটে ব্যথা, বমি বমি ভাব, মাথা ঘোরা, পায়ে ব্যথা অথবা গায়ে ব্যথা। The patient is describing symptoms such as headache, fever, cough, cold, sore throat, chest pain, stomach pain, nausea, dizziness, leg pain, or body pain.';

    /**
     * Voice input for the Symptom Checker — accepts a short audio recording
     * in Bengali or English only. Whisper's translate-any-language endpoint
     * was detecting/translating too broad a range of languages and producing
     * unreliable results for languages this app doesn't actually need to
     * support, so this now transcribes first (which reports the detected
     * language) instead.
     *
     * That detected-language field is based on the audio itself, and is
     * known to be unreliable for short clips and especially for
     * code-switched speech — patients very naturally drop in English
     * symptom/medical words while speaking Bengali, and Whisper can then
     * mislabel the whole clip as English (or something else) even though it
     * transcribed the Bengali correctly. So the language field is only used
     * as a fallback; the primary check looks at the transcript text itself
     * for Bengali script, which directly reflects what was actually said
     * and doesn't depend on that audio-level guess. Anything that is
     * neither Bengali script nor plain English text gets a clear message
     * instead of a silently poor translation. A second Groq reasoning call
     * (polishTranscript) then turns the transcript into clean English:
     * translating if it was Bengali, lightly correcting mishearings if it
     * was already English — no hardcoded word lists involved, just the
     * model's own language understanding.
     */
    public function voiceToText(Request $request)
    {
        // mimetypes (not mimes) because it checks the file's real detected
        // content type, not its extension — Symfony's extension→mimetype
        // table maps "webm" to video/webm only, so mimes:webm would have
        // rejected genuine audio/webm recordings from the browser's
        // MediaRecorder. Verified against both a real audio file and a
        // disguised non-audio file before landing on this rule.
        $request->validate(['audio' => 'required|file|mimetypes:audio/*,video/webm|max:10240']);

        $apiKey = config('services.groq.key');
        if (empty($apiKey) || $apiKey === 'your-groq-key-here') {
            return response()->json(['error' => 'AI voice input is not available right now.'], 503);
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Authorization' => 'Bearer '.$apiKey])
                ->attach('file', file_get_contents($request->file('audio')->getRealPath()), 'recording.webm')
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model' => 'whisper-large-v3',
                    'response_format' => 'verbose_json',
                    'temperature' => 0,
                    'prompt' => self::VOICE_TRANSCRIPTION_PROMPT,
                ]);

            if (in_array($response->status(), [401, 403])) {
                return response()->json(['error' => 'AI voice input is not available right now.'], 503);
            }
            if (! $response->successful()) {
                return response()->json(['error' => 'Could not process the recording. Please try again or type instead.'], 503);
            }

            $json = $response->json();
            $rawText = trim($json['text'] ?? '');
            $language = strtolower(trim($json['language'] ?? ''));
            $segments = $json['segments'] ?? [];

            if ($rawText === '') {
                return response()->json(['error' => "Couldn't hear any speech in that recording. Please try again."], 422);
            }

            // Whisper is known to hallucinate stock phrases ("Thank you.",
            // "Bye.") when given silent, very short, or noise-only audio.
            // Rather than guessing from the words themselves, trust Whisper's
            // own per-segment no_speech_prob — if it wasn't confident this
            // was actually speech, don't pass a hallucinated guess onward.
            if (! empty($segments)) {
                $avgNoSpeechProb = array_sum(array_column($segments, 'no_speech_prob')) / count($segments);
                if ($avgNoSpeechProb > 0.7) {
                    return response()->json(['error' => "Couldn't hear clear speech in that recording. Please try again, speaking clearly into the microphone."], 422);
                }
            }

            $hasBengaliScript = (bool) preg_match('/\p{Bengali}/u', $rawText);
            $isAsciiOnly = (bool) preg_match('/^[\x00-\x7F]*$/', $rawText);

            $isBengali = $hasBengaliScript || in_array($language, ['bengali', 'bn']);
            $isEnglish = ! $hasBengaliScript && ($isAsciiOnly || in_array($language, ['english', 'en']));

            if (! $isBengali && ! $isEnglish) {
                return response()->json(['error' => 'Voice input currently supports Bengali or English only. Please try again in one of those languages, or type your symptoms instead.'], 422);
            }

            return response()->json(['text' => $this->polishTranscript($rawText, $isBengali)]);
        } catch (ConnectionException $e) {
            return response()->json(['error' => 'No internet connection to the AI voice service.'], 503);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong processing the recording.'], 500);
        }
    }

    /**
     * Second AI pass over the raw Whisper transcript. Whisper hears sounds
     * accurately but has no real medical knowledge, so it can mishear or
     * garble medical terms. This sends the raw text to the same reasoning
     * model used elsewhere in the app, which uses its actual language
     * understanding to translate (if Bengali) or lightly correct (if
     * already English) — it is told explicitly never to invent or drop
     * anything the patient didn't say. If this step fails for any reason,
     * it falls back to the untouched raw Whisper text (translated Bengali
     * would not auto-become English in that fallback case, so voice input
     * still works, just without the polish pass) so voice input never
     * breaks because of it.
     */
    private function polishTranscript(string $rawText, bool $isBengali): string
    {
        $context = $isBengali
            ? 'A speech-to-text system transcribed the raw Bengali audio into the Bengali text below.'
            : 'A speech-to-text system transcribed the audio below, which was already in English.';

        $instruction = $isBengali
            ? 'Translate it accurately into natural, clear English, and lightly correct only likely transcription errors — garbled words or misheard medical/symptom terms.'
            : 'Lightly correct only likely transcription errors — garbled words, misheard medical/symptom terms, broken grammar. If it already looks correct, return it completely unchanged.';

        $prompt = <<<PROMPT
A patient spoke to a hospital app describing their symptoms. {$context} Speech-to-text systems can mishear things, especially medical terms and accents.

RAW TRANSCRIPTION:
"{$rawText}"

{$instruction} Do NOT add any symptom or detail the patient didn't mention. Do NOT remove anything they said. Do NOT rephrase parts that are already clear, even if you would word them differently.

Respond ONLY with this JSON: {"corrected_text":"the final English text"}
PROMPT;

        $data = $this->callGroq($prompt, 500);

        if (isset($data['__error__']) || empty($data['corrected_text'])) {
            return $rawText;
        }

        return $data['corrected_text'];
    }

    public function apiStatus()
    {
        return response()->json(self::checkApiStatus());
    }

    // ── FEATURE 1: SYMPTOM CHECKER ───────────────────────────────

    public function symptomChecker()
    {
        $categories = DoctorCategory::where('is_active', 1)->orderBy('name')->get();

        return Inertia::render('patient/ai/SymptomChecker', [
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'icon' => $c->icon]),
            'apiStatus' => Inertia::defer(fn () => self::checkApiStatus()),
        ]);
    }

    public function analyzeSymptoms(Request $request)
    {
        $request->validate([
            'symptoms' => 'required|string|min:10|max:1000',
            // All optional, one-off context for this single check — see below.
            'duration'             => 'nullable|string|max:50',
            'trend'                => 'nullable|string|max:50',
            'additional_history'  => 'nullable|string|max:1000',
            'bp_systolic'          => 'nullable|integer|min:40|max:300',
            'bp_diastolic'         => 'nullable|integer|min:20|max:200',
            'pulse_rate'           => 'nullable|integer|min:20|max:250',
            'temperature_celsius'  => 'nullable|numeric|min:25|max:45',
            'spo2'                 => 'nullable|integer|min:0|max:100',
        ]);

        $patient = $this->patient();
        $age = $patient->age ? "Age: {$patient->age} years" : '';
        $gender = $patient->gender ? "Gender: {$patient->gender}" : '';

        // History: always pull the real stored history (past diagnoses +
        // profile fields). If the patient also typed something into the
        // optional "additional history" box, that's added on top — it
        // does NOT replace the stored data, since one quick note shouldn't
        // make their actual allergies/conditions/meds disappear from what
        // the AI sees. Nothing here gets saved back to their record either
        // way.

        // Past diagnoses on record in this system — only exists for
        // returning patients who've already had a completed, prescribed visit.
        // Not deduplicated: a diagnosis repeating across visits is a real
        // recurrence signal worth the AI seeing, and exact-string matching
        // can't reliably recognize reworded duplicates anyway (e.g.
        // "Diabetes" vs "Type 2 Diabetes") — that recognition is left to
        // the AI's own medical knowledge, not a PHP string comparison.
        $historyList = Appointment::where('patient_id', $patient->id)->where('status', 'completed')
            ->whereHas('prescription')->with('prescription')
            ->orderByDesc('appointment_date')->take(5)->get()
            ->pluck('prescription.diagnosis')->filter()->values();
        $pastDiagnoses = $historyList->isEmpty() ? null : 'Past diagnoses on record (most recent first): '.$historyList->join(', ');

        // Self-reported history from the patient's profile — this is what
        // covers brand-new patients who have no visit history here yet,
        // plus anything (allergies, existing conditions, meds) that never
        // came from a diagnosis made in this system.
        $selfReported = collect([
            'Known allergies' => $patient->known_allergies,
            'Chronic conditions' => $patient->chronic_conditions,
            'Current medications' => $patient->current_medications,
            'Family medical history' => $patient->family_medical_history,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->join('; ') ?: null;

        // Whatever the patient added just for this one check.
        $justNow = $request->filled('additional_history')
            ? 'Patient added for this check: '.trim($request->additional_history)
            : null;

        $historyParts = array_filter([$pastDiagnoses, $selfReported, $justNow]);
        $history = $historyParts ? implode(' | ', $historyParts) : 'None on record';

        // Vitals: same one-off-override idea. If the patient entered any
        // vitals field just now, use exactly that (clearly labeled as
        // patient-reported, not clinical) instead of the stored lookup —
        // and don't touch the vitals table at all in that case.
        $manualVitals = $request->only(['bp_systolic', 'bp_diastolic', 'pulse_rate', 'temperature_celsius', 'spo2']);
        $hasManualVitals = (bool) array_filter($manualVitals, fn ($v) => $v !== null && $v !== '');

        $vitalsLine = '';
        if ($hasManualVitals) {
            $vitalsLine = sprintf(
                "\nPatient-reported vitals (just now, unverified): BP %s/%s mmHg, Pulse %s bpm, Temp %s°C, SpO2 %s%%",
                $manualVitals['bp_systolic'] ?? '—', $manualVitals['bp_diastolic'] ?? '—',
                $manualVitals['pulse_rate'] ?? '—', $manualVitals['temperature_celsius'] ?? '—',
                $manualVitals['spo2'] ?? '—'
            );
        } else {
            // Most recent doctor-recorded vitals, if this patient has any.
            // Guarded on purpose: this is enrichment, not core to the feature —
            // if it fails for any reason (e.g. migrations not yet run), the
            // symptom checker should still work, just without this extra line.
            try {
                $latestVital = Vital::where('patient_id', $patient->id)->latest()->first();
                if ($latestVital) {
                    $vitalsLine = sprintf(
                        "\nMost recent recorded vitals (%s): BP %s/%s mmHg, Pulse %s bpm, Temp %s°C, SpO2 %s%%",
                        $latestVital->created_at->diffForHumans(),
                        $latestVital->bp_systolic ?? '—', $latestVital->bp_diastolic ?? '—',
                        $latestVital->pulse_rate ?? '—', $latestVital->temperature_celsius ?? '—',
                        $latestVital->spo2 ?? '—'
                    );
                }
            } catch (\Throwable $e) {
                // Swallow and continue without vitals context.
            }
        }

        $categories = DoctorCategory::where('is_active', 1)->orderBy('name')->get();
        $catList = $categories->map(fn ($c) => "id {$c->id} = {$c->name}")->join(', ');

        $symptoms = $request->symptoms;
        $durationLine = $request->filled('duration') ? "\nDuration so far: {$request->duration}" : '';
        $trendLine = $request->filled('trend') ? "\nTrend: {$request->trend}" : '';

        $prompt = <<<PROMPT
You are a senior medical triage specialist. A patient has described their symptoms. Your job is to:
1. Determine the correct medical specialty from the available list
2. Assess urgency
3. Suggest likely conditions

PATIENT:
{$gender}
{$age}
Known medical history: {$history}{$vitalsLine}
Symptoms: "{$symptoms}"{$durationLine}{$trendLine}

AVAILABLE SPECIALTIES (pick the single best-fitting one, by id, using your own clinical judgment — weigh the symptoms together with the patient's age, gender, and history):
{$catList}

URGENCY LEVELS:
- emergency: life-threatening RIGHT NOW (crushing chest pain + sweating + arm pain, sudden paralysis, severe uncontrolled bleeding, loss of consciousness, severe difficulty breathing)
- high: needs medical care within 24 hours (high fever, severe pain, worsening symptoms)
- medium: should be seen within 2-3 days
- low: routine appointment this week is fine

If the patient gave how long they've had these symptoms and whether they're getting worse, weigh that directly into the urgency level — the same symptoms are more urgent if new/worsening than if long-standing/improving. Don't guess a duration or trend that wasn't given.
If the patient's known medical history or recorded vitals are relevant to the current symptoms (e.g. a known condition could explain them, an abnormal vital supports or changes the urgency, or the symptoms could be a complication of it), mention that connection in specialty_reason or immediate_advice. Do not mention history or vitals that are unrelated. Past diagnoses are listed as recorded, not deduplicated — recognize when two entries describe the same condition worded differently (e.g. "Diabetes" vs "Type 2 Diabetes") using your own medical knowledge.
Be specific in possible_conditions — name actual medical conditions, not vague descriptions.

Respond ONLY with this JSON — no markdown, no explanation:
{"recommended_specialty_id":ID_NUMBER_FROM_THE_LIST_ABOVE,"urgency":"low or medium or high or emergency","urgency_note":"specific practical advice for this patient right now","specialty_reason":"one clear clinical sentence explaining why this specialty fits these symptoms","possible_conditions":["most likely specific condition name","second possibility","third possibility"],"immediate_advice":["specific actionable advice 1","specific actionable advice 2","specific actionable advice 3"],"warning_signs":["specific warning sign 1 that means go to emergency room immediately","warning sign 2"],"disclaimer":"AI-generated guidance only. Always consult a qualified doctor for proper diagnosis."}
PROMPT;

        $data = $this->callGroq($prompt, 800);
        if (isset($data['__error__'])) {
            return response()->json(['error' => $data['__error__']], 503);
        }

        $matched = $categories->firstWhere('id', $data['recommended_specialty_id'] ?? null);

        $data['recommended_specialty'] = $matched?->name ?? 'General Medicine';
        $data['category_id'] = $matched?->id ?? null;
        $data['category_icon'] = $matched?->icon ?? '🩺';
        $data['possible_conditions'] = $data['possible_conditions'] ?? [];
        $data['immediate_advice'] = $data['immediate_advice'] ?? [];
        $data['warning_signs'] = $data['warning_signs'] ?? [];

        return response()->json($data);
    }

    // ── FEATURE 2: PRESCRIPTION EXPLAINER ───────────────────────

    public function prescriptionExplainerIndex()
    {
        $patient = $this->patient();
        $appointments = Appointment::with('doctor.category', 'prescription')
            ->where('patient_id', $patient->id)
            ->whereHas('prescription')
            ->orderByDesc('appointment_date')
            ->get();

        return Inertia::render('patient/ai/PrescriptionExplainerList', [
            'appointments' => $appointments->map(fn (Appointment $a) => [
                'id' => $a->id,
                'appointment_date' => $a->appointment_date->format('d M Y'),
                'diagnosis' => $a->prescription->diagnosis,
                'doctor' => [
                    'name' => $a->doctor->name,
                    'category' => $a->doctor->category?->name ?? 'General Medicine',
                    'icon' => $a->doctor->category?->icon ?? '🩺',
                ],
            ]),
        ]);
    }

    public function prescriptionExplainer(int $appointmentId)
    {
        $patient = $this->patient();
        $appointment = Appointment::with('doctor.category', 'prescription')
            ->where('id', $appointmentId)->where('patient_id', $patient->id)->firstOrFail();
        if (! $appointment->prescription) {
            return redirect()->route('patient.appointments')->with('error', 'No prescription found.');
        }

        return Inertia::render('patient/ai/PrescriptionExplainer', [
            'appointment' => [
                'id' => $appointment->id,
                'appointment_date' => $appointment->appointment_date->format('d M Y'),
                'diagnosis' => $appointment->prescription->diagnosis,
                'medicines' => $appointment->prescription->medicines,
                'doctor' => [
                    'name' => $appointment->doctor->name,
                    'category' => $appointment->doctor->category?->name ?? 'General Medicine',
                    'icon' => $appointment->doctor->category?->icon ?? '🩺',
                ],
            ],
            'apiStatus' => Inertia::defer(fn () => self::checkApiStatus()),
        ]);
    }

    public function explainPrescription(Request $request, int $appointmentId)
    {
        $patient = $this->patient();
        $appointment = Appointment::with('doctor.category', 'prescription')
            ->where('id', $appointmentId)->where('patient_id', $patient->id)->firstOrFail();
        if (! $appointment->prescription) {
            return response()->json(['error' => 'No prescription found.'], 404);
        }

        $rx = $appointment->prescription;

        // HEREDOC এর বাইরে variable বানাও — PHP 8.5 এ HEREDOC এর ভেতরে ?-> এবং ?? কাজ করে না
        $doctorName = $appointment->doctor->name;
        $doctorSpecialty = $appointment->doctor && $appointment->doctor->category
                         ? $appointment->doctor->category->name
                         : 'General Medicine';
        $diagnosis = $rx->diagnosis;
        $medicines = $rx->medicines;
        $instructions = $rx->instructions;
        $followUpDate = $rx->follow_up_date;

        $allergies = $patient->known_allergies ?: 'None reported';
        $chronicConditions = $patient->chronic_conditions ?: 'None reported';
        $currentMedications = $patient->current_medications ?: 'None reported';

        // If the doctor used "Override & Save Anyway" past a safety flag,
        // that context is fed in here (not hardcoded text) so the AI can
        // phrase a calm, honest note in its own words — never inventing a
        // concern that wasn't there, never silently dropping one that was.
        $overrideContext = $rx->safety_check_override
            ? "\n\nIMPORTANT CONTEXT (do not skip): When this prescription was written, an AI safety check flagged something, and the doctor reviewed it and chose to proceed anyway. What was flagged: \"{$rx->safety_check_override}\". In your response's \"safety_note\" field, calmly reassure the patient that their doctor specifically reviewed a consideration for this prescription and confirmed it was still the right choice for them. Do not quote the raw flagged text verbatim or alarm the patient — just be honest that a check happened and the doctor made a deliberate, informed decision. Trust the doctor's judgment; your job is to communicate it calmly, not question it."
            : "\n\nNo safety concerns were flagged or overridden for this prescription — leave \"safety_note\" as null.";

        $prompt = <<<PROMPT
You are a medical translator explaining a prescription to a patient who has no medical background. Use simple, friendly, easy-to-understand language.

PRESCRIPTION DETAILS:
Doctor: Dr. {$doctorName}
Specialty: {$doctorSpecialty}
Diagnosis: {$diagnosis}
Medicines prescribed: {$medicines}
Instructions: {$instructions}
Follow-up date: {$followUpDate}

PATIENT'S OWN REPORTED BACKGROUND (for tailoring tone and relevant tips only — a full safety check already happened separately when this was prescribed, this is not that):
Known allergies: {$allergies}
Chronic conditions: {$chronicConditions}
Current medications: {$currentMedications}
{$overrideContext}

RULES:
- List EVERY single medicine separately — never skip any, never combine multiple medicines into one object
- Each medicine gets its own separate object in the medicines array
- Use plain, everyday language — no medical jargon or technical terms
- Be specific about how and when to take each medicine exactly as written
- Side effects should be the most common and relevant ones to watch for
- Where the patient's own reported background is genuinely relevant (e.g. a diet tip that matters more given a chronic condition), personalize naturally — don't force a mention where it doesn't add anything
- Make the patient feel reassured and informed

Respond ONLY with this JSON (no markdown, no explanation):
{"diagnosis_explained":"plain friendly explanation of what the diagnosis means in everyday words","medicines":[{"name":"exact medicine name as written by doctor","purpose":"what this medicine does to help them in simple words","how_to_take":"exact dose, when to take it, with or without food","side_effects":"2-3 common side effects to watch for, explained simply","important_notes":"any critical warning or important instruction for this specific medicine"}],"general_instructions":["general care instruction 1","instruction 2","instruction 3"],"diet_advice":["specific diet tip that helps this diagnosis","diet tip 2"],"recovery_timeline":"realistic honest recovery time for this specific diagnosis","when_to_seek_help":["specific warning sign that means they should call the doctor immediately 1","warning sign 2","warning sign 3"],"follow_up_importance":"in plain words, why the follow-up appointment matters for this specific diagnosis","safety_note":"calm reassuring note if the doctor reviewed and overrode a flagged concern, otherwise null","disclaimer":"This is an AI explanation to help you understand your prescription. Always follow your doctor's exact instructions."}
PROMPT;

        $data = $this->callGroq($prompt, 1500);
        if (isset($data['__error__'])) {
            return response()->json(['error' => $data['__error__']], 503);
        }

        $data['medicines'] = $data['medicines'] ?? [];
        $data['general_instructions'] = $data['general_instructions'] ?? [];
        $data['diet_advice'] = $data['diet_advice'] ?? [];
        $data['when_to_seek_help'] = $data['when_to_seek_help'] ?? [];
        $data['safety_note'] = $data['safety_note'] ?? null;

        return response()->json($data);
    }
}
