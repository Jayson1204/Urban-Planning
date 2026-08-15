<?php

namespace App\Services;

class ActivityLogService
{
    private $repo;

    public function __construct($repo)
    {
        $this->repo = $repo;
    }

    /**
     * Record one activity log entry. Never throws - a logging failure must not
     * break the write operation it was called from.
     */
    public function record($module, $action, $targetTable, $targetId, $targetLabel, $description = null)
    {
        try {
            global $headerUser;
            $userId = $_SESSION['user_id'] ?? null;
            $actorName = $headerUser['full_name'] ?? 'System User';

            $this->repo->create([
                'user_id' => $userId,
                'actor_name' => $actorName,
                'module' => $module,
                'action' => $action,
                'target_table' => $targetTable,
                'target_id' => $targetId !== null ? (string)$targetId : null,
                'target_label' => $targetLabel,
                'description' => $description,
            ]);
        } catch (\Throwable $e) {
            error_log('ActivityLogService::record failed: ' . $e->getMessage());
        }
    }
}
