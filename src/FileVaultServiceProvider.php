<?php

namespace SoareCostin\FileVault;

use Illuminate\Support\ServiceProvider;

class FileVaultServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('file-vault.php'),
            ], 'file-vault-config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'file-vault');

        // Register the main class to use with the facade
        $this->app->singleton('file-vault', function () {
            return new FileVault;
        });
    }
}
