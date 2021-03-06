<?php

declare(strict_types=1);

namespace Tests\fixtures\Middleware;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Tests\fixtures\TestDependencies\Bar;
use Tests\fixtures\TestDependencies\Foo;

class MiddlewareWithDependencies extends Middleware
{
    
    private Foo $foo;
    private Bar $bar;
    
    public function __construct(Foo $foo, Bar $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $request->body = $this->foo->foo.$this->bar->bar;
        
        return $next($request);
    }
    
}