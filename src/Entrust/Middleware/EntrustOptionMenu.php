<?php

namespace Weirdo\Entrust\Middleware;

use Closure;
use Weirdo\Helper\Helper;
use Illuminate\Cache\TaggableStore;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Weirdo\Entrust\Traits\EntrustHelperTrait;
use Weirdo\Entrust\Models\EntrustOptionMenu as OptionMenu;

class EntrustOptionMenu
{
    use EntrustHelperTrait, Helper;

    /**
     * @var Guard
     */
    protected $auth;

    protected $action;

    protected $controller;

    /**
     * @var OptionMenu
     */
    protected $optionMenu;

    /**
     * Creates a new instance of the middleware.
     * @param Guard $auth
     * @param OptionMenu $optionMenu
     */
    public function __construct(Guard $auth, OptionMenu $optionMenu)
    {
        $this->auth = $auth;
        $this->optionMenu = $optionMenu;
        $controller = self::getActionName();
        $this->action = self::getMethodInExecutionModulo();
        $this->controller = is_array($controller) ? $this->findFirstMatch($controller, "/Controller/i") : $controller;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Check the role permission in the options menu
        $fullAction = "{$this->controller}@{$this->action}";
        $menuOption = null;
        if (Cache::getStore() instanceof TaggableStore) {
            $menuOption = Cache::tags(Config::get('entrust.options_menu_table'))
                ->remember(
                    'menuOption' . $fullAction,
                    Config::get('cache.ttl'),
                    function () use ($fullAction) {
                        return $this->getMenuOption($fullAction);
                    }
                );
        } else {
            $menuOption = $this->getMenuOption($fullAction);
        }

        if ($menuOption) {
            $roles = $menuOption->cachedRoles()
                ->pluck('name')
                ->toArray();

            if ($this->auth->guest() || !$request->user()->hasRole($roles)) {
                abort(403, 'Unauthorized.');
            }
        }

        return $next($request);
    }

    /**
     * @param string $fullAction
     * @return OptionMenu|null
     */
    private function getMenuOption($fullAction)
    {
        return $this->optionMenu
            ->where(
                "full_action",
                '=',
                $fullAction
            )
            ->first();
    }
}
