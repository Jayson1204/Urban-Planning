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

    // Builds "col >= :date_from_SUFFIX AND col <= :date_to_SUFFIX" fragments with a
    // caller-chosen placeholder suffix, since EMULATE_PREPARES=false rejects a named
    // placeholder reused within one query (see reference: PDO placeholder gotcha) and
    // several of the methods below combine multiple date-ranged subqueries.
    private function dateRangeClause($column, $dateFrom, $dateTo, $suffix, array &$params)
    {
        $clauses = [];
        if (!empty($dateFrom)) {
            $key = "date_from_{$suffix}";
            $clauses[] = "{$column} >= :{$key}";
            $params[$key] = $dateFrom . ' 00:00:00';
        }
        if (!empty($dateTo)) {
            $key = "date_to_{$suffix}";
            $clauses[] = "{$column} <= :{$key}";
            $params[$key] = $dateTo . ' 23:59:59';
        }
        return $clauses;
    }

    public function overallSummary($dateFrom = null, $dateTo = null)
    {
        $paramsUnits = [];
        $whereUnits = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'ou', $paramsUnits));
        $unitsRow = $this->db->query(
            "SELECT COUNT(*) AS total FROM housing_units WHERE " . implode(' AND ', $whereUnits),
            $paramsUnits
        );

        $paramsApp = [];
        $whereApp = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'oa', $paramsApp));
        $appRow = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(beneficiary_status IN ('Applicant','Qualified')) AS pending,
                SUM(beneficiary_status = 'Awarded') AS approved,
                SUM(beneficiary_status = 'Disqualified') AS rejected,
                SUM(beneficiary_status = 'Cancelled') AS cancelled
             FROM housing_beneficiaries WHERE " . implode(' AND ', $whereApp),
            $paramsApp
        );

        $paramsProj = [];
        $whereProj = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'op', $paramsProj));
        $projRow = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(project_status = 'Planned') AS planned,
                SUM(project_status = 'Ongoing') AS ongoing,
                SUM(project_status = 'Completed') AS completed,
                SUM(project_status = 'Delayed') AS `delayed`,
                SUM(project_status = 'Cancelled') AS cancelled
             FROM urban_projects WHERE " . implode(' AND ', $whereProj),
            $paramsProj
        );

        $paramsRes = [];
        $whereRes = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'or', $paramsRes));
        $resRow = $this->db->query(
            "SELECT COUNT(*) AS total FROM residents WHERE " . implode(' AND ', $whereRes),
            $paramsRes
        );

        return [
            'total_housing_units' => (int)($unitsRow[0]['total'] ?? 0),
            'total_applications' => (int)($appRow[0]['total'] ?? 0),
            'pending_applications' => (int)($appRow[0]['pending'] ?? 0),
            'approved_applications' => (int)($appRow[0]['approved'] ?? 0),
            'rejected_applications' => (int)($appRow[0]['rejected'] ?? 0),
            'cancelled_applications' => (int)($appRow[0]['cancelled'] ?? 0),
            'total_urban_projects' => (int)($projRow[0]['total'] ?? 0),
            'planned_projects' => (int)($projRow[0]['planned'] ?? 0),
            'ongoing_projects' => (int)($projRow[0]['ongoing'] ?? 0),
            'completed_projects' => (int)($projRow[0]['completed'] ?? 0),
            'delayed_projects' => (int)($projRow[0]['delayed'] ?? 0),
            'cancelled_projects' => (int)($projRow[0]['cancelled'] ?? 0),
            'total_residents' => (int)($resRow[0]['total'] ?? 0),
        ];
    }

    public function housingApplicationsByStatus($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'has', $params));
        return $this->db->query(
            "SELECT beneficiary_status, COUNT(*) AS total FROM housing_beneficiaries
             WHERE " . implode(' AND ', $where) . " GROUP BY beneficiary_status",
            $params
        );
    }

    public function housingApplicationsByCategory($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'hac', $params));
        return $this->db->query(
            "SELECT category, COUNT(*) AS total FROM housing_beneficiaries
             WHERE " . implode(' AND ', $where) . " GROUP BY category ORDER BY total DESC",
            $params
        );
    }

    public function housingApplicationsOverTime($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = ["status = 'Active'"];
        $dateCol = 'COALESCE(application_date, created_at)';

        if (!empty($dateFrom) || !empty($dateTo)) {
            $where = array_merge($where, $this->dateRangeClause($dateCol, $dateFrom, $dateTo, 'hot', $params));
        } else {
            $where[] = "{$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        }

        return $this->db->query(
            "SELECT DATE_FORMAT({$dateCol}, '%Y-%m') AS month, COUNT(*) AS total
             FROM housing_beneficiaries WHERE " . implode(' AND ', $where) . "
             GROUP BY month ORDER BY month",
            $params
        );
    }

    public function housingApplicationsStatusOverTime($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = ["status = 'Active'"];
        $dateCol = 'COALESCE(application_date, created_at)';

        if (!empty($dateFrom) || !empty($dateTo)) {
            $where = array_merge($where, $this->dateRangeClause($dateCol, $dateFrom, $dateTo, 'haso', $params));
        } else {
            $where[] = "{$dateCol} >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        }

        return $this->db->query(
            "SELECT DATE_FORMAT({$dateCol}, '%Y-%m') AS month,
                    SUM(beneficiary_status IN ('Applicant','Qualified')) AS pending,
                    SUM(beneficiary_status = 'Awarded') AS approved,
                    SUM(beneficiary_status = 'Disqualified') AS rejected
             FROM housing_beneficiaries WHERE " . implode(' AND ', $where) . "
             GROUP BY month ORDER BY month",
            $params
        );
    }

    public function housingUnitsByBarangay($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = array_merge(
            ["status = 'Active'", "barangay IS NOT NULL AND barangay <> ''"],
            $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'hub', $params)
        );
        return $this->db->query(
            "SELECT barangay, COUNT(*) AS total FROM housing_units
             WHERE " . implode(' AND ', $where) . " GROUP BY barangay ORDER BY total DESC LIMIT 15",
            $params
        );
    }

    public function projectsByStatus($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'pbs', $params));
        return $this->db->query(
            "SELECT project_status, COUNT(*) AS total FROM urban_projects
             WHERE " . implode(' AND ', $where) . " GROUP BY project_status",
            $params
        );
    }

    public function projectsByType($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = array_merge(["status = 'Active'"], $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'pbt', $params));
        return $this->db->query(
            "SELECT project_type, COUNT(*) AS total FROM urban_projects
             WHERE " . implode(' AND ', $where) . " GROUP BY project_type ORDER BY total DESC",
            $params
        );
    }

    public function projectsByBarangay($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = array_merge(
            ["status = 'Active'", "barangay IS NOT NULL AND barangay <> ''"],
            $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'pbb', $params)
        );
        return $this->db->query(
            "SELECT barangay, COUNT(*) AS total FROM urban_projects
             WHERE " . implode(' AND ', $where) . " GROUP BY barangay ORDER BY total DESC LIMIT 15",
            $params
        );
    }

    public function projectsOverTime($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = ["status = 'Active'"];

        if (!empty($dateFrom) || !empty($dateTo)) {
            $where = array_merge($where, $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'pot', $params));
        } else {
            $where[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        }

        return $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
             FROM urban_projects WHERE " . implode(' AND ', $where) . "
             GROUP BY month ORDER BY month",
            $params
        );
    }

    public function residentsByBarangay($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = array_merge(
            ["status = 'Active'", "barangay IS NOT NULL AND barangay <> ''"],
            $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'rbb', $params)
        );
        return $this->db->query(
            "SELECT barangay, COUNT(*) AS total FROM residents
             WHERE " . implode(' AND ', $where) . " GROUP BY barangay ORDER BY total DESC LIMIT 15",
            $params
        );
    }

    public function residentsOverTime($dateFrom = null, $dateTo = null)
    {
        $params = [];
        $where = ["status = 'Active'"];

        if (!empty($dateFrom) || !empty($dateTo)) {
            $where = array_merge($where, $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'rot', $params));
        } else {
            $where[] = "created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)";
        }

        return $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS total
             FROM residents WHERE " . implode(' AND ', $where) . "
             GROUP BY month ORDER BY month",
            $params
        );
    }

    // Per-barangay comparison across all local modules that carry a barangay column.
    // Only barangays with at least one non-zero count are returned, rather than all
    // 188 seeded barangays, so the Location Analytics table doesn't fill up with zeros.
    public function locationComparison($dateFrom = null, $dateTo = null)
    {
        $paramsRes = [];
        $whereRes = array_merge(
            ["status = 'Active'", "barangay IS NOT NULL AND barangay <> ''"],
            $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'lcr', $paramsRes)
        );
        $residents = $this->db->query(
            "SELECT barangay, COUNT(*) AS total FROM residents WHERE " . implode(' AND ', $whereRes) . " GROUP BY barangay",
            $paramsRes
        );

        $paramsUnits = [];
        $whereUnits = array_merge(
            ["status = 'Active'", "barangay IS NOT NULL AND barangay <> ''"],
            $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'lcu', $paramsUnits)
        );
        $units = $this->db->query(
            "SELECT barangay, COUNT(*) AS total FROM housing_units WHERE " . implode(' AND ', $whereUnits) . " GROUP BY barangay",
            $paramsUnits
        );

        $paramsApp = [];
        $whereApp = array_merge(
            ["b.status = 'Active'", "COALESCE(res.barangay, h.barangay) IS NOT NULL AND COALESCE(res.barangay, h.barangay) <> ''"],
            $this->dateRangeClause('b.created_at', $dateFrom, $dateTo, 'lca', $paramsApp)
        );
        $applications = $this->db->query(
            "SELECT COALESCE(res.barangay, h.barangay) AS barangay, COUNT(*) AS total
             FROM housing_beneficiaries b
             LEFT JOIN residents res ON b.resident_id = res.resident_id
             LEFT JOIN households h ON b.household_id = h.household_id
             WHERE " . implode(' AND ', $whereApp) . "
             GROUP BY barangay",
            $paramsApp
        );

        $paramsProj = [];
        $whereProj = array_merge(
            ["status = 'Active'", "barangay IS NOT NULL AND barangay <> ''"],
            $this->dateRangeClause('created_at', $dateFrom, $dateTo, 'lcp', $paramsProj)
        );
        $projects = $this->db->query(
            "SELECT barangay, COUNT(*) AS total FROM urban_projects WHERE " . implode(' AND ', $whereProj) . " GROUP BY barangay",
            $paramsProj
        );

        $combined = [];
        $merge = function ($rows, $key) use (&$combined) {
            foreach ($rows as $row) {
                $name = $row['barangay'];
                if ($name === null || $name === '') {
                    continue;
                }
                if (!isset($combined[$name])) {
                    $combined[$name] = ['barangay' => $name, 'residents' => 0, 'housing_units' => 0, 'applications' => 0, 'projects' => 0];
                }
                $combined[$name][$key] = (int)$row['total'];
            }
        };
        $merge($residents, 'residents');
        $merge($units, 'housing_units');
        $merge($applications, 'applications');
        $merge($projects, 'projects');

        $grandTotal = 0;
        foreach ($combined as &$row) {
            $row['total'] = $row['residents'] + $row['housing_units'] + $row['applications'] + $row['projects'];
            $grandTotal += $row['total'];
        }
        unset($row);

        foreach ($combined as &$row) {
            $row['percent'] = $grandTotal > 0 ? round(($row['total'] / $grandTotal) * 100, 1) : 0.0;
        }
        unset($row);

        $result = array_values($combined);
        usort($result, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return $result;
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
