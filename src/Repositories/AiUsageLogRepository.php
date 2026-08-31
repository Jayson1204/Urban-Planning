<?php

namespace App\Repositories;

class AiUsageLogRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($data)
    {
        return $this->db->insert('ai_usage_logs', $data);
    }

    public function summary()
    {
        $totals = $this->db->query(
            "SELECT COUNT(*) AS request_count,
                    COALESCE(SUM(prompt_tokens), 0) AS prompt_tokens,
                    COALESCE(SUM(thoughts_tokens), 0) AS thoughts_tokens,
                    COALESCE(SUM(candidates_tokens), 0) AS candidates_tokens,
                    COALESCE(SUM(total_tokens), 0) AS total_tokens,
                    COALESCE(SUM(estimated_cost_usd), 0) AS estimated_cost_usd
             FROM ai_usage_logs"
        );

        $byModel = $this->db->query(
            "SELECT model,
                    COUNT(*) AS request_count,
                    COALESCE(SUM(total_tokens), 0) AS total_tokens,
                    COALESCE(SUM(estimated_cost_usd), 0) AS estimated_cost_usd
             FROM ai_usage_logs
             GROUP BY model
             ORDER BY estimated_cost_usd DESC"
        );

        $last7Days = $this->db->query(
            "SELECT DATE(created_at) AS day,
                    COUNT(*) AS request_count,
                    COALESCE(SUM(estimated_cost_usd), 0) AS estimated_cost_usd
             FROM ai_usage_logs
             WHERE created_at >= (NOW() - INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY day DESC"
        );

        return [
            'totals' => $totals[0] ?? [],
            'by_model' => $byModel,
            'last_7_days' => $last7Days,
        ];
    }
}
