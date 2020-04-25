<?php

namespace Weirdo\Entrust;

/**
 * @license MIT
 * @package Weirdo\Entrust
 */
use Illuminate\Support\ServiceProvider;

class EntrustServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/config.php' => app()->basePath() . '/config/entrust.php',
        ]);

        // Register blade directives
        $this->bladeDirectives();
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerEntrust();

        $this->mergeConfig();
    }

    /**
     * Register the blade directives
     * @return void
     */
    private function bladeDirectives()
    {
        if (!class_exists('\Blade')) {
            return;
        }

        // Call to Entrust::hasRole
        \Blade::directive('role', function ($expression) {
            return "<?php if (\\Entrust::hasRole({$expression})) : ?>";
        });

        \Blade::directive('endrole', function ($expression) {
            return "<?php endif; // Entrust::hasRole ?>";
        });

        // Call to Entrust::can
        \Blade::directive('permission', function ($expression) {
            return "<?php if (\\Entrust::can({$expression})) : ?>";
        });

        \Blade::directive('endpermission', function ($expression) {
            return "<?php endif; // Entrust::can ?>";
        });

        // Call to Entrust::ability
        \Blade::directive('ability', function ($expression) {
            return "<?php if (\\Entrust::ability({$expression})) : ?>";
        });

        \Blade::directive('endability', function ($expression) {
            return "<?php endif; // Entrust::ability ?>";
        });
    }

    /**
     * Register the application bindings.
     * @return void
     */
    private function registerEntrust()
    {
        $this->app->bind('entrust', function ($app) {
            return new Entrust($app);
        });
        $this->app->alias('entrust', 'Weirdo\Entrust\Entrust');
    }

    /**
     * Merges user's and entrust's configs.
     * @return void
     */
    private function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php',
            'entrust'
        );
    }
}
