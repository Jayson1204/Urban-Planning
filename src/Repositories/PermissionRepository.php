<?php

namespace App\Repositories;

class PermissionRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getPermissionsByRoleId($roleId)
    {
        $sql = "
            SELECT 
                a.action_name,
                r.resource_name
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.permission_id
            JOIN actions a ON p.action_id = a.action_id
            JOIN resources r ON p.resource_id = r.resource_id
            WHERE rp.role_id = :role_id
        ";
        return $this->db->query($sql, ['role_id' => $roleId]);
    }
}
