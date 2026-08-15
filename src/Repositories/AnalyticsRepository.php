<?php

namespace App\Repositories;

class AnalyticsRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function summary()
    {
        $rows = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM residents WHERE status = 'Active') AS total_residents,
                (SELECT COUNT(*) FROM households WHERE status = 'Active') AS total_households,
                (SELECT COUNT(*) FROM housing_units WHERE status = 'Active') AS total_housing_units,
                (SELECT COUNT(*) FROM urban_projects WHERE status = 'Active') AS total_urban_projects,
                (SELECT COUNT(*) FROM field_survey_assignments WHERE status = 'Active') AS total_survey_assignments,
                (SELECT COUNT(*) FROM field_survey_results WHERE status = 'Active') AS total_survey_results"
        );
        return $rows[0] ?? [];
    }

    public function housingOccupancyChart()
    {
        return $this->db->query(
            "SELECT occupancy_status, COUNT(*) AS total
             FROM housing_units
             WHERE status = 'Active'
             GROUP BY occupancy_status"
        );
    }

    public function urbanProjectStatusChart()
    {
        return $this->db->query(
            "SELECT project_status, COUNT(*) AS total
             FROM urban_projects
             WHERE status = 'Active'
             GROUP BY project_status"
        );
    }

    public function surveyConditionChart()
    {
        return $this->db->query(
            "SELECT condition_rating, COUNT(*) AS total
             FROM field_survey_results
             WHERE status = 'Active' AND condition_rating IS NOT NULL
             GROUP BY condition_rating"
        );
    }

    public function kpis()
    {
        $rows = $this->db->query(
            "SELECT
                (SELECT COUNT(*) FROM housing_units WHERE status = 'Active') AS housing_total,
                (SELECT COUNT(*) FROM housing_units WHERE status = 'Active' AND occupancy_status = 'Occupied') AS housing_occupied,
                (SELECT COUNT(*) FROM field_survey_assignments WHERE status = 'Active') AS assignments_total,
                (SELECT COUNT(*) FROM field_survey_assignments WHERE status = 'Active' AND assignment_status = 'Completed') AS assignments_completed,
                (SELECT COUNT(*) FROM urban_projects WHERE status = 'Active') AS projects_total,
                (SELECT COUNT(*) FROM urban_projects WHERE status = 'Active' AND project_status = 'Completed') AS projects_completed"
        );
        $r = $rows[0] ?? [];

        $pct = function ($num, $den) {
            $num = (int)($num ?? 0);
            $den = (int)($den ?? 0);
            return $den > 0 ? round(($num / $den) * 100, 1) : 0.0;
        };

        return [
            'housing_occupancy_rate' => $pct($r['housing_occupied'] ?? 0, $r['housing_total'] ?? 0),
            'survey_completion_rate' => $pct($r['assignments_completed'] ?? 0, $r['assignments_total'] ?? 0),
            'project_completion_rate' => $pct($r['projects_completed'] ?? 0, $r['projects_total'] ?? 0),
        ];
    }

    public function recentActivity($limit = 8)
    {
        $limit = max(1, (int)$limit);

        $sql = "(SELECT 'Resident' AS type, CONCAT_WS(' ', first_name, last_name) AS label, created_at AS event_date
                 FROM residents ORDER BY created_at DESC LIMIT {$limit})
                UNION ALL
                (SELECT 'Housing Unit' AS type, unit_code AS label, created_at AS event_date
                 FROM housing_units ORDER BY created_at DESC LIMIT {$limit})
                UNION ALL
                (SELECT 'Urban Project' AS type, project_title AS label, created_at AS event_date
                 FROM urban_projects ORDER BY created_at DESC LIMIT {$limit})
                UNION ALL
                (SELECT 'Survey Assignment' AS type, CONCAT('Assignment #', assignment_id) AS label, created_at AS event_date
                 FROM field_survey_assignments ORDER BY created_at DESC LIMIT {$limit})
                ORDER BY event_date DESC
                LIMIT {$limit}";

        return $this->db->query($sql);
    }
}
