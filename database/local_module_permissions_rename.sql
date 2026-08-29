-- Renames the local, role-based module access system away from its old "capstone" naming.
-- Still entirely local and separate from production's shared roles/permissions system;
-- `role_id` in local_module_permissions still refers to production's role_id (no FK possible,
-- since `roles` itself lives on production). Module definitions (which slugs are valid) stay
-- in code (`LocalModulePermissionRepository::moduleDefinitions()`), not a DB table -- module
-- creation/management for the app's actual production modules already lives on the remote
-- system via the existing Module Management page.

RENAME TABLE `capstone_module_permissions` TO `local_module_permissions`;
