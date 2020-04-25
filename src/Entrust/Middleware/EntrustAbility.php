<?php

namespace Weirdo\Entrust\Middleware;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
use Closure;
use Illuminate\Contracts\Auth\Guard;

class EntrustAbility
{
    const DELIMITER = '|';

    protected $auth;

    /**
     * Creates a new instance of the middleware.
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param mixed $roles
     * @param mixed $permissions
     * @param mixed $modules
     * @param bool $validateAll
     * @return mixed
     */
    public function handle($request, Closure $next, $roles, $permissions, $modules, $validateAll = false)
    {
        if (!is_array($roles)) {
            $roles = explode(self::DELIMITER, $roles);
        }

        if (!is_array($permissions)) {
            $permissions = explode(self::DELIMITER, $permissions);
        }

        if (!is_array($modules)) {
            $modules = explode(self::DELIMITER, $modules);
        }

        if (!is_bool($validateAll)) {
            $validateAll = filter_var($validateAll, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->auth->guest() || !$request->user()->ability($roles, $permissions, $modules, ['validate_all' => $validateAll])) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
