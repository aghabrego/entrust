<?php

namespace Weirdo\Entrust\Middleware;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
use Closure;
use Weirdo\Helper\Helper;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Weirdo\Entrust\Traits\EntrustHelperTrait;
use Weirdo\Entrust\Models\EntrustModule as Module;

class EntrustPermission
{
    use EntrustHelperTrait, Helper;

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
     * @var Module
     */
    protected $module;

    /**
     * @var string
     */
    protected $controller;

    /**
     * Creates a new instance of the middleware.
     * @param Guard $auth
     * @param Module $module
     */
    public function __construct(Guard $auth, Module $module)
    {
        $this->auth = $auth;
        $this->module = $module;
        $controller = self::getActionName();
        $this->action = self::getMethodInExecutionModulo();
        $this->controller = is_array($controller) ? $this->findFirstMatch($controller, "/Controller/i") : $controller;
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
                // Roles del module
                $collectionRoles = $module->cachedRoles();
                $user = $request->user();
                $abilities = [];
                foreach ($collectionRoles as $role) {
                    // Permisos de los roles del module
                    $collectionPermissions = $role->cachedPermissions();
                    $permissions = $this->tearOffItems($collectionPermissions, 'name')->toArray();
                    $hasPermission = array_filter($permissions, fn($permission) => ($permission === $this->action && $user->can($permission)));
                    $abilities[$role->name] = ($user->hasRole($role->name) && count($hasPermission) > 0);
                }

                $isRoles = array_filter($abilities, fn($abi) => $abi === true);

                if (count($isRoles) <= 0) {
                    abort(403, 'Unauthorized.');
                }
            }

            if ($this->auth->guest()) {
                abort(403, 'Unauthorized.');
            }
        }

        return $next($request);
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
