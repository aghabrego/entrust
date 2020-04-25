<?php

namespace Weirdo\Entrust\Traits;

use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;
use Weirdo\Entrust\Models\EntrustModule;

trait EntrustHelperTrait
{
    private static $_expression = 'App\\Http\\Controllers\\';

    /**
     * @return string
     */
    public static function getCurrentRouteAction()
    {
        return Route::getFacadeRoot()->currentRouteAction();
    }

    /**
     * Obtiene la ruta actual y retorna el controlador
     * @return string
     */
    public static function getActionName()
    {
        return self::formatoNombreControlador(
            self::getCurrentRouteAction()
        );
    }

    /**
     * Obtiene el nombre del controlador
     * @param string $action_name
     * @return string
     */
    public static function formatoNombreControlador($action_name)
    {
        $controller = explode('@', $action_name);
        $name = explode('\\', str_replace(self::$_expression, '', $controller[0]));

        return $name;
    }

    /**
     * Obtiene el metodo actual en ejecucion
     * dentro de modulo
     * @return string
     */
    public static function getMethodInExecutionModulo()
    {
        $current_route = self::getCurrentRouteAction();
        $controller = explode('@', $current_route);
        $action = explode('\\', str_replace(self::$_expression, '', $controller[1]));

        if (isset($action[0]) && count($action) == 1) {
            return $action[0];
        }

        return $action;
    }

    /**
     * @param string $controller
     * @param string $cacheKey
     * @return mixed Modules
     */
    public function getCachedModule($controller, $tagKey, $cacheKey)
    {
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags(Config::get($tagKey))
                ->remember($cacheKey, Config::get('cache.ttl'), function () use ($controller) {
                    $module = EntrustModule::query()->where('controller', $controller)->first();
                    return $module ? $module->id_name : null;
                });
        } else {
            $module = EntrustModule::query()->where('controller', $controller)->first();

            return $module ? $module->id_name : null;
        }
    }

    /**
     * Big block of caching functionality.
     * @param string $tagKey
     * @param string $cacheKey
     * @return mixed Modules
     */
    public function getCachedModules($tagKey, $cacheKey)
    {
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags(Config::get($tagKey))
                ->remember($cacheKey, Config::get('cache.ttl'), function () {
                    return $this->modules()->get();
                });
        } else {
            return $this->modules()->get();
        }
    }

    /**
     * Big block of caching functionality.
     * @param string $tagKey
     * @param string $cacheKey
     * @return mixed Roles
     */
    public function getCachedRoles($tagKey, $cacheKey)
    {
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags(Config::get($tagKey))
                ->remember($cacheKey, Config::get('cache.ttl'), function () {
                    return $this->roles()->get();
                });
        } else {
            return $this->roles()->get();
        }
    }

    /**
     * Big block of caching functionality.
     * @param string $tagKey
     * @param string $cacheKey
     * @return mixed Roles
     */
    public function getCachedPermissions($tagKey, $cacheKey)
    {
        if (Cache::getStore() instanceof TaggableStore) {
            return Cache::tags(Config::get($tagKey))
                ->remember($cacheKey, Config::get('cache.ttl'), function () {
                    return $this->perms()->get();
                });
        } else {
            return $this->perms()->get();
        }
    }

    /**
     * Checks if the user has a module by its name.
     * @param string|array $name
     * @param string $tagKey
     * @param mixed $cacheKey
     * @param bool $requireAll
     * @return bool
     */
    public function checkIfItHasModule($name, $tagKey, $cacheKey, $requireAll = false)
    {
        if (is_array($name)) {
            foreach ($name as $moduleController) {
                $hasModule = $this->checkIfItHasModule($moduleController, $tagKey, $cacheKey);
                if ($hasModule && !$requireAll) {
                    return true;
                } elseif (!$hasModule && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the modules were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the modules were found.
            // Return the value of $requireAll;
            return $requireAll;
        } else {
            foreach ($this->getCachedRoles($tagKey, $cacheKey) as $role) {
                // Validate against the Module table
                foreach ($role->cachedModules() as $module) {
                    if (str_is($name, $module->controller)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if the user has a role by its name.
     * @param string|array $name
     * @param string $tagKey
     * @param mixed $cacheKey
     * @param bool $requireAll
     * @return bool
     */
    public function checkIfItHasRole($name, $tagKey, $cacheKey, $requireAll = false)
    {
        if (is_array($name)) {
            foreach ($name as $roleName) {
                $hasRole = $this->checkIfItHasRole($roleName, $tagKey, $cacheKey);
                if ($hasRole && !$requireAll) {
                    return true;
                } elseif (!$hasRole && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the roles were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the roles were found.
            // Return the value of $requireAll;
            return $requireAll;
        } else {
            foreach ($this->getCachedRoles($tagKey, $cacheKey) as $role) {
                if ($role->name === $name) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has a permission by its name.
     * @param string|array $permission
     * @param string $tagKey
     * @param mixed $cacheKey
     * @param bool $requireAll
     * @return bool
     */
    public function checkIfItHasCan($permission, $tagKey, $cacheKey, $requireAll = false)
    {
        if (is_array($permission)) {
            foreach ($permission as $permName) {
                $hasPerm = $this->checkIfItHasCan($permName, $tagKey, $cacheKey);
                if ($hasPerm && !$requireAll) {
                    return true;
                } elseif (!$hasPerm && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the perms were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the perms were found.
            // Return the value of $requireAll;
            return $requireAll;
        } else {
            foreach ($this->getCachedRoles($tagKey, $cacheKey) as $role) {
                // Validate against the Permission table
                foreach ($role->cachedPermissions() as $perm) {
                    if (str_is($permission, $perm->name)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if the role has a permission by its name.
     * @param string|array $name
     * @param bool $requireAll
     * @return bool
     */
    public function checkIfItHasCanRole($name, $tagKey, $cacheKey, $requireAll = false)
    {
        if (is_array($name)) {
            foreach ($name as $permissionName) {
                $hasPermission = $this->checkIfItHasCanRole($permissionName, $tagKey, $cacheKey);
                if ($hasPermission && !$requireAll) {
                    return true;
                } elseif (!$hasPermission && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the permissions were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the permissions were found.
            // Return the value of $requireAll;
            return $requireAll;
        } else {
            foreach ($this->getCachedPermissions($tagKey, $cacheKey) as $permission) {
                if ($permission->name == $name) {
                    return true;
                }
            }
        }

        return false;
    }
}
