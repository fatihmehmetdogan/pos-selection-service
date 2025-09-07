<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Disable DebugClassLoader for PHP 7.4 compatibility
        if (class_exists('\\Symfony\\Component\\ErrorHandler\\DebugClassLoader')) {
            // Disable Symfony's DebugClassLoader which has issues with PHP 7.4
            if (method_exists('\\Symfony\\Component\\ErrorHandler\\DebugClassLoader', 'disable')) {
                \Symfony\Component\ErrorHandler\DebugClassLoader::disable();
            }
        }

        parent::boot();
    }
}
