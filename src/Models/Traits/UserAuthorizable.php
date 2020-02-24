<?php namespace WebEd\Base\ACL\Models\Traits;

trait UserAuthorizable
{
    /**
     * Set relationship
     * @return mixed
     */
    public function roles()
    {
        return $this->belongsToMany(\WebEd\Base\ACL\Models\Role::class, 'users_roles', 'user_id', 'role_id');
    }

    /**
     * @return bool
     */
    public function isSuperAdmin()
    {
        if (check_user_acl()->hasRoles($this->id, ['super-admin'])) {
            return true;
        }

        $relatedRoles = $this->roles()->select('slug')->get()->pluck('slug')->toArray();
        check_user_acl()->pushRoles($this->id, $relatedRoles);
        if (check_user_acl()->hasRoles($this->id, ['super-admin'])) {
            return true;
        }
        return false;
    }

    /**
     * @param array|string $roles
     * @return bool
     */
    public function hasRole($roles)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if(!is_array($roles)) {
            $roles = func_get_args();
        }

        if (!$roles) {
            return true;
        }

        $roles = array_values($roles);

        if (check_user_acl()->hasRoles($this->id, $roles)) {
            return true;
        }

        $relatedRoles = $this->roles()->select('slug')->get()->pluck('slug')->toArray();
        check_user_acl()->pushRoles($this->id, $relatedRoles);

        if (check_user_acl()->hasRoles($this->id, $roles)) {
            return true;
        }
        return false;
    }

    /**
     * @param string|array $permissions
     * @return bool
     */
    public function hasPermission($permissions)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if(!is_array($permissions)) {
            $permissions = func_get_args();
        }

        if (!$permissions) {
            return true;
        }

        $permissions = array_values($permissions);

        if (check_user_acl()->hasPermissions($this->id, $permissions)) {
            return true;
        }

        $relatedPermissions = static::join('users_roles', 'users_roles.user_id', '=', 'users.id')
            ->join('roles', 'users_roles.role_id', '=', 'roles.id')
            ->join('roles_permissions', 'roles_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'roles_permissions.permission_id', '=', 'permissions.id')
            ->where('users.id', '=', $this->id)
            ->distinct()
            ->groupBy('permissions.id', 'permissions.slug')
            ->select('permissions.slug')
            ->get()
            ->pluck('slug')
            ->toArray();

        check_user_acl()->pushPermissions($this->id, $relatedPermissions);

        if (check_user_acl()->hasPermissions($this->id, $permissions)) {
            return true;
        }

        return false;
    }
}
