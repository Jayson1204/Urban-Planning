-- Retires the local, per-role module access table. Resident Management / Housing Management /
-- Urban Planning sidebar sections are now gated the same way as every other module: via
-- production's shared Role & Permission system (granted_resources), using Module Management to
-- create the module/resource and Permission Builder to grant it to roles. There is no longer a
-- separate local grant flag to keep in sync.

DROP TABLE IF EXISTS `local_module_permissions`;
