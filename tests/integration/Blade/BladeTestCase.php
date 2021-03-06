<?php

declare(strict_types=1);

namespace Tests\integration\Blade;

use Tests\FrameworkTestCase;
use Snicco\Session\SessionServiceProvider;
use Snicco\Core\Blade\BladeServiceProvider;

class BladeTestCase extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->rmdir(BLADE_CACHE);
        $this->withSessionsEnabled();
    }
    
    protected function packageProviders() :array
    {
        return [
            BladeServiceProvider::class,
            SessionServiceProvider::class,
        ];
    }
    
}