<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Vital;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class SmartAiController extends Controller
{
    private function patient()
    {
        return Auth::guard('patient')->user();
    }

    private function callGroq(string $prompt, int $maxTokens = 1400): array
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
                ->post('https://api.groq.com/openai/v1/chat/completions', [
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
                ]);

            if (in_array($response->status(), [401, 403])) {
                return ['__error__' => 'API Not Connected — API key is invalid or expired'];
            }
            if ($response->status() === 429) {
                sleep(5);
                $response = Http::timeout(45)
                    ->withHeaders(['Authorization' => 'Bearer '.$apiKey, 'Content-Type' => 'application/json'])
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => 'openai/gpt-oss-120b', 'temperature' => 0, 'max_completion_tokens' => $maxTokens,
                        'reasoning_effort' => 'low', 'include_reasoning' => false,
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            ['role' => 'system', 'content' => 'You are a medical AI assistant. Always respond with valid JSON only. No markdown, no explanation, no extra text — only the JSON object.'],
                            ['role' => 'user',   'content' => $prompt],
                        ],
                    ]);
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

    // ── HEALTH RISK ASSESSMENT ───────────────────────────────────

    public function healthRiskAssessment()
    {
        $patient = $this->patient();
        $hasProfile = $patient->date_of_birth || $patient->blood_group
            || $patient->known_allergies || $patient->chronic_conditions
            || $patient->current_medications || $patient->family_medical_history;
        $appointments = Appointment::with('doctor.category', 'prescription')
            ->where('patient_id', $patient->id)->where('status', 'completed')
            ->orderByDesc('appointment_date')->get();

        return Inertia::render('patient/ai/HealthRisk', [
            'patient' => [
                'name' => $patient->full_name, 'gender' => $patient->gender,
                'age' => $patient->age, 'blood_group' => $patient->blood_group,
                'total_visits' => $appointments->count(),
                'last_visit' => $appointments->first()?->appointment_date?->format('d M Y'),
            ],
            'conditions' => $appointments->filter(fn ($a) => $a->prescription)
                ->pluck('prescription.diagnosis')->filter()->unique()->values(),
            'hasProfile' => $hasProfile,
            'apiStatus' => Inertia::defer(fn () => AiController::checkApiStatus()),
        ]);
    }

    public function generateRiskAssessment(Request $request)
    {
        $patient = $this->patient();
        $appointments = Appointment::with('doctor.category', 'prescription')
            ->where('patient_id', $patient->id)->where('status', 'completed')
            ->orderByDesc('appointment_date')->get();

        $firstName = explode(' ', trim($patient->full_name))[0];

        // Vitals across visits, oldest to newest — lets the AI reason about
        // trends (e.g. blood pressure climbing over several visits), not
        // just whatever the single latest reading happens to be. Kept as
        // its own query (not eager-loaded onto $appointments above) and
        // guarded: this is enrichment, not core to the feature, so a DB
        // hiccup here (e.g. migrations not yet run) must not take down the
        // whole risk assessment.
        $vitalsHistory = [];
        try {
            $vitalsByAppointment = Vital::whereIn('appointment_id', $appointments->pluck('id'))->get()->keyBy('appointment_id');
            $vitalsHistory = $appointments->filter(fn ($a) => $vitalsByAppointment->has($a->id))
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

        // Every visit sent as its own entry, deliberately NOT grouped by
        // exact diagnosis text here — "Diabetes" and "Type 2 Diabetes" are
        // the same condition to a doctor but not to a PHP string match, so
        // that judgment belongs to the AI's actual medical knowledge, not
        // brittle exact-matching. See the prompt instruction below.
        $visitHistory = $appointments->filter(fn ($a) => $a->prescription)
            ->sortBy('appointment_date')
            ->map(fn ($a) => [
                'date' => $a->appointment_date->format('d M Y'),
                'specialty' => $a->doctor?->category?->name ?? 'General',
                'diagnosis' => trim($a->prescription->diagnosis),
                'medicines' => $a->prescription->medicines ?? '',
            ])
            ->values()->take(60)->toArray();

        $patientData = [
            'name' => $patient->full_name,
            'gender' => $patient->gender ?? 'Unknown',
            'age' => $patient->age ?? 'Unknown',
            'blood_group' => $patient->blood_group ?? 'Unknown',
            'known_allergies' => $patient->known_allergies ?? 'None reported',
            'chronic_conditions' => $patient->chronic_conditions ?? 'None reported',
            'current_medications' => $patient->current_medications ?? 'None reported',
            'family_medical_history' => $patient->family_medical_history ?? 'None reported',
            'total_visits' => $appointments->count(),
            'visit_history' => $visitHistory,
            'vitals_history' => $vitalsHistory,
        ];

        $patientJson = json_encode($patientData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are a senior clinical AI performing a comprehensive health risk assessment. Analyze all available information carefully — visit history, self-reported medical history, and vitals trends.

Patient data:
{$patientJson}

Use your own clinical judgment to assess this patient's overall health risk holistically — consider severity, how well-controlled each condition appears to be, interactions between conditions, and the patient's age, the way an experienced physician reviewing this chart would. Each entry in visit_history is one individual visit, not pre-grouped — the SAME underlying condition can appear worded differently across visits (e.g. "Diabetes", "Type 2 Diabetes", and "T2DM" are the same condition; so are "Migraine" and "Chronic Migraine"). Use your own medical knowledge to recognize this and treat them as ONE recurring condition, not as separate unrelated entries — do not rely on exact text matching. A condition that genuinely recurs across visits (however differently it was worded each time) generally indicates an ongoing/chronic issue and should be weighted accordingly; a condition appearing only once is still real and must still be counted, not ignored just because it's less frequent or was recorded further in the past.

Also factor in known_allergies, chronic_conditions, current_medications, and family_medical_history from the patient data above into your risk categories and scoring — these are self-reported by the patient and may include conditions never formally diagnosed at a visit in this system, but they are still real and must be counted the same as a diagnosed visit would be. If visit_history is empty, base the assessment on this self-reported information plus general age/gender risk factors instead — still produce a full, genuine assessment, do not respond as if there is insufficient data.

If vitals_history has entries, look for trends across visits, not just the latest reading — e.g. blood pressure climbing over several visits, or oxygen saturation dropping — a worsening trend is itself a risk signal even when the single most recent reading still looks acceptable in isolation. Call out a trend specifically, by name, in the relevant category's reason when you find one.

Note: each visit's "specialty" field is which TYPE OF DOCTOR the patient saw (e.g. ENT, General Medicine) — it is NOT the same thing as the risk categories you create below, and should not be used to decide category membership. A condition belongs to a risk category based on its own clinical/physiological nature, regardless of which doctor treated it — for example a sinusitis or upper respiratory infection is a Respiratory-category condition even when the visit's specialty says "ENT" or "General Medicine", not "Respiratory" or "Pulmonology".

OUTPUT SCALE (for consistency with the app's display):
- overall_risk_score: 0-100
- risk_level: must be exactly one of "low" (roughly 0-35), "moderate" (roughly 36-60), "high" (roughly 61-80), or "critical" (roughly 81-100)
- risk_color: "green" for low, "yellow" for moderate, "orange" for high, "red" for critical

CATEGORY SCORING:
Create one category object for EVERY distinct body-system/health-domain that has at least one relevant diagnosis in this patient's actual visit history — do not limit yourself to a fixed preset list. Typical categories include (use whichever genuinely apply, and others too if a diagnosis needs a home that isn't covered by these): Cardiovascular, Metabolic/Diabetes, Hypertension, Respiratory, Mental Health, Gastrointestinal, Genitourinary, Musculoskeletal, Dermatological, Neurological, Infectious/Other. Every single diagnosis in the visit history above must be counted under at least one category's relevant_diagnoses — none may be left uncovered. Any condition listed in chronic_conditions must also get its own category coverage the same way, even though it isn't in visit_history. Score each category 0-100 based on your own reading of the diagnoses in that domain — higher for more severe or less-controlled findings. Before finalizing, re-read every diagnosis in the visit history against your chosen categories using your own medical knowledge — a diagnosis can belong to a category even if the category's name doesn't literally appear in it (for example, sinusitis or an upper respiratory infection are respiratory conditions even though the word "respiratory" isn't in their name). Double-check that each category's "reason" text is factually consistent with the actual visit history. Also pay attention to severity qualifiers already present in the diagnosis text itself (e.g. "mild", "Stage 1", "severe") — they should influence how high or low you score that category, not be ignored in favor of just the condition's name.

Address the patient as "{$firstName}" in your response text. Write naturally — do NOT include JSON key names in text.

Respond ONLY with this JSON (no markdown, no explanation):
{"overall_risk_score":NUMBER_0_to_100,"risk_level":"low or moderate or high or critical","risk_color":"green or yellow or orange or red","risk_summary":"2-3 warm sentences to {$firstName} explaining their overall health risk, mentioning their specific conditions by name","risk_categories":[{"category":"a body-system/health-domain name that genuinely applies to this patient","relevant_diagnoses":["exact diagnosis text you are counting toward this category"],"score":NUMBER,"level":"low or moderate or high","reason":"1 clinical sentence based on their actual diagnoses"}],"top_risk_factors":["specific condition or factor 1","specific factor 2","specific factor 3"],"protective_factors":["genuine positive health factor 1","positive factor 2"],"immediate_actions":["specific actionable step for their condition 1","specific step 2","specific step 3"],"recommended_tests":["clinically relevant test 1 for their conditions","test 2","test 3","test 4","test 5"],"lifestyle_changes":["specific lifestyle change for their diagnosed condition 1","change 2","change 3"],"next_checkup_months":NUMBER,"disclaimer":"AI-generated assessment. Not a medical diagnosis. Please consult your doctor for professional evaluation."}
(risk_categories should have as many objects as there are genuinely relevant categories for THIS patient — typically 4-8 — not a fixed count.)
PROMPT;

        $data = $this->callGroq($prompt, 2200);
        if (isset($data['__error__'])) {
            return response()->json(['error' => $data['__error__']], 503);
        }

        // relevant_diagnoses is a reasoning aid for the model, not something
        // the UI currently displays — drop it from the response so the
        // frontend's shape stays exactly what it already expects.
        if (isset($data['risk_categories']) && is_array($data['risk_categories'])) {
            $data['risk_categories'] = array_map(function ($cat) {
                unset($cat['relevant_diagnoses']);

                return $cat;
            }, $data['risk_categories']);
        }

        // Structural safety net, not a medical judgment: the frontend looks
        // up a colour by exact risk_level string, so guarantee it's always
        // one of the 4 values it expects (normalising case, and falling back
        // to a score-derived level in the unlikely event the model returns
        // something else entirely).
        $levelColors = ['low' => 'green', 'moderate' => 'yellow', 'high' => 'orange', 'critical' => 'red'];
        $level = strtolower(trim($data['risk_level'] ?? ''));
        if (! isset($levelColors[$level])) {
            $score = (int) ($data['overall_risk_score'] ?? 0);
            $level = $score > 80 ? 'critical' : ($score > 60 ? 'high' : ($score > 35 ? 'moderate' : 'low'));
        }
        $data['risk_level'] = $level;
        $data['risk_color'] = $levelColors[$level];
        $data['risk_categories'] = $data['risk_categories'] ?? [];
        $data['top_risk_factors'] = $data['top_risk_factors'] ?? [];
        $data['protective_factors'] = $data['protective_factors'] ?? [];
        $data['immediate_actions'] = $data['immediate_actions'] ?? [];
        $data['recommended_tests'] = $data['recommended_tests'] ?? [];
        $data['lifestyle_changes'] = $data['lifestyle_changes'] ?? [];

        return response()->json($data);
    }

    // ── HEALTH PLAN GENERATOR ─────────────────────────────────────

    public function healthPlan()
    {
        $patient = $this->patient();
        $appointments = Appointment::with('doctor.category', 'prescription')
            ->where('patient_id', $patient->id)->where('status', 'completed')
            ->orderByDesc('appointment_date')->get();

        return Inertia::render('patient/ai/HealthPlan', [
            'patient' => [
                'name' => $patient->full_name, 'gender' => $patient->gender,
                'age' => $patient->age, 'blood_group' => $patient->blood_group,
                'total_visits' => $appointments->count(),
            ],
            'conditions' => $appointments->filter(fn ($a) => $a->prescription)
                ->pluck('prescription.diagnosis')->filter()->unique()->values(),
            'apiStatus' => Inertia::defer(fn () => AiController::checkApiStatus()),
        ]);
    }

    public function generateHealthPlan(Request $request)
    {
        $patient = $this->patient();
        $firstName = explode(' ', trim($patient->full_name))[0];

        $appointments = Appointment::with('doctor.category', 'prescription')
            ->where('patient_id', $patient->id)->where('status', 'completed')
            ->orderByDesc('appointment_date')->get();

        // Every visit sent as its own entry, not grouped by exact string —
        // same reasoning as Health Risk: "Diabetes" and "Type 2 Diabetes"
        // are the same condition to a doctor, not to a PHP string match.
        $conditions = $appointments
            ->filter(fn ($a) => $a->prescription)
            ->sortBy('appointment_date')
            ->map(fn ($a) => [
                'date' => $a->appointment_date->format('d M Y'),
                'diagnosis' => trim($a->prescription->diagnosis),
                'medicines' => $a->prescription->medicines ?? '',
                'specialty' => $a->doctor?->category?->name ?? 'General',
            ])
            ->values()->take(40)->toArray();

        $conditionsJson = json_encode($conditions, JSON_UNESCAPED_UNICODE);

        // Vitals across visits, oldest to newest — same guarded pattern as
        // Health Risk and Patient Summary: its own query, wrapped so a DB
        // hiccup here can't take down the whole plan.
        $vitalsHistory = [];
        try {
            $vitalsByAppointment = Vital::whereIn('appointment_id', $appointments->pluck('id'))->get()->keyBy('appointment_id');
            $vitalsHistory = $appointments->filter(fn ($a) => $vitalsByAppointment->has($a->id))
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
        $vitalsJson = json_encode($vitalsHistory, JSON_UNESCAPED_UNICODE);

        // PHP 8.5: ?? operator cannot be used inside HEREDOC — assign to variable first
        $patientBloodGroup = $patient->blood_group ? $patient->blood_group : 'Unknown';
        $allergies = $patient->known_allergies ?: 'None reported';
        $chronicConditionsReported = $patient->chronic_conditions ?: 'None reported';
        $currentMedications = $patient->current_medications ?: 'None reported';
        $familyHistory = $patient->family_medical_history ?: 'None reported';

        $prompt = <<<PROMPT
You are a clinical health coach creating a detailed personalised weekly health plan for a specific patient.

PATIENT:
Name: {$patient->full_name} (call them {$firstName})
Age: {$patient->age} years | Gender: {$patient->gender} | Blood Group: {$patientBloodGroup}
Total hospital visits: {$appointments->count()}
Known allergies: {$allergies}
Chronic conditions (self-reported): {$chronicConditionsReported}
Current medications (self-reported): {$currentMedications}
Family medical history: {$familyHistory}

DIAGNOSED CONDITIONS AND MEDICINES (from medical history):
{$conditionsJson}

RECENT VITALS ACROSS VISITS (oldest to newest, if any recorded):
{$vitalsJson}

Read the diagnosed conditions above using your own clinical judgment — for example, a diagnosis phrased as "monitoring", "follow-up", or "management" of a condition means the patient currently has it. Each entry is one individual visit, not pre-grouped — the SAME condition can appear worded differently across visits (e.g. "Diabetes", "Type 2 Diabetes", and "T2DM" are the same condition). Use your own medical knowledge to recognize this rather than relying on exact wording — a condition genuinely recurring across visits, however differently worded each time, is likely chronic/ongoing. Treat chronic_conditions and family_medical_history (self-reported above) with the same weight as a diagnosed condition when personalizing the plan — do not limit personalization only to what came from a formal visit.

YOUR TASK — create a FULLY PERSONALISED plan using your own medical knowledge of each of this patient's specific conditions:
1. DIET: Every food item must explain WHY it is good or safe for THIS patient's specific conditions, based on real clinical nutrition knowledge. Foods to avoid must be specific to their conditions. KNOWN ALLERGIES ABOVE ARE NON-NEGOTIABLE: never recommend a food containing a listed allergen anywhere in the plan, and include each allergen in foods_to_avoid explicitly.
2. EXERCISE: Every exercise must explain why it is appropriate OR safe given their conditions (e.g., if they have hypertension, explain why walking is chosen over running). If recent vitals show an elevated or worsening trend (e.g. rising blood pressure, low oxygen saturation), lower the recommended intensity accordingly and say why in the notes — don't default to moderate/high intensity if the vitals argue against it.
3. SLEEP: Tailor sleep advice to their conditions (e.g., anxiety → strict bedtime routine; diabetes → consistent hours to help regulate blood sugar).
4. STRESS MANAGEMENT: Specific techniques that address their diagnosed mental/physical conditions.
5. WEEKLY GOALS: Measurable, condition-specific goals (e.g., "Check fasting blood sugar every morning before breakfast" — not generic "eat healthy").
6. WARNINGS: Specific to their actual conditions.

IMPORTANT:
- Do NOT write generic advice. Every recommendation must reference a specific condition and reflect sound, real clinical practice for it.
- Do NOT write "eat healthy" or "exercise regularly" without specifying why for their condition.
- If there are no diagnosed or self-reported conditions at all: create an age-appropriate preventive plan for {$patient->age}-year-old {$patient->gender}.

Respond ONLY with this JSON (no markdown, no explanation):
{"intro_message":"2 warm sentences to {$firstName} mentioning their specific conditions by name and what this plan addresses","diet_plan":{"breakfast":["specific food — why it helps their condition","specific food — reason","specific food — reason"],"lunch":["specific food — condition-specific reason","specific food — reason"],"dinner":["specific food — condition-specific reason","specific food — reason"],"snacks":["specific snack — why safe for their condition","specific snack — reason"],"foods_to_avoid":["specific food to avoid — exactly why harmful for their condition","food — why","food — why","food — why"],"hydration":"exact amount ml/day — why this amount is important for their specific conditions"},"exercise_plan":[{"day":"Monday","activity":"specific exercise name","duration":"X minutes","intensity":"low or moderate or high","notes":"why this exercise is appropriate for their specific condition"},{"day":"Tuesday","activity":"specific exercise","duration":"X minutes","intensity":"moderate","notes":"condition-specific benefit"},{"day":"Wednesday","activity":"gentle yoga or stretching","duration":"20 minutes","intensity":"low","notes":"why rest and recovery matters for their condition"},{"day":"Thursday","activity":"specific exercise","duration":"X minutes","intensity":"moderate","notes":"condition-specific note"},{"day":"Friday","activity":"specific exercise","duration":"X minutes","intensity":"low","notes":"condition-specific note"},{"day":"Saturday","activity":"specific exercise","duration":"X minutes","intensity":"moderate","notes":"condition-specific note"},{"day":"Sunday","activity":"Complete rest","duration":"Full day","intensity":"low","notes":"Full recovery — why this matters for their specific condition"}],"sleep_plan":{"recommended_hours":"X-Y hours — reason for their condition","bedtime":"specific time — reason","wake_time":"specific time","sleep_tips":["tip specifically for their condition 1","tip for their condition 2","tip 3"]},"stress_management":["technique name — how to do it in 1 sentence — why it helps their specific condition","technique 2 with how-to and why","technique 3 with how-to and why"],"weekly_goals":["measurable goal for their specific condition 1","measurable goal 2","measurable goal 3","measurable goal 4"],"health_warnings":["specific warning based on their actual condition 1","warning 2","warning 3"],"disclaimer":"This plan is personalised for {$firstName} based on their medical history. Always consult your doctor before making significant lifestyle changes."}
PROMPT;

        $data = $this->callGroq($prompt, 2500);
        if (isset($data['__error__'])) {
            return response()->json(['error' => $data['__error__']], 503);
        }

        $data['diet_plan'] = $data['diet_plan'] ?? [];
        $data['diet_plan']['breakfast'] = $data['diet_plan']['breakfast'] ?? [];
        $data['diet_plan']['lunch'] = $data['diet_plan']['lunch'] ?? [];
        $data['diet_plan']['dinner'] = $data['diet_plan']['dinner'] ?? [];
        $data['diet_plan']['snacks'] = $data['diet_plan']['snacks'] ?? [];
        $data['diet_plan']['foods_to_avoid'] = $data['diet_plan']['foods_to_avoid'] ?? [];
        $data['diet_plan']['hydration'] = $data['diet_plan']['hydration'] ?? '';

        $data['exercise_plan'] = $data['exercise_plan'] ?? [];

        $data['sleep_plan'] = $data['sleep_plan'] ?? [];
        $data['sleep_plan']['sleep_tips'] = $data['sleep_plan']['sleep_tips'] ?? [];
        $data['sleep_plan']['recommended_hours'] = $data['sleep_plan']['recommended_hours'] ?? '';
        $data['sleep_plan']['bedtime'] = $data['sleep_plan']['bedtime'] ?? '';
        $data['sleep_plan']['wake_time'] = $data['sleep_plan']['wake_time'] ?? '';

        $data['stress_management'] = $data['stress_management'] ?? [];
        $data['weekly_goals'] = $data['weekly_goals'] ?? [];
        $data['health_warnings'] = $data['health_warnings'] ?? [];

        return response()->json($data);
    }
}
