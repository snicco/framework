<?php


    declare(strict_types = 1);


    namespace Tests\traits;

    use Contracts\ContainerAdapter;
    use Tests\stubs\Middleware\BarMiddleware;
    use Tests\stubs\Middleware\FooMiddleware;
    use Tests\stubs\TestRequest;
    use Tests\stubs\TestResponseEmitter;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ResponseFactory;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\ExceptionHandling\NullErrorHandler;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Factories\RouteActionFactory;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Middleware\Core\RouteRunner;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\Pipeline;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;

    trait TestHelpers
    {

        private function newRouteCollection()
        {

            $condition_factory = new ConditionFactory($this->conditions(), $this->container);
            $handler_factory = new RouteActionFactory([], $this->container);

            return new RouteCollection(
                $this->createRouteMatcher(),
                $condition_factory,
                $handler_factory

            );


        }

        private function createRoutes(\Closure $routes)
        {

            $this->routes = $this->newRouteCollection();

            $this->router = $this->newRouter();

            $routes();

            $this->router->loadRoutes();

        }

        private function newRouter()
        {

            return new Router($this->container, $this->routes);

        }

        private function newUrlGenerator() : UrlGenerator
        {
            return new UrlGenerator(new FastRouteUrlGenerator($this->routes));
        }

        private function newKernel(array $with_middleware = [])
        {

            $pipeline = new Pipeline($this->container);

            $this->container->instance(ErrorHandlerInterface::class, new NullErrorHandler());
            $this->container->instance(AbstractRouteCollection::class, $this->routes);
            $this->container->instance(ResponseFactory::class, $factory = $this->createResponseFactory());
            $this->container->instance(ContainerAdapter::class, $this->container);

            $router_runner = new RouteRunner($factory, new Pipeline($this->container));
            $router_runner->middlewareAliases([
                'foo' => FooMiddleware::class,
                'bar' => BarMiddleware::class,
            ]);

            foreach ($with_middleware as $group_name => $middlewares) {

                $router_runner->withMiddlewareGroup($group_name, $middlewares);

            }

            $this->container->instance(RouteRunner::class, $router_runner);


            return new HttpKernel($pipeline, new TestResponseEmitter());

        }

        private function runKernelAndGetOutput(IncomingRequest $request, HttpKernel $kernel = null)
        {

            $kernel = $kernel ?? $this->newKernel();

            ob_start();
            $kernel->run($request);

            return ob_get_clean();

        }

        private function runAndAssertOutput($expected, IncomingRequest $request)
        {

            $this->assertSame(
                $expected,
                $actual = $this->runKernelAndGetOutput($request),
                "Expected output:[{$expected}] Received:['{$actual}'].");

        }

        private function runAndAssertEmptyOutput(IncomingRequest $request) {

            $this->runAndAssertOutput('', $request);

        }

        private function conditions() : array {

            return array_merge(RoutingServiceProvider::CONDITION_TYPES , [

                'true'                 => \Tests\stubs\Conditions\TrueCondition::class,
                'false'                => \Tests\stubs\Conditions\FalseCondition::class,
                'maybe'                => \Tests\stubs\Conditions\MaybeCondition::class,
                'unique'               => \Tests\stubs\Conditions\UniqueCondition::class,
                'dependency_condition' => \Tests\stubs\Conditions\ConditionWithDependency::class,

            ]);


        }

        private function webRequest($method, $path) {

            return new IncomingWebRequest('wordpress.php', TestRequest::from($method, $path));

        }

    }