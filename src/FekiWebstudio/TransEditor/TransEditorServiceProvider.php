<?php
namespace FekiWebstudio\TransEditor;

use Illuminate\Support\ServiceProvider;

class TransEditorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public $defer = true;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        // Merge / publish configuration file
        $configFile = __DIR__ . '/../../../resources/config/transeditor.php';
        $this->mergeConfigFrom($configFile, 'transeditor');

        $this->publishes([
            $configFile => config_path('transeditor.php')
        ], 'config');

    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        return false;

        // TODO : DELETE THIS
        $this->app->singleton('transeditor', function ($app) {
            return new ThumberManager($app);
        });

        $this->app->bind('thumb', function()
        {
            return new ThumbnailManager();
        });
    }

    /**
     * Get the services provided by the provider
     * @return array
     */
    public function provides()
    {
        return [ 'transeditor' ];
    }
}
