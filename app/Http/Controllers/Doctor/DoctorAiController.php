<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Vital;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DoctorAiController extends Controller
{
    private function doctor()
    {
        return Auth::guard('doctor')->user();
    }

    private function callGroq(string $prompt, int $maxTokens = 1200): array
    {
        $apiKey = config('services.groq.key');
        if (empty($apiKey) || $apiKey === 'your-groq-key-here') {
            return ['__error__' => 'API Not Connected — GROQ_API_KEY is not set in .env'];
        }

        $payload = [
            'model' => 'openai/gpt-oss-120b',
            'temperature' => 0,
            'max_completion_tokens' => $maxTokens,
            'reasoning_effort' => 'low',
            'include_reasoning' => false,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You are a clinical AI assistant for doctors. Always respond with valid JSON only. No markdown, no explanation, no extra text.'],
                ['role' => 'user',   'content' => $prompt],
            ],
        ];

        try {
            $response = Http::timeout(45)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', $payload);

            if (in_array($response->status(), [401, 403])) {
                return ['__error__' => 'API key is invalid or expired'];
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

    // ── AI PATIENT HISTORY SUMMARY ───────────────────────────────

    public function generatePatientSummary(int $appointmentId)
    {
        $doctor = $this->doctor();
        $appointment = Appointment::with('patient')
            ->where('id', $appointmentId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $patient = $appointment->patient;
        $history = Appointment::with('prescription', 'doctor.category')
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->orderByDesc('appointment_date')
            ->take(15)
            ->get();

        // Vitals across visits, oldest to newest — same guarded pattern as
        // Health Risk: its own query (not eager-loaded), wrapped so a DB
        // hiccup here can't take down the whole briefing.
        $vitalsHistory = [];
        try {
            $vitalsByAppointment = Vital::whereIn('appointment_id', $history->pluck('id'))->get()->keyBy('appointment_id');
            $vitalsHistory = $history->filter(fn ($a) => $vitalsByAppointment->has($a->id))
                ->sortBy('appointment_date')
                ->map(function ($a) use ($vitalsByAppointment) {
                    $v = $vitalsByAppointment[$a->id];

                    return [
                        'date' => $a->appointment_date->format('d M Y'),
                        'bp_systolic' => $v->bp_systolic,
                        'bp_diastolic' => $v->bp_diastolic,
                        'pulse_rate' => $v->pulse_rate,
                        'temperature_celsius' => $v->temperature_celsius,
                        'spo2' => $v->spo2,
                    ];
                })->values()->toArray();
        } catch (\Throwable $e) {
            // Swallow and continue without vitals trend context.
        }

        $patientData = [
            'name' => $patient->full_name,
            'gender' => $patient->gender ?? 'Unknown',
            'age' => $patient->age ?? 'Unknown',
            'blood_group' => $patient->blood_group ?? 'Unknown',
            'known_allergies' => $patient->known_allergies ?? 'None reported',
            'chronic_conditions_reported' => $patient->chronic_conditions ?? 'None reported',
            'current_medications_reported' => $patient->current_medications ?? 'None reported',
            'family_medical_history' => $patient->family_medical_history ?? 'None reported',
            'total_visits' => $history->count(),
            'visit_history' => $history->filter(fn ($a) => $a->prescription)->map(fn ($a) => [
                'date' => $a->appointment_date->format('d M Y'),
                'specialty' => $a->doctor?->category?->name ?? 'General',
                'diagnosis' => $a->prescription->diagnosis,
                'medicines' => $a->prescription->medicines,
            ])->values()->toArray(),
            'vitals_history' => $vitalsHistory,
        ];

        $json = json_encode($patientData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are a clinical AI briefing a doctor before a patient consultation, using your own medical knowledge and clinical judgment.

Patient data:
{$json}

Read every diagnosis using your own clinical judgment — for example, a diagnosis phrased as "monitoring", "follow-up", or "management" of a condition means the patient currently has it, not just a one-off mention. List every chronic or clinically significant condition found across all visits — do not miss any. Identify all medications the patient has been on across visits. Flag any worrying patterns (worsening conditions, repeated infections, increasing complexity) using your own clinical reasoning. Use clinical language appropriate for a doctor. Do NOT use JSON key names in your response text.

Also factor in known_allergies, chronic_conditions_reported, current_medications_reported, and family_medical_history from the patient data above — these are self-reported and may include things never formally diagnosed at a visit in this system, but are just as real and must be included in chronic_conditions/key_medications the same as anything from visit_history. If known_allergies has a real value (not "None reported"), it must always be mentioned in patient_snapshot — this is a doctor's pre-consultation briefing, and a known allergy is exactly the kind of thing that must not be missed walking into the room. If vitals_history has multiple entries, look for trends across visits (e.g. blood pressure climbing, oxygen saturation dropping) and call it out specifically in red_flags or consultation_focus when a trend is present — a worsening trend matters even if the single latest reading looks acceptable alone.

Respond ONLY with this JSON (no markdown, no explanation):
{"patient_snapshot":"2-sentence clinical overview mentioning ALL key conditions by name","chronic_conditions":["every chronic and significant condition identified — be thorough, list all"],"key_medications":["notable medication or drug class pattern 1","pattern 2","pattern 3"],"red_flags":["specific clinical concern 1 with detail","concern 2"],"consultation_focus":["specific clinical area to assess today 1","area 2","area 3"],"suggested_tests":["relevant test 1","test 2","test 3"],"clinical_note":"one concise paragraph of most important observations and clinical priorities for today's visit"}
PROMPT;

        $data = $this->callGroq($prompt, 1200);
        if (isset($data['__error__'])) {
            return response()->json(['error' => $data['__error__']], 503);
        }

        $data['chronic_conditions'] = $data['chronic_conditions'] ?? [];
        $data['key_medications'] = $data['key_medications'] ?? [];
        $data['red_flags'] = $data['red_flags'] ?? [];
        $data['consultation_focus'] = $data['consultation_focus'] ?? [];
        $data['suggested_tests'] = $data['suggested_tests'] ?? [];

        return response()->json($data);
    }

    // ── AI PRESCRIPTION SAFETY CHECK ────────────────────────────

    public function prescriptionSafetyCheck(Request $request, int $appointmentId)
    {
        $request->validate([
            'diagnosis' => 'required|string|max:1000',
            'medicines' => 'required|string|max:3000',
        ]);

        $doctor = $this->doctor();
        $appointment = Appointment::with('patient')
            ->where('id', $appointmentId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $patient = $appointment->patient;
        $history = Appointment::with('prescription')
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->orderByDesc('appointment_date')
            ->take(10)->get();

        // Not deduplicated by exact text — "Diabetes" and "Type 2 Diabetes"
        // are the same condition to a doctor, not to a string match, and
        // this list feeds the drug-disease contraindication check below,
        // so losing a recurring condition to a wording mismatch is a real
        // risk here, not just noise. Recognizing equivalence is left to
        // the AI's own medical knowledge in the prompt instructions.
        $knownConditions = $history
            ->filter(fn ($a) => $a->prescription)
            ->map(fn ($a) => $a->prescription->diagnosis)
            ->values()->join(', ');

        $knownMedicines = $history
            ->filter(fn ($a) => $a->prescription && $a->prescription->medicines)
            ->map(fn ($a) => $a->prescription->medicines)
            ->take(5)->join(' | ');

        $allergies = $patient->known_allergies ?: 'None reported';
        $chronicConditionsProfile = $patient->chronic_conditions ?: 'None reported';
        $currentMedicationsProfile = $patient->current_medications ?: 'None reported';

        $prompt = <<<PROMPT
You are a senior clinical pharmacist AI performing a comprehensive prescription safety check, using your own full pharmacological knowledge.

PATIENT:
Name: {$patient->full_name} | Age: {$patient->age}y | Gender: {$patient->gender}
KNOWN ALLERGIES: {$allergies}
Chronic conditions (self-reported): {$chronicConditionsProfile}
Current medications (self-reported): {$currentMedicationsProfile}
Known diagnoses from visit history: {$knownConditions}
Previously prescribed medicines (from visit history): {$knownMedicines}

TODAY'S PRESCRIPTION:
Diagnosis: {$request->diagnosis}
Medicines to check: {$request->medicines}

Using your own clinical and pharmacological knowledge, thoroughly check this prescription for, in this priority order:
1. ALLERGY CONFLICTS — check every medicine being prescribed today against KNOWN ALLERGIES above first. This is the highest-priority check: if a medicine today is, or belongs to the same drug class/family as, something the patient is allergic to, this MUST go in critical_issues, not warnings. Check the drug class, not just an exact name match (e.g. a penicillin allergy also conflicts with amoxicillin, ampicillin, and other penicillin-class drugs, even though the names differ).
2. DRUG-DRUG INTERACTIONS between every pair of medicines listed today, and with current medications — both current_medications (self-reported) and medicines evident from visit history. A medicine the patient reports taking now is just as real as one prescribed in this system, even if it was prescribed elsewhere.
3. DRUG-DISEASE CONTRAINDICATIONS — medicines that are unsafe or need caution given the patient's known conditions, from EITHER chronic_conditions (self-reported) or diagnoses from visit history — treat a self-reported condition as equally real as a formally diagnosed one. The visit-history diagnoses list is not deduplicated, so the same condition may appear worded differently across visits (e.g. "Diabetes" and "Type 2 Diabetes") — recognize these as one condition using your own medical knowledge, don't treat differently-worded mentions as unrelated.
4. DOSAGE SAFETY — doses that are unusually high or low for this patient's age and condition
5. DUPLICATE THERAPY — overlapping medicines from the same class prescribed together
6. APPROPRIATENESS — whether the medicines fit the stated diagnosis

IMPORTANT CALIBRATION:
- This patient has known conditions — medicines that treat those SAME known conditions are EXPECTED and APPROPRIATE, not a concern (e.g. do not flag a diabetes medicine just because the patient has diabetes)
- Only flag REAL, clinically significant issues — not theoretical or trivially minor ones
- Standard medicines at standard doses are safe; note them in safe_checks
- If nothing critical or problematic is found, return empty arrays for critical_issues and warnings — do not invent issues to fill the response
- If this patient has no visit history yet (first visit), still perform a full check using the self-reported allergies/conditions/medications above plus your own general pharmacological knowledge — an empty visit history is not a reason to skip or soften checks

Respond ONLY with this JSON:
{"verdict_summary":"one clear sentence overall assessment","critical_issues":["only real critical safety risks — empty array if none"],"warnings":["real clinical concerns worth reviewing — empty array if none"],"info":["minor informational notes — empty array if none"],"safe_checks":["what was verified as safe 1","safe check 2","safe check 3"],"recommendations":["actionable recommendation if any — empty array if none"]}
PROMPT;

        $data = $this->callGroq($prompt, 1000);
        if (isset($data['__error__'])) {
            return response()->json(['error' => $data['__error__']], 503);
        }

        $data['critical_issues'] = $data['critical_issues'] ?? [];
        $data['warnings'] = $data['warnings'] ?? [];
        $data['info'] = $data['info'] ?? [];
        $data['safe_checks'] = $data['safe_checks'] ?? [];
        $data['recommendations'] = $data['recommendations'] ?? [];

        return response()->json($data);
    }
}
