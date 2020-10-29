<?php

namespace Weirdo\Entrust\Traits;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
use InvalidArgumentException;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Weirdo\Entrust\Traits\EntrustHelperTrait;

trait EntrustOptionMenuTrait
{
    use EntrustHelperTrait;

    /**
     * Big block of caching functionality.
     * @return mixed Roles
     */
    public function cachedRoles()
    {
        $optionMenuPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_roles_for_option_menu_' . $this->$optionMenuPrimaryKey;

        return $this->getCachedRoles(
            'entrust.options_menu_table',
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
            Cache::tags(Config::get('entrust.options_menu_table'))->flush();
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
            Cache::tags(Config::get('entrust.options_menu_table'))->flush();
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
            Cache::tags(Config::get('entrust.options_menu_table'))->flush();
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
            Config::get('entrust.role_option_menu_table'),
            Config::get('entrust.option_menu_foreign_key'),
            Config::get('entrust.role_foreign_key')
        );
    }

    /**
     * belongs-To relations with Module.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module()
    {
        return $this->belongsTo(Config::get('entrust.module'), Config::get('entrust.module_foreign_key'));
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
     * Checks if the user has a role by its name.
     * @param string|array $name Role name or array of role names.
     * @param bool $requireAll All roles in the array are required.
     * @return bool
     */
    public function hasRole($name, $requireAll = false)
    {
        $optionMenuPrimaryKey = $this->primaryKey;
        $cacheKey = 'entrust_roles_for_option_menu_' . $this->$optionMenuPrimaryKey;

        return $this->checkIfItHasRole(
            $name,
            'entrust.options_menu_table',
            $cacheKey,
            $requireAll
        );
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
}
