<?php

namespace App\Services;

class NotificationService
{
    /**
     * Dispatch a notification triggered by an audited action.
     *
     * @param int $auditId The ID of the audit log record
     * @param string $actionName The audited action string (e.g., "Create User Account")
     * @param int $actorUserId The ID of the user who performed the action
     * @param int|null $departmentId The department related to the action
     * @param string|null $targetTable The table impacted by the action
     * @param string|null $targetId The record ID impacted
     * @param string|null $description Full audit log description
     * @return bool
     */
    public static function dispatchFromAudit(
        int $auditId,
        string $actionName,
        int $actorUserId,
        ?int $departmentId,
        ?string $targetTable,
        ?string $targetId,
        ?string $description
    ): bool {
        global $db;

        try {
            if (!$db && class_exists('Database')) {
                $db = \Database::getInstance();
            }
            if (!$db) return false;

            // 1. Filter out routine / excluded actions (page views, standard logins, OTPs, etc.)
            $excludedPatterns = [
                '/view/i',
                '/export/i',
                '/download/i',
                '/login/i',
                '/logout/i',
                '/2fa/i',
                '/otp/i',
                '/timeout/i',
                '/session/i'
            ];

            foreach ($excludedPatterns as $pattern) {
                if (preg_match($pattern, $actionName)) {
                    return true; 
                }
            }

            // 2. Resolve Action ID dynamically from database
            $actionId = self::resolveActionId($actionName);
            if (!$actionId) {
                return false;
            }

            // 3. Resolve Priority
            $priority = self::resolvePriority($actionName, $targetTable);

            // 4. Resolve Title & Message with Actor Name
            $title = self::generateTitle($actionName, $targetTable);
            
            $actorName = 'System User';
            if ($actorUserId > 0) {
                $actorRow = $db->query("SELECT first_name, last_name FROM users WHERE user_id = :uid", ['uid' => $actorUserId]);
                if (!empty($actorRow[0])) {
                    $fullName = trim(($actorRow[0]['first_name'] ?? '') . ' ' . ($actorRow[0]['last_name'] ?? ''));
                    if (!empty($fullName)) {
                        $actorName = $fullName;
                    }
                }
            }

            if (!empty($description)) {
                if (stripos($description, $actorName) === false) {
                    $message = "{$actorName}: {$description}";
                } else {
                    $message = $description;
                }
            } else {
                $message = "{$actorName} performed '{$actionName}' on {$targetTable}" . ($targetId ? " (ID: {$targetId})" : "");
            }

            // 5. Identify Recipients
            $recipients = self::resolveRecipients($actorUserId, $departmentId, $targetTable, $targetId, $actionName);

            // Filter out the actor so users do not receive notifications for their own actions
            $recipients = array_filter($recipients, function($id) use ($actorUserId) {
                return (int)$id !== (int)$actorUserId;
            });

            if (empty($recipients)) {
                return true;
            }

            // 6. Insert notifications for each recipient
            foreach ($recipients as $recipientUserId) {
                $payload = [
                    'audit_id'            => $auditId,
                    'action_id'           => $actionId,
                    'actor_user_id'       => $actorUserId,
                    'recipient_user_id'   => $recipientUserId,
                    'department_id'       => $departmentId,
                    'title'               => $title,
                    'message'             => $message,
                    'priority'            => $priority,
                    'notification_status' => 'Unread',
                    'created_at'          => date('Y-m-d H:i:s')
                ];
                
                $db->insert('notifications', $payload);
            }

            return true;
        } catch (\Throwable $e) {
            error_log("NotificationService Dispatch Failure: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resolve action_id dynamically from database using action_name matching.
     */
    private static function resolveActionId(string $actionName): ?int
    {
        global $db;
        
        $mappedName = 'Edit'; // Default fallback
        $lower = strtolower($actionName);

        if (strpos($lower, 'create') !== false || strpos($lower, 'add') !== false || strpos($lower, 'insert') !== false) {
            $mappedName = 'Create';
        } elseif (strpos($lower, 'delete') !== false || strpos($lower, 'remove') !== false || strpos($lower, 'purge') !== false) {
            $mappedName = 'Delete';
        } elseif (strpos($lower, 'approve') !== false || strpos($lower, 'accept') !== false) {
            $mappedName = 'Approve';
        } elseif (strpos($lower, 'reject') !== false || strpos($lower, 'deny') !== false) {
            $mappedName = 'Reject';
        } elseif (strpos($lower, 'archive') !== false) {
            $mappedName = 'Archive';
        } elseif (strpos($lower, 'restore') !== false) {
            $mappedName = 'Restore';
        } elseif (strpos($lower, 'export') !== false || strpos($lower, 'download') !== false) {
            $mappedName = 'Export';
        } elseif (strpos($lower, 'view') !== false || strpos($lower, 'read') !== false) {
            $mappedName = 'View';
        }

        $res = $db->select('actions', ['action_name' => $mappedName], 'action_id');
        if (!empty($res)) {
            return (int)$res[0]['action_id'];
        }

        // Hard fallback to first action in table if match fails
        $first = $db->query("SELECT action_id FROM actions LIMIT 1");
        return !empty($first) ? (int)$first[0]['action_id'] : null;
    }

    /**
     * Determine notification priority.
     */
    private static function resolvePriority(string $actionName, ?string $targetTable): string
    {
        $lower = strtolower($actionName);

        // Critical events
        if (
            strpos($lower, 'delete') !== false || 
            strpos($lower, 'reject') !== false || 
            strpos($lower, 'lock') !== false ||
            strpos($lower, 'disable') !== false
        ) {
            return 'Critical';
        }

        // High priority events
        if (
            strpos($lower, 'create') !== false || 
            strpos($lower, 'approve') !== false || 
            strpos($lower, 'grant') !== false
        ) {
            return 'High';
        }

        // Normal priority events
        return 'Normal';
    }

    /**
     * Generate user-friendly title.
     */
    private static function generateTitle(string $actionName, ?string $targetTable): string
    {
        $object = $targetTable ? ucwords(str_replace('_', ' ', $targetTable)) : 'System';
        
        // Trim plural 's' for cleaner titles
        if (substr($object, -1) === 's' && $object !== 'Process') {
            $object = substr($object, 0, -1);
        }

        return "{$actionName} — {$object}";
    }

    
    private static function resolveRecipients(
        int $actorUserId, 
        ?int $departmentId, 
        ?string $targetTable, 
        ?string $targetId,
        string $actionName = ''
    ): array {
        global $db;
        $recipients = [];

        try {
            $superAdmins = $db->query("
                SELECT u.user_id 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE r.is_superadmin = 1 OR r.role_prefix IN ('SA', 'SADM') OR LOWER(r.role_name) = 'super administrator'
            ");
        } catch (\Throwable $e) {
            $superAdmins = $db->query("
                SELECT u.user_id 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE r.role_prefix IN ('SA', 'SADM') OR LOWER(r.role_name) = 'super administrator'
            ");
        }
        
        foreach ($superAdmins as $sa) {
            $recipients[] = (int)$sa['user_id'];
        }

        if (in_array(strtolower($targetTable ?? ''), ['users', 'citizen_users', 'user_accounts']) && !empty($targetId)) {
            $targetUserId = (int)$targetId;
            if ($targetUserId > 0) {
                $recipients[] = $targetUserId;
            }
        }

        if (in_array(strtolower($targetTable ?? ''), ['roles', 'role_permissions', 'permissions']) && !empty($targetId)) {
            $targetRoleId = (int)$targetId;
            if ($targetRoleId > 0) {
                $roleUsers = $db->query("SELECT user_id FROM users WHERE role_id = :rid AND status != 'Archived'", ['rid' => $targetRoleId]);
                foreach ($roleUsers as $u) {
                    $recipients[] = (int)$u['user_id'];
                }
            }
        }

        
        $recipients = array_unique($recipients);

        return array_values($recipients);
    }

    /**
     * Fetch unread/read notifications for a user.
     */
    public function getForUser(int $userId, int $limit = 25): array
    {
        global $db;
        
        $sql = "SELECT n.*, 
                       u.first_name AS actor_first, u.last_name AS actor_last, u.profile_picture AS actor_pic,
                       a.action_name,
                       al.target_table, al.target_id
                FROM notifications n
                JOIN users u ON n.actor_user_id = u.user_id
                JOIN actions a ON n.action_id = a.action_id
                LEFT JOIN audit_logs al ON n.audit_id = al.audit_id
                WHERE n.recipient_user_id = :user_id AND n.notification_status != 'Archived'
                ORDER BY n.created_at DESC
                LIMIT :limit";

        $stmt = $db->getPdo()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get count of unread notifications for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        global $db;
        $res = $db->query("SELECT COUNT(*) AS total FROM notifications WHERE recipient_user_id = :user_id AND notification_status = 'Unread'", ['user_id' => $userId]);
        return !empty($res) ? (int)$res[0]['total'] : 0;
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        global $db;
        
        $data = [
            'notification_status' => 'Read',
            'read_at' => date('Y-m-d H:i:s')
        ];
        
        $affected = $db->update('notifications', $data, [
            'notification_id' => $notificationId,
            'recipient_user_id' => $userId
        ]);
        
        return $affected > 0;
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllAsRead(int $userId): bool
    {
        global $db;
        
        $data = [
            'notification_status' => 'Read',
            'read_at' => date('Y-m-d H:i:s')
        ];
        
        $affected = $db->update('notifications', $data, [
            'recipient_user_id' => $userId,
            'notification_status' => 'Unread'
        ]);
        
        return $affected >= 0;
    }
}
