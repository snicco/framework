<?php

declare(strict_types=1);

namespace Tests\fixtures\ViewComposers;

use Snicco\View\Contracts\ViewComposer;
use Snicco\View\Contracts\ViewInterface;
use Tests\fixtures\TestDependencies\Bar;

class FooComposer implements ViewComposer
{
    
    private Bar $bar;
    
    public function __construct(Bar $bar)
    {
        $this->bar = $bar;
    }
    
    public function compose(ViewInterface $view) :void
    {
        $view->with(['foo' => $this->bar->bar]);
    }
    
}