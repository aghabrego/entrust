<?php

namespace Weirdo\Entrust\Traits;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
use Weirdo\Helper\Helper;
use InvalidArgumentException;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Weirdo\Entrust\Traits\EntrustHelperTrait;

trait EntrustUserTrait
{
    use Helper, EntrustHelperTrait;

    /**
     * Big block of caching functionality.
     * @return mixed Roles
     */
    public function cachedRoles()
    {
        $userPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_roles_for_user_' . $this->$userPrimaryKey;

        return $this->getCachedRoles(
            'entrust.role_user_table',
            $cacheKey
        );
    }

    /**
     * @param string $controller
     * @return mixed
     */
    public function getCacheModuleUser($controller)
    {
        $userPrimaryKey = $this->primaryKey;
        $cacheKey = "{$controller}_{$this->$userPrimaryKey}";

        return $this->getCachedModule(
            $controller,
            'entrust.role_user_table',
            $cacheKey
        );
    }

    /**
     * {@inheritDoc}
     */
    public function save(array $options = [])
    {
        //both inserts and updates
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.role_user_table'))->flush();
            Cache::tags(Config::get('entrust.projects_for_user'))->flush();
        }

        return parent::save($options);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(array $options = [])
    {
        //soft or hard
        $result = parent::delete($options);
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.role_user_table'))->flush();
            Cache::tags(Config::get('entrust.projects_for_user'))->flush();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function restore()
    {
        //soft delete undo's
        $result = parent::restore();
        if (Cache::getStore() instanceof TaggableStore) {
            Cache::tags(Config::get('entrust.role_user_table'))->flush();
            Cache::tags(Config::get('entrust.projects_for_user'))->flush();
        }

        return $result;
    }

    /**
     * Many-to-Many relations with Role.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            Config::get('entrust.role'),
            Config::get('entrust.role_user_table'),
            Config::get('entrust.user_foreign_key'),
            Config::get('entrust.role_foreign_key')
        );
    }

    /**
     * Boot the user model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the user model uses soft deletes.
     * @return void|bool
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            if (!method_exists(Config::get('auth.providers.users.model'), 'bootSoftDeletes')) {
                $user->roles()->sync([]);
            }

            return true;
        });
    }

    /**
     * Checks if the user has a module by its name.
     * @param string|array $name module name or array of role names.
     * @param bool $requireAll All modules in the array are required.
     * @return bool
     */
    public function hasModules($name, $requireAll = false)
    {
        $userPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_roles_for_user_' . $this->$userPrimaryKey;

        return $this->checkIfItHasModule(
            $name,
            'entrust.role_user_table',
            $cacheKey,
            $requireAll
        );
    }

    /**
     * Checks if the user has a role by its name.
     * @param string|array $name Role name or array of role names.
     * @param bool $requireAll All roles in the array are required.
     * @return bool
     */
    public function hasRole($name, $requireAll = false)
    {
        $userPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_roles_for_user_' . $this->$userPrimaryKey;

        return $this->checkIfItHasRole(
            $name,
            'entrust.role_user_table',
            $cacheKey,
            $requireAll
        );
    }

    /**
     * Check if user has a permission by its name.
     * @param string|array $permission Permission string or array of permissions.
     * @param bool $requireAll All permissions in the array are required.
     * @return bool
     */
    public function can($permission, $requireAll = false)
    {
        $userPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_roles_for_user_' . $this->$userPrimaryKey;

        return $this->checkIfItHasCan(
            $permission,
            'entrust.role_user_table',
            $cacheKey
        );
    }

    /**
     * Checks role(s) and permission(s).
     * @param string|array $roles Array of roles or comma separated string
     * @param string|array $permissions Array of permissions or comma separated string.
     * @param array|string $modules The modules needed.
     * @param array $options validate_all (true|false) or return_type (boolean|array|both)
     * @throws \InvalidArgumentException
     * @return array|bool
     */
    public function ability($roles, $permissions, $modules, $options = [])
    {
        // Convert string to array if that's what is passed in.
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        if (!is_array($permissions)) {
            $permissions = explode(',', $permissions);
        }
        if (!is_array($modules)) {
            $modules = explode(',', $modules);
        }

        // Set up default values and validate options.
        if (!isset($options['validate_all'])) {
            $options['validate_all'] = false;
        } else {
            if ($options['validate_all'] !== true && $options['validate_all'] !== false) {
                throw new InvalidArgumentException();
            }
        }

        if (!isset($options['return_type'])) {
            $options['return_type'] = 'boolean';
        } else {
            if ($options['return_type'] != 'boolean' && $options['return_type'] != 'array' && $options['return_type'] != 'both') {
                throw new InvalidArgumentException();
            }
        }

        // Loop through roles, modules and permissions and check each.
        $checkedRoles = [];
        $checkedModules = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->can($permission);
        }
        foreach ($modules as $module) {
            $searchResult = $this->getCacheModuleUser($module);
            $checkedModules[$module] = [
                'has' => $this->hasModules($module),
                'id_name' => $searchResult,
            ];
        }

        // If validate all and there is a false in either
        // Check that if validate all, then there should not be any false.
        // Check that if not validate all, there must be at least one true.
        if (($options['validate_all'] && !($this->arrayInWith($checkedRoles, false) || $this->arrayInWith($checkedPermissions, false) || $this->arrayInWith($checkedModules, false))) ||
            (!$options['validate_all'] && ($this->arrayInWith($checkedRoles, true) || $this->arrayInWith($checkedPermissions, true) || $this->arrayInWith($checkedModules, true)))) {
            $validateAll = true;
        } else {
            $validateAll = false;
        }

        // Return based on option
        if ($options['return_type'] == 'boolean') {
            return $validateAll;
        } elseif ($options['return_type'] == 'array') {
            return ['roles' => $checkedRoles, 'permissions' => $checkedPermissions, 'modules' => $checkedModules];
        } else {
            return [$validateAll, ['roles' => $checkedRoles, 'permissions' => $checkedPermissions, 'modules' => $checkedModules]];
        }
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     * @param mixed $role
     */
    public function attachRole($role)
    {
        if (is_object($role)) {
            $role = $role->getKey();
        }

        if (is_array($role)) {
            $role = $role['id'];
        }

        $this->roles()->attach($role);
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     * @param mixed $role
     */
    public function detachRole($role)
    {
        if (is_object($role)) {
            $role = $role->getKey();
        }

        if (is_array($role)) {
            $role = $role['id'];
        }

        $this->roles()->detach($role);
    }

    /**
     * Attach multiple roles to a user
     * @param mixed $roles
     */
    public function attachRoles($roles)
    {
        foreach ($roles as $role) {
            $this->attachRole($role);
        }
    }

    /**
     * Detach multiple roles from a user
     * @param mixed $roles
     */
    public function detachRoles($roles=null)
    {
        if (!$roles) {
            $roles = $this->roles()->get();
        }

        foreach ($roles as $role) {
            $this->detachRole($role);
        }
    }

    /**
     *Filtering users according to their role
     *@param string $role
     *@return users collection
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function ($query) use ($role) {
            $query->where('name', $role);
        });
    }

    /**
     * @param array|string $modules
     * @param string|array $permissions
     * @param boolean $validateAll
     * @return boolean
     */
    public function verifyAbility($modules, $permissions, $validateAll = false)
    {
        if ($this instanceof Model) {
            $roles = $this->cachedRoles();

            return $this->ability($roles, $permissions, $modules, ['validate_all' => $validateAll]);
        }

        return false;
    }
}
