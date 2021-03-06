<?php

declare(strict_types=1);

namespace Tests\fixtures\Controllers\Web;

use Snicco\Http\Psr7\Request;
use Tests\fixtures\TestDependencies\Bar;
use Tests\fixtures\TestDependencies\Foo;

class TeamsController
{
    
    public function handle(Request $request, string $team, string $player)
    {
        return $team.':'.$player;
    }
    
    public function withDependencies(Request $request, Foo $foo, Bar $bar, string $team, string $player)
    {
        return $foo->foo.':'.$bar->bar.':'.$team.':'.$player;
    }
    
    public function withoutClassDeps(string $team, string $player)
    {
        return $team.':'.$player;
    }
    
    public function withConditions(Request $request, Foo $foo, Bar $bar, $baz, $biz)
    {
        return $foo->foo.':'.$bar->bar.':'.$baz.':'.$biz;
    }
    
}