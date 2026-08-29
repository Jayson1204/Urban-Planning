<?php

namespace App\Services;

class HeaderService
{
    // How long a fetched permission set is reused before re-querying the remote API.
    // bootstrap.php (and therefore this method) runs on every page load AND every local
    // API call, so without this a single page view can trigger several remote calls back to back.
    private const PERMISSIONS_CACHE_TTL_SECONDS = 30;

    private $userService;
    private $permissionService;
    private $authService;

    public function __construct($userService, $permissionService, $authService)
    {
        $this->userService = $userService;
        $this->permissionService = $permissionService;
        $this->authService = $authService;
    }

    public function buildHeaderUser()
    {
        $headerUser = [
            'full_name' => 'System User',
            'initials' => 'SU',
            'role' => 'Staff',
            'role_prefix' => 'STF',
            'profile_picture' => 'default-avatar.png',
            'is_superadmin' => false,
            'is_global_access' => false,
            'granted_actions' => [],
            'granted_resources' => []
        ];

        if (!$this->authService->isLoggedIn()) {
            return $headerUser;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $employeeId = $_SESSION['employee_id'] ?? null;

        $user = $this->userService->getCurrentUserDetails($userId, $employeeId);

        if ($user) {
            $mid = !empty($user['middle_name']) ? $user['middle_name'] . ' ' : '';
            $headerUser['full_name'] = trim(($user['first_name'] ?? '') . ' ' . $mid . ($user['last_name'] ?? ''));
            $headerUser['initials'] = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'S', 0, 1));
            $headerUser['profile_picture'] = $user['profile_picture'] ?? 'default-avatar.png';
            
            $headerUser['position_id'] = $user['position_id'] ?? null;
            $headerUser['position_name'] = $user['position_name'] ?? '';
            $headerUser['department_id'] = $user['department_id'] ?? ($user['role_dept_id'] ?? null);
            $headerUser['department_name'] = $user['department_name'] ?? '';
            $headerUser['department_code'] = $user['department_code'] ?? '';

            $headerUser['role'] = $user['role_name'] ?? 'Staff';
            $headerUser['role_prefix'] = $user['role_prefix'] ?? 'STF';
            $headerUser['role_id'] = $user['role_id'] ?? null;
            $headerUser['is_global_access'] = filter_var($user['is_global_access'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $roleNameLower = strtolower($headerUser['role']);
            $rolePrefixUpper = strtoupper($headerUser['role_prefix']);

            if (!empty($user['is_superadmin']) || $rolePrefixUpper === 'SA' || $rolePrefixUpper === 'SADM' || $roleNameLower === 'super administrator' || $roleNameLower === 'superadmin') {
                $headerUser['is_superadmin'] = true;
            } else {
                $headerUser['is_superadmin'] = false;
            }

            // Permissions: query the LGU REST API for panel changes, but only once per
            // PERMISSIONS_CACHE_TTL_SECONDS -- this runs on every page load AND every local
            // API call (both require bootstrap.php), so without a cache a single page view
            // can trigger this remote call several times in a row.
            if (!empty($user['role_id'])) {
                $targetRoleId = intval($user['role_id']);
                $cacheAge = time() - (int)($_SESSION['user_permissions_fetched_at'] ?? 0);
                $cacheIsFresh = ($_SESSION['user_permissions_role_id'] ?? null) === $targetRoleId
                    && $cacheAge < self::PERMISSIONS_CACHE_TTL_SECONDS
                    && isset($_SESSION['user_granted_actions'], $_SESSION['user_granted_resources']);

                if ($cacheIsFresh) {
                    $headerUser['granted_actions'] = $_SESSION['user_granted_actions'];
                    $headerUser['granted_resources'] = $_SESSION['user_granted_resources'];
                    return $headerUser;
                }

                require_once __DIR__ . '/../../config/proxy.php';
                $apiBaseUrl = getenv('EXPO_PUBLIC_API_BASE_URL') ?: 'https://civentral.tech/api/employee';
                $remoteUrl = rtrim($apiBaseUrl, '/') . '/permissions.php';
                $res = proxyRequest($remoteUrl, 'GET', null);

                // Fall back to the last known-good permissions on a failed/timed-out remote
                // call, rather than dropping the user's access on a transient network blip.
                $grantedActions = $_SESSION['user_granted_actions'] ?? [];
                $grantedResources = $_SESSION['user_granted_resources'] ?? [];
                $userPermsMap = $_SESSION['user_permissions_map'] ?? [];

                if (!empty($res['body']) && $res['code'] === 200) {
                    $grantedActions = [];
                    $grantedResources = [];
                    $userPermsMap = [];
                    $body = $res['body'];
                    $rolesPerms = $body['role_permissions'] ?? [];
                    $perms = $body['permissions'] ?? [];
                    $resources = $body['resources'] ?? [];
                    $actions = $body['actions'] ?? [];
                    $modules = $body['modules'] ?? [];

                    // Map action names & resource names
                    $actionsMap = [];
                    foreach ($actions as $a) {
                        $actionsMap[$a['action_id']] = strtoupper($a['action_name']);
                    }

                    $resourcesMap = [];
                    foreach ($resources as $r) {
                        $resourcesMap[$r['resource_id']] = strtolower(trim($r['resource_name']));
                    }

                    // Map module names, and which module each resource belongs to, so sidebar
                    // gating can match a granted module's name even when the underlying resource
                    // itself is named something unrelated (e.g. a resource named "test" under
                    // the "Housing Management" module).
                    $moduleNameMap = [];
                    foreach ($modules as $m) {
                        $mid = $m['module_id'] ?? $m['id'] ?? null;
                        if ($mid !== null) {
                            $moduleNameMap[$mid] = strtolower(trim($m['module_name'] ?? $m['name'] ?? ''));
                        }
                    }
                    $resourceModuleMap = [];
                    foreach ($resources as $r) {
                        $resourceModuleMap[$r['resource_id']] = $r['module_id'] ?? null;
                    }

                    $permsMap = [];
                    foreach ($perms as $p) {
                        $permsMap[$p['permission_id']] = [
                            'action_id' => $p['action_id'],
                            'resource_id' => $p['resource_id']
                        ];
                    }
                    
                    foreach ($rolesPerms as $rp) {
                        if (intval($rp['role_id']) === $targetRoleId) {
                            $pId = $rp['permission_id'];
                            if (isset($permsMap[$pId])) {
                                $actId = $permsMap[$pId]['action_id'];
                                $resId = $permsMap[$pId]['resource_id'];
                                
                                if (isset($actionsMap[$actId])) {
                                    $grantedActions[] = $actionsMap[$actId];
                                }
                                if (isset($resourcesMap[$resId])) {
                                    $grantedResources[] = $resourcesMap[$resId];

                                    $modId = $resourceModuleMap[$resId] ?? null;
                                    if ($modId !== null && !empty($moduleNameMap[$modId])) {
                                        $grantedResources[] = $moduleNameMap[$modId];
                                    }
                                }
                                if (isset($actionsMap[$actId]) && isset($resourcesMap[$resId])) {
                                    $actName = $actionsMap[$actId];
                                    $resName = $resourcesMap[$resId];
                                    if (!isset($userPermsMap[$resName])) {
                                        $userPermsMap[$resName] = [];
                                    }
                                    if (!in_array($actName, $userPermsMap[$resName])) {
                                        $userPermsMap[$resName][] = $actName;
                                    }
                                }
                            }
                        }
                    }

                    // Only refresh the cache timestamp on a successful fetch, so a failed/timed-out
                    // call retries on the next request instead of holding the fallback for the full TTL.
                    $_SESSION['user_permissions_fetched_at'] = time();
                    $_SESSION['user_permissions_role_id'] = $targetRoleId;
                }

                $headerUser['granted_actions'] = array_values(array_unique($grantedActions));
                $headerUser['granted_resources'] = array_values(array_unique($grantedResources));

                // Cache inside local session
                $_SESSION['user_granted_actions'] = $headerUser['granted_actions'];
                $_SESSION['user_granted_resources'] = $headerUser['granted_resources'];
                $_SESSION['user_permissions_map'] = $userPermsMap;
            }
        }

        return $headerUser;
    }
}
