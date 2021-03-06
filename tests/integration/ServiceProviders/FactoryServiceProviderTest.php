<?php

declare(strict_types=1);

namespace Tests\integration\ServiceProviders;

use Tests\stubs\TestApp;
use Tests\FrameworkTestCase;
use Snicco\Routing\ControllerAction;
use Snicco\Factories\RouteActionFactory;
use Snicco\Factories\RouteConditionFactory;
use Tests\fixtures\ViewComposers\FooComposer;
use Snicco\Core\View\DependencyInjectionViewComposerFactory;

class FactoryServiceProviderTest extends FrameworkTestCase
{
    
    protected function setUp() :void
    {
        $this->afterApplicationCreated(function () {
            $this->bootApp();
        });
        parent::setUp();
    }
    
    /** @test */
    public function the_factory_service_provider_is_set_up_correctly()
    {
        $this->assertInstanceOf(
            RouteActionFactory::class,
            TestApp::resolve(RouteActionFactory::class)
        );
        $this->assertInstanceOf(
            DependencyInjectionViewComposerFactory::class,
            TestApp::resolve(DependencyInjectionViewComposerFactory::class)
        );
        $this->assertInstanceOf(
            RouteConditionFactory::class,
            TestApp::resolve(RouteConditionFactory::class)
        );
    }
    
    /** @test */
    public function the_controller_namespace_can_be_configured_correctly()
    {
        /** @var RouteActionFactory $factory */
        $factory = TestApp::resolve(RouteActionFactory::class);
        
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->create('AdminController@handle')
        );
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->create('WebController@handle')
        );
        $this->assertInstanceOf(
            ControllerAction::class,
            $factory->create('AjaxController@handle')
        );
    }
    
    /** @test */
    public function the_view_composer_namespace_can_be_configured_correctly()
    {
        /** @var DependencyInjectionViewComposerFactory $factory */
        $factory = TestApp::resolve(DependencyInjectionViewComposerFactory::class);
        
        $composer = $factory->create('FooComposer');
        
        $this->assertInstanceOf(FooComposer::class, $composer);
    }
    
}