<?php

namespace Weirdo\Entrust\Middleware;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
use Closure;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Weirdo\Entrust\Traits\EntrustHelperTrait;
use Weirdo\Entrust\Models\EntrustModule as Module;

class EntrustModule
{
    use EntrustHelperTrait;

    const DELIMITER = '|';

    /**
     * @var Guard
     */
    protected $auth;

    /**
     * @var Module
     */
    protected $module;

    /**
     * @var string
     */
    protected $controller;

    /**
     * Creates a new instance of the middleware.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth, Module $module)
    {
        $this->auth = $auth;
        $this->module = $module;
        $controller = self::getActionName();

        if (is_array($controller) && isset($controller[0])) {
            $this->controller = $controller[0];
        }
    }

    /**
     * Handle an incoming request.
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     * @param $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles = null)
    {
        if (is_null($roles)) {
            $controller = $this->controller;
            $module = null;
            if (Cache::getStore() instanceof TaggableStore) {
                $module = Cache::tags(Config::get('entrust.modules_table'))
                    ->remember(
                        'module_' . $controller,
                        Config::get('cache.ttl'),
                        function () use ($controller) {
                            return $this->getModule($controller);
                        }
                    );
            } else {
                $module = $this->getModule($controller);
            }

            if ($module) {
                $roles = $module->cachedRoles()
                    ->pluck('name')
                    ->toArray();

                $this->checkRoles($request, $roles);
            }
        } else {
            $this->checkRoles($request, $roles);
        }

        return $next($request);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param array|string $roles
     * @return void
     */
    private function checkRoles($request, $roles)
    {
        if (!is_array($roles)) {
            $roles = explode(self::DELIMITER, $roles);
        }

        if ($this->auth->guest() || !$request->user()->hasRole($roles)) {
            abort(403, 'Unauthorized.');
        }
    }

    /**
     * @param  string $controller
     * @return void
     */
    private function getModule($controller)
    {
        return $this->module
            ->query()
            ->withController($controller)
            ->first();
    }
}
