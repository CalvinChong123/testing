<?php

namespace App\Library;

class RoleTag
{
    /**
     * NEXUS: System Default Roles (Use only in initSeeder).
     * ! As a Permission-based system, use PermissionTag instead
     */
    public const ETC_SUPER_ADMIN = 'ETC Admin';

    public const CLIENT_SUPER_ADMIN = 'Super Admin';

    public const ADMIN = 'Admin';

    public const USER = 'User';
}
