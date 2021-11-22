<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Closure;
use Throwable;
use Snicco\EventDispatcher\Listener;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Exceptions\ListenerCreationException;

/**
 * @internal
 */
final class ParameterBasedListenerFactory implements ListenerFactory
{
    
    public function create($listener, Event $event) :Listener
    {
        if ($listener instanceof Closure) {
            return new Listener($listener);
        }
        try {
            $instance = new $listener[0];
        } catch (Throwable $e) {
            throw ListenerCreationException::becauseTheListenerWasNotInstantiatable(
                $listener,
                $event->getName(),
                $e
            );
        }
        
        return new Listener(fn(...$payload) => $instance->{$listener[1]}(...$payload));
    }
    
}