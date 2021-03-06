<?php

declare(strict_types=1);

namespace Snicco\Listeners;

use Snicco\EventDispatcher\EventMapper;
use Snicco\Core\Events\EventObjects\AdminInit;
use Snicco\Core\Events\EventObjects\IncomingAdminRequest;

class CreateDynamicHooks
{
    
    private EventMapper $event_mapper;
    
    public function __construct(EventMapper $event_mapper)
    {
        $this->event_mapper = $event_mapper;
    }
    
    public function handle(AdminInit $event)
    {
        $hook = $event->hook;
        $this->event_mapper->mapFirst(
            "load-{$hook}",
            IncomingAdminRequest::class,
        );
    }
    
}