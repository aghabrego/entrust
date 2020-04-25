<?php

namespace Weirdo\Entrust\Traits;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Zizaco\Entrust
 */
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Weirdo\Entrust\Traits\EntrustHelperTrait;

trait EntrustRoleTrait
{
    use EntrustHelperTrait;

    public function cachedPermissions()
    {
        $rolePrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_permissions_for_role_' . $this->$rolePrimaryKey;

        return $this->getCachedPermissions(
            'entrust.permission_role_table',
            $cacheKey
        );
    }

    public function cachedModules()
    {
        $userPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_modules_for_role_' . $this->$userPrimaryKey;

        return $this->getCachedModules(
            'entrust.role_module_table',
            $cacheKey
        );
    }

    public function save(array $options = [])
    {
        //both inserts and updates
        if (!parent::save($options)) {
            return false;
        }

        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.permission_role_table'))->flush();
            Cache::tags(Config::get('entrust.options_menu_table'))->flush();
            Cache::tags(Config::get('entrust.role_module_table'))->flush();
            Cache::tags(Config::get('entrust.modules_table'))->flush();
        }

        return true;
    }

    public function delete(array $options = [])
    {
        //soft or hard
        if (!parent::delete($options)) {
            return false;
        }

        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.permission_role_table'))->flush();
            Cache::tags(Config::get('entrust.options_menu_table'))->flush();
            Cache::tags(Config::get('entrust.role_module_table'))->flush();
            Cache::tags(Config::get('entrust.modules_table'))->flush();
        }

        return true;
    }

    public function restore()
    {
        //soft delete undo's
        if (!parent::restore()) {
            return false;
        }

        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.permission_role_table'))->flush();
            Cache::tags(Config::get('entrust.options_menu_table'))->flush();
            Cache::tags(Config::get('entrust.role_module_table'))->flush();
            Cache::tags(Config::get('entrust.modules_table'))->flush();
        }

        return true;
    }

    /**
     * Many-to-Many relations with the user model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            Config::get('auth.providers.users.model'),
            Config::get('entrust.role_user_table'),
            Config::get('entrust.role_foreign_key'),
            Config::get('entrust.user_foreign_key')
        );
    }

    /**
     * Many-to-Many relations with the module model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function modules()
    {
        return $this->belongsToMany(
            Config::get('entrust.module'),
            Config::get('entrust.role_module_table'),
            Config::get('entrust.role_foreign_key'),
            Config::get('entrust.module_foreign_key')
        );
    }

    /**
     * Many-to-Many relations with the module model.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function optionsMenu()
    {
        return $this->belongsToMany(
            Config::get('entrust.option_menu'),
            Config::get('entrust.role_option_menu_table'),
            Config::get('entrust.role_foreign_key'),
            Config::get('entrust.option_menu_foreign_key')
        );
    }

    /**
     * Many-to-Many relations with the permission model.
     * Named "perms" for backwards compatibility. Also because "perms" is short and sweet.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function perms()
    {
        return $this->belongsToMany(
            Config::get('entrust.permission'),
            Config::get('entrust.permission_role_table'),
            Config::get('entrust.role_foreign_key'),
            Config::get('entrust.permission_foreign_key')
        );
    }

    /**
     * Boot the role model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the role model uses soft deletes.
     * @return void|bool
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($role) {
            if (!method_exists(Config::get('entrust.role'), 'bootSoftDeletes')) {
                $role->users()->sync([]);
                $role->modules()->sync([]);
                $role->optionsMenu()->sync([]);
                $role->perms()->sync([]);
            }

            return true;
        });
    }

    /**
     * Checks if the role has a permission by its name.
     * @param string|array $name Permission name or array of permission names.
     * @param bool $requireAll All permissions in the array are required.
     * @return bool
     */
    public function hasPermission($name, $requireAll = false)
    {
        $rolePrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_permissions_for_role_' . $this->$rolePrimaryKey;

        return $this->checkIfItHasCanRole(
            $name,
            'entrust.permission_role_table',
            $cacheKey
        );
    }

    /**
     * Save the inputted permissions.
     * @param mixed $inputPermissions
     * @return void
     */
    public function savePermissions($inputPermissions)
    {
        if (!empty($inputPermissions)) {
            $this->perms()->sync($inputPermissions);
        } else {
            $this->perms()->detach();
        }

        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.permission_role_table'))->flush();
            Cache::tags(Config::get('entrust.options_menu_table'))->flush();
            Cache::tags(Config::get('entrust.role_module_table'))->flush();
            Cache::tags(Config::get('entrust.modules_table'))->flush();
        }
    }

    /**
     * Attach permission to current role.
     * @param object|array $permission
     * @return void
     */
    public function attachPermission($permission)
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            return $this->attachPermissions($permission);
        }

        $this->perms()->attach($permission);
    }

    /**
     * Detach permission from current role.
     * @param object|array $permission
     * @return void
     */
    public function detachPermission($permission)
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            return $this->detachPermissions($permission);
        }

        $this->perms()->detach($permission);
    }

    /**
     * Attach multiple permissions to current role.
     * @param mixed $permissions
     * @return void
     */
    public function attachPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            $this->attachPermission($permission);
        }
    }

    /**
     * Detach multiple permissions from current role
     * @param mixed $permissions
     * @return void
     */
    public function detachPermissions($permissions = null)
    {
        if (!$permissions) {
            $permissions = $this->perms()->get();
        }

        foreach ($permissions as $permission) {
            $this->detachPermission($permission);
        }
    }
}
