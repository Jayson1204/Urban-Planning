<?php

namespace App\Repositories;

class CapstoneModulePermissionRepository
{
    private $db;

    /**
     * Single source of truth for which capstone modules exist and can be
     * granted to a role. Add an entry here as each new module phase ships.
     */
    public static function moduleDefinitions()
    {
        return [
            'resident_management' => ['label' => 'Resident Management', 'icon' => 'fa-people-roof'],
            'urban_planning' => ['label' => 'Urban Planning', 'icon' => 'fa-map-location-dot'],
        ];
    }

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getModulesForRole($roleId)
    {
        $rows = $this->db->query(
            "SELECT module_slug FROM capstone_module_permissions WHERE role_id = :role_id",
            ['role_id' => $roleId]
        );
        return array_column($rows, 'module_slug');
    }

    public function roleHasModule($roleId, $moduleSlug)
    {
        if (empty($roleId)) {
            return false;
        }
        $rows = $this->db->query(
            "SELECT 1 FROM capstone_module_permissions WHERE role_id = :role_id AND module_slug = :module_slug",
            ['role_id' => $roleId, 'module_slug' => $moduleSlug]
        );
        return !empty($rows);
    }

    public function getAllAssignments()
    {
        $rows = $this->db->query("SELECT role_id, module_slug FROM capstone_module_permissions");
        $byRole = [];
        foreach ($rows as $row) {
            $byRole[(int)$row['role_id']][] = $row['module_slug'];
        }
        return $byRole;
    }

    /**
     * Replace the full set of module grants for a role with the given list.
     */
    public function setModulesForRole($roleId, array $moduleSlugs)
    {
        $validSlugs = array_keys(static::moduleDefinitions());
        $moduleSlugs = array_values(array_intersect($moduleSlugs, $validSlugs));

        $this->db->delete('capstone_module_permissions', ['role_id' => $roleId]);
        foreach ($moduleSlugs as $slug) {
            $this->db->insert('capstone_module_permissions', [
                'role_id' => $roleId,
                'module_slug' => $slug,
            ]);
        }
        return $moduleSlugs;
    }
}
