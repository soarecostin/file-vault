<?php

namespace Soarecostin\FileVault;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Soarecostin\FileVault\Skeleton\SkeletonClass
 */
class FileVaultFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'file-vault';
    }
}
