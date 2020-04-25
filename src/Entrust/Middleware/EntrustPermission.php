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
use Weirdo\Entrust\Traits\EntrustHelperTrait;

class EntrustPermission
{
    use EntrustHelperTrait;

    const DELIMITER = '|';

    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var string
     */
    protected $action;

    /**
     * Creates a new instance of the middleware.
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
        $this->action = self::getMethodInExecutionModulo();
    }

    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param mixed $permissions
     * @return mixed
     */
    public function handle($request, Closure $next, $permissions = null)
    {
        if (!is_null($permissions)) {
            if (!is_array($permissions)) {
                $permissions = explode(self::DELIMITER, $permissions);
            }

            if ($this->auth->guest() || !$request->user()->can($permissions)) {
                abort(403, 'Unauthorized.');
            }
        } else {
            if ($this->auth->guest() || !$request->user()->can($this->action)) {
                abort(403, 'Unauthorized.');
            }
        }

        return $next($request);
    }
}
