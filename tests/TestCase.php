<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SoareCostin\FileVault\Facades\FileVault;
use SoareCostin\FileVault\FileVaultServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            FileVaultServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [
            'FileVault' => FileVault::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Set the storage local filesystem
        $app['config']->set('filesystems.disks.local.driver', 'local');
        $app['config']->set('filesystems.disks.local.root', realpath(__DIR__.'/../storage/app'));
        $app['config']->set('filesystems.default', 'local');

        // Generate and set a random encryption key
        $app['config']->set('file-vault.key', $this->generateRandomKey());
    }

    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
            \SoareCostin\FileVault\FileVault::generateKey()
        );
    }
}
