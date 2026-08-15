<?php

namespace App\Services;

class GeminiService
{
    private $apiKey;
    private $model;
    private $analyticsRepo;

    public function __construct($analyticsRepo = null, $model = 'gemini-flash-latest')
    {
        $this->apiKey = getenv('GEMINI_API_KEY') ?: null;
        $this->model = $model;
        $this->analyticsRepo = $analyticsRepo;
    }

    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    public function generateContent($prompt)
    {
        return $this->call(null, [['role' => 'user', 'parts' => [['text' => $prompt]]]]);
    }

    /**
     * Multi-turn chat, grounded with a live snapshot of program data so the assistant
     * answers from real numbers instead of guessing. $history is an array of
     * ['role' => 'user'|'model', 'text' => '...'] pairs from prior turns in the session.
     */
    public function chat($message, $history = [])
    {
        $contents = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'model' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $turn['text'] ?? '']]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        return $this->call($this->buildSystemInstruction(), $contents);
    }

    private function buildSystemInstruction()
    {
        $context = $this->buildContextSummary();

        return "You are the AI Planning Assistant inside CIVENTRAL, a local government urban planning system for "
            . "Caloocan City. You help planning staff interpret resident, housing, urban project, and field survey "
            . "data, generate planning recommendations, and summarize program status. Be concise and practical. "
            . "When asked for recommendations or summaries, ground your answer in the CURRENT PROGRAM DATA below; "
            . "do not invent numbers that aren't given to you.\n\nCURRENT PROGRAM DATA (live snapshot):\n{$context}";
    }

    private function buildContextSummary()
    {
        if (!$this->analyticsRepo) {
            return 'No live data available.';
        }

        $summary = $this->analyticsRepo->summary();
        $kpis = $this->analyticsRepo->kpis();
        $occupancy = $this->analyticsRepo->housingOccupancyChart();
        $projectStatus = $this->analyticsRepo->urbanProjectStatusChart();
        $conditions = $this->analyticsRepo->surveyConditionChart();

        $lines = [];
        $lines[] = "- Total residents: " . ($summary['total_residents'] ?? 0) . " across " . ($summary['total_households'] ?? 0) . " households";
        $lines[] = "- Housing units: " . ($summary['total_housing_units'] ?? 0) . " (occupancy rate: " . ($kpis['housing_occupancy_rate'] ?? 0) . "%)";
        $lines[] = "- Urban projects: " . ($summary['total_urban_projects'] ?? 0) . " (completion rate: " . ($kpis['project_completion_rate'] ?? 0) . "%)";
        $lines[] = "- Field survey assignments: " . ($summary['total_survey_assignments'] ?? 0) . ", results recorded: " . ($summary['total_survey_results'] ?? 0) . " (completion rate: " . ($kpis['survey_completion_rate'] ?? 0) . "%)";

        if ($occupancy) {
            $parts = array_map(fn($r) => "{$r['occupancy_status']}: {$r['total']}", $occupancy);
            $lines[] = "- Housing occupancy breakdown: " . implode(', ', $parts);
        }
        if ($projectStatus) {
            $parts = array_map(fn($r) => "{$r['project_status']}: {$r['total']}", $projectStatus);
            $lines[] = "- Urban project status breakdown: " . implode(', ', $parts);
        }
        if ($conditions) {
            $parts = array_map(fn($r) => "{$r['condition_rating']}: {$r['total']}", $conditions);
            $lines[] = "- Field survey condition ratings: " . implode(', ', $parts);
        }

        return implode("\n", $lines);
    }

    private function call($systemInstructionText, $contents)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'GEMINI_API_KEY is not configured on the server.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";
        $payload = ['contents' => $contents];
        if ($systemInstructionText) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstructionText]]];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return ['success' => false, 'error' => 'Gemini request failed: ' . $error];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($response, true);

        if ($httpCode !== 200) {
            $message = $body['error']['message'] ?? ('Gemini API returned HTTP ' . $httpCode);
            return ['success' => false, 'error' => $message, 'raw' => $body];
        }

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null) {
            return ['success' => false, 'error' => 'Gemini response did not contain any text.', 'raw' => $body];
        }

        return ['success' => true, 'text' => $text];
    }
}
