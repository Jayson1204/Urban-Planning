<?php

namespace App\Services;

class GeminiService
{
    private $apiKey;
    private $defaultModel;
    private $complexModel;
    private $analyticsRepo;
    private $usageLogRepo;

    // USD per 1M tokens. Thinking tokens (thoughtsTokenCount) bill at the output rate.
    // Only models this service actually routes to need an entry; an unlisted model just
    // skips cost estimation rather than failing the request.
    private const PRICING = [
        'gemini-3.1-flash-lite' => ['input' => 0.25, 'output' => 1.50],
        'gemini-3.5-flash-lite' => ['input' => 0.30, 'output' => 2.50],
        'gemini-flash-latest' => ['input' => 0.30, 'output' => 2.50],
    ];

    // Cheap heuristic for routing to the pricier "complex" model tier: no extra API call,
    // just message length or intent keywords implying multi-point analysis/recommendations.
    private const COMPLEX_KEYWORDS = [
        'recommend', 'analy', 'priorit', 'compare', 'trend', 'assess', 'strategy', 'forecast', 'evaluat',
    ];
    private const COMPLEX_LENGTH_THRESHOLD = 220;

    private const DEFAULT_MAX_OUTPUT_TOKENS = 400;
    private const COMPLEX_MAX_OUTPUT_TOKENS = 800;
    private const TEMPERATURE = 0.3;

    public function __construct($analyticsRepo = null, $usageLogRepo = null)
    {
        $this->apiKey = getenv('GEMINI_API_KEY') ?: null;
        $this->defaultModel = getenv('GEMINI_MODEL') ?: 'gemini-3.1-flash-lite';
        $this->complexModel = getenv('GEMINI_MODEL_COMPLEX') ?: 'gemini-3.5-flash-lite';
        $this->analyticsRepo = $analyticsRepo;
        $this->usageLogRepo = $usageLogRepo;
    }

    public function isConfigured()
    {
        return !empty($this->apiKey);
    }

    public function generateContent($prompt, $complex = false)
    {
        $model = $complex ? $this->complexModel : $this->defaultModel;
        $maxOutputTokens = $complex ? self::COMPLEX_MAX_OUTPUT_TOKENS : self::DEFAULT_MAX_OUTPUT_TOKENS;

        return $this->call($model, null, [['role' => 'user', 'parts' => [['text' => $prompt]]]], $maxOutputTokens);
    }

    /**
     * Multi-turn chat, grounded with a live snapshot of program data so the assistant
     * answers from real numbers instead of guessing. $history is an array of
     * ['role' => 'user'|'model', 'text' => '...'] pairs from prior turns in the session.
     */
    public function chat($message, $history = [])
    {
        $complex = $this->isComplexRequest($message);
        $model = $complex ? $this->complexModel : $this->defaultModel;
        $maxOutputTokens = $complex ? self::COMPLEX_MAX_OUTPUT_TOKENS : self::DEFAULT_MAX_OUTPUT_TOKENS;

        $contents = [];
        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'model' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $turn['text'] ?? '']]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        return $this->call($model, $this->buildSystemInstruction(), $contents, $maxOutputTokens);
    }

    private function isComplexRequest($message)
    {
        if (strlen($message) > self::COMPLEX_LENGTH_THRESHOLD) {
            return true;
        }

        $lower = strtolower($message);
        foreach (self::COMPLEX_KEYWORDS as $keyword) {
            if (strpos($lower, $keyword) !== false) {
                return true;
            }
        }

        return false;
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

    private function call($model, $systemInstructionText, $contents, $maxOutputTokens)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'GEMINI_API_KEY is not configured on the server.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";
        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => $maxOutputTokens,
                'temperature' => self::TEMPERATURE,
            ],
        ];
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
            $this->logUsage($model, $body['usageMetadata'] ?? null, false);
            return ['success' => false, 'error' => $message, 'raw' => $body];
        }

        $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
        $this->logUsage($model, $body['usageMetadata'] ?? null, $text !== null);

        if ($text === null) {
            return ['success' => false, 'error' => 'Gemini response did not contain any text.', 'raw' => $body];
        }

        return ['success' => true, 'text' => $text];
    }

    /**
     * Never throws - a logging failure must not break the chat reply it was called from.
     */
    private function logUsage($model, $usageMetadata, $success)
    {
        if (!$this->usageLogRepo) {
            return;
        }

        try {
            $promptTokens = $usageMetadata['promptTokenCount'] ?? 0;
            $thoughtsTokens = $usageMetadata['thoughtsTokenCount'] ?? 0;
            $candidatesTokens = $usageMetadata['candidatesTokenCount'] ?? 0;
            $totalTokens = $usageMetadata['totalTokenCount'] ?? ($promptTokens + $thoughtsTokens + $candidatesTokens);

            $estimatedCost = null;
            if (isset(self::PRICING[$model])) {
                $pricing = self::PRICING[$model];
                // Thinking tokens are billed as output tokens.
                $outputTokens = $thoughtsTokens + $candidatesTokens;
                $estimatedCost = ($promptTokens / 1000000 * $pricing['input']) + ($outputTokens / 1000000 * $pricing['output']);
            }

            $userId = $_SESSION['user_id'] ?? null;

            $this->usageLogRepo->create([
                'user_id' => $userId,
                'model' => $model,
                'prompt_tokens' => $promptTokens,
                'thoughts_tokens' => $thoughtsTokens,
                'candidates_tokens' => $candidatesTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost_usd' => $estimatedCost,
                'success' => $success ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            error_log('GeminiService::logUsage failed: ' . $e->getMessage());
        }
    }
}
