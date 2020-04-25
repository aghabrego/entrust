<?php

namespace Weirdo\Entrust\Contracts;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
interface EntrustModuleInterface
{
    /**
     * Many-to-Many relations with Role.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles();

    /**
     * Checks if the user has a role by its name.
     * @param string|array $name Role name or array of role names.
     * @param bool $requireAll All roles in the array are required.
     * @return bool
     */
    public function hasRole($name, $requireAll = false);

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     * @param mixed $role
     */
    public function attachRole($role);

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     * @param mixed $role
     */
    public function detachRole($role);

    /**
     * Attach multiple roles to a user
     * @param mixed $roles
     */
    public function attachRoles($roles);

    /**
     * Detach multiple roles from a user
     * @param mixed $roles
     */
    public function detachRoles($roles);
}
