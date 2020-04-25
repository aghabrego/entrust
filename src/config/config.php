<?php

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
return [

    /**
    |---------------------------------------------------------------------------
    | Entrust Module Model
    |---------------------------------------------------------------------------
    |
    | This is the module model used by Entrust
    |
    */
    'module' => 'App\Module',

    /**
    |---------------------------------------------------------------------------
    | Entrust Module Model
    |---------------------------------------------------------------------------
    |
    | This is the modules table used by Entrust to save roles to the database.
    |
    */
    'modules_table' => 'modules',

    /*
    |--------------------------------------------------------------------------
    | Entrust role_module Table
    |--------------------------------------------------------------------------
    |
    | This is the role_module table used by Entrust to save assigned roles to the
    | database.
    |
    */
    'role_module_table' => 'role_module',

    /**
    |---------------------------------------------------------------------------
    | Entrust module foreign key
    |---------------------------------------------------------------------------
    |
    | This is the modules foreign key used by Entrust to make a proper
    | relation between modules and roles and permissions
    |
    */
    'module_foreign_key' => 'module_id',

    /**
    |---------------------------------------------------------------------------
    | Entrust OptionMenu Model
    |---------------------------------------------------------------------------
    |
    | This is the Option menu model used by Entrust
    |
    */
    'option_menu' => 'App\OptionMenu',

    /**
    |---------------------------------------------------------------------------
    | Entrust OptionMenu Model
    |---------------------------------------------------------------------------
    |
    | This is the options menu table used by Entrust to save roles to the database.
    |
    */
    'options_menu_table' => 'options_menu',

    /*
    |--------------------------------------------------------------------------
    | Entrust role_option_menu Table
    |--------------------------------------------------------------------------
    |
    | This is the role_option_menu table used by Entrust to save assigned roles to the
    | database.
    |
    */
    'role_option_menu_table' => 'role_option_menu',

    /*
    |--------------------------------------------------------------------------
    | Entrust option menu foreign key
    |--------------------------------------------------------------------------
    |
    | This is the option menu foreign key used by Entrust to make a proper
    | relation between roles and options menu.
    |
    */
    'option_menu_foreign_key' => 'option_menu_id',

    /*
    |--------------------------------------------------------------------------
    | Entrust Role Model
    |--------------------------------------------------------------------------
    |
    | This is the Role model used by Entrust to create correct relations.  Update
    | the role if it is in a different namespace.
    |
    */
    'role' => 'App\Role',

    /*
    |--------------------------------------------------------------------------
    | Entrust Roles Table
    |--------------------------------------------------------------------------
    |
    | This is the roles table used by Entrust to save roles to the database.
    |
    */
    'roles_table' => 'roles',

    /*
    |--------------------------------------------------------------------------
    | Entrust role foreign key
    |--------------------------------------------------------------------------
    |
    | This is the role foreign key used by Entrust to make a proper
    | relation between permissions and roles & roles and users
    |
    */
    'role_foreign_key' => 'role_id',

    /*
    |--------------------------------------------------------------------------
    | Application User Model
    |--------------------------------------------------------------------------
    |
    | This is the User model used by Entrust to create correct relations.
    | Update the User if it is in a different namespace.
    |
    */
    'user' => 'App\User',

    /*
    |--------------------------------------------------------------------------
    | Application Users Table
    |--------------------------------------------------------------------------
    |
    | This is the users table used by the application to save users to the
    | database.
    |
    */
    'users_table' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Entrust role_user Table
    |--------------------------------------------------------------------------
    |
    | This is the role_user table used by Entrust to save assigned roles to the
    | database.
    |
    */
    'role_user_table' => 'role_user',

    /*
    |--------------------------------------------------------------------------
    | Entrust user foreign key
    |--------------------------------------------------------------------------
    |
    | This is the user foreign key used by Entrust to make a proper
    | relation between roles and users
    |
    */
    'user_foreign_key' => 'user_id',

    /*
    | Tags using to cache a user's projects.
    */
    'projects_for_user' => 'tags_projects_for_user',

    /*
    |--------------------------------------------------------------------------
    | Entrust Permission Model
    |--------------------------------------------------------------------------
    |
    | This is the Permission model used by Entrust to create correct relations.
    | Update the permission if it is in a different namespace.
    |
    */
    'permission' => 'App\Permission',

    /*
    |--------------------------------------------------------------------------
    | Entrust Permissions Table
    |--------------------------------------------------------------------------
    |
    | This is the permissions table used by Entrust to save permissions to the
    | database.
    |
    */
    'permissions_table' => 'permissions',

    /*
    |--------------------------------------------------------------------------
    | Entrust permission_role Table
    |--------------------------------------------------------------------------
    |
    | This is the permission_role table used by Entrust to save relationship
    | between permissions and roles to the database.
    |
    */
    'permission_role_table' => 'permission_role',

    /*
    |--------------------------------------------------------------------------
    | Entrust permission foreign key
    |--------------------------------------------------------------------------
    |
    | This is the permission foreign key used by Entrust to make a proper
    | relation between permissions and roles
    |
    */
    'permission_foreign_key' => 'permission_id',
];
