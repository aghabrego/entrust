<?php

namespace Weirdo\Entrust\Traits;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
use Illuminate\Support\Facades\Config;
use Weirdo\Entrust\Traits\EntrustHelperTrait;

trait EntrustPermissionTrait
{
    use EntrustHelperTrait;

    /**
     * Many-to-Many relations with role model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            Config::get('entrust.role'),
            Config::get('entrust.permission_role_table'),
            Config::get('entrust.permission_foreign_key'),
            Config::get('entrust.role_foreign_key')
        );
    }

    /**
     * Boot the permission model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the permission model uses soft deletes.
     * @return void|bool
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($permission) {
            if (!method_exists(Config::get('entrust.permission'), 'bootSoftDeletes')) {
                $permission->roles()->sync([]);
            }

            return true;
        });
    }

    /**
     * Big block of caching functionality.
     * @param string $permissionName
     * @return mixed Roles
     */
    public function cachedPermission($permissionName)
    {
        $primaryKey = $this->primaryKey;
        $cacheKey = 'entrust_permission_' . $this->$primaryKey;

        return $this->getCachedPermission(
            'entrust.permissions_table',
            $cacheKey,
            $permissionName
        );
    }
}
