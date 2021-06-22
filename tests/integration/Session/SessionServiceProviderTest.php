<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use BetterWpHooks\Contracts\Dispatcher;
    use BetterWpHooks\Dispatchers\WordpressDispatcher;
    use Slim\Csrf\Guard;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Auth\Controllers\AuthConfirmationController;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\Middleware\CsrfMiddleware;
    use WPEmerge\Session\EncryptedSession;
    use WPEmerge\Session\Middleware\ShareSessionWithView;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;

    class SessionServiceProviderTest extends IntegrationTest
    {

        /** @test */
        public function sessions_are_disabled_by_default()
        {

            $this->newTestApp([
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertNull(TestApp::config('session.enable'));

        }

        /** @test */
        public function sessions_can_be_enabled_in_the_config()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertTrue(TestApp::config('session.enabled'));

        }

        /** @test */
        public function nothing_is_bound_if_session_are_not_enabled()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => false,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $global = TestApp::config('middleware.groups.global');

            $this->assertNotContains(StartSessionMiddleware::class, $global);


        }

        /** @test */
        public function the_cookie_name_has_a_default_value()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame('wp_mvc_session', TestApp::config('session.cookie'));

        }

        /** @test */
        public function a_cookie_name_can_be_set()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                    'cookie' => 'test_cookie',
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame('test_cookie', TestApp::config('session.cookie'));

        }

        /** @test */
        public function the_session_table_has_a_default_value()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame('sessions', TestApp::config('session.table'));

        }

        /** @test */
        public function the_default_absolute_timeout_is_eight_hours () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame(28800, TestApp::config('session.lifetime'));

        }

        /** @test */
        public function the_rotation_timeout_is_half_of_the_absolute_timeout_by_default () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame(14400, TestApp::config('session.rotate'));

        }

        /** @test */
        public function the_default_lottery_chance_is_2_percent()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame([2, 100], TestApp::config('session.lottery'));


        }

        /** @test */
        public function the_session_cookie_path_is_root_by_default()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame('/', TestApp::config('session.path'));

        }

        /** @test */
        public function the_session_cookie_domain_is_null_by_default()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertNull(TestApp::config('session.domain', ''));

        }

        /** @test */
        public function the_session_cookie_is_set_to_only_secure()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertTrue(TestApp::config('session.secure'));

        }

        /** @test */
        public function the_session_cookie_is_set_to_http_only()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertTrue(TestApp::config('session.http_only'));

        }

        /** @test */
        public function same_site_is_set_to_lax()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame('lax', TestApp::config('session.same_site'));

        }

        /** @test */
        public function session_lifetime_is_set()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertSame(SessionManager::HOUR_IN_SEC * 8, TestApp::config('session.lifetime'));

        }

        /** @test */
        public function the_session_store_can_be_resolved()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $store = TestApp::resolve(Session::class);

            $this->assertInstanceOf(Session::class, $store);

        }

        /** @test */
        public function the_database_driver_is_used_by_default()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $driver = TestApp::resolve(\WPEmerge\Session\Contracts\SessionDriver::class);

            $this->assertInstanceOf(\WPEmerge\Session\Drivers\DatabaseSessionDriver::class, $driver);


        }

        /** @test */
        public function the_session_store_is_not_encrypted_by_default()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertFalse(TestApp::config('session.encrypt', ''));

        }

        /** @test */
        public function the_session_store_can_be_encrypted()
        {

            $this->newTestApp([
                'app_key' => 'base64:L0L/nXmGaFVpJ795dFRPt9c5eUrqIqkvJqkb98KbC10=',
                'session' => [
                    'enabled' => true,
                    'encrypt' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $driver = TestApp::resolve(Session::class);

            $this->assertInstanceOf(EncryptedSession::class, $driver);

        }

        /** @test */
        public function the_session_middleware_is_added_if_enabled()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertContains(StartSessionMiddleware::class, TestApp::config('middleware.groups.global'));

        }

        /** @test */
        public function the_csrf_middleware_is_bound()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertInstanceOf(CsrfMiddleware::class, TestApp::resolve(CsrfMiddleware::class));

        }

        /** @test */
        public function the_slim_guard_is_bound()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertInstanceOf(Guard::class, TestApp::resolve(Guard::class));

        }

        /** @test */
        public function the_session_can_be_resolved_as_an_alias()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertInstanceOf(Session::class, TestApp::session());

        }

        /** @test */
        public function a_csrf_field_can_be_created_as_an_alias()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $html = TestApp::csrfField();
            $this->assertStringContainsString('csrf', $html);
            $this->assertStringStartsWith('<input', $html);


        }

        /** @test */
        public function a_csrf_token_can_be_generated_as_ajax_token  () {

            $this->newTestApp([
                'session' => [
                    'enabled'=>true,
                ],
                'providers' => [
                    SessionServiceProvider::class
                ]
            ]);

            $token = TestApp::csrf()->asStringToken();

            $this->assertStringContainsString('csrf_name', $token);
            $this->assertStringContainsString('csrf_value', $token);

        }

        /** @test */
        public function confirm_auth_controller_can_be_resolved()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertInstanceOf(AuthConfirmationController::class, TestApp::resolve(AuthConfirmationController::class));

        }

        /** @test */
        public function middleware_aliases_are_bound()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $middleware_aliases = TestApp::config('middleware.aliases');

            $this->assertArrayHasKey('csrf', $middleware_aliases);

        }

        /** @test */
        public function global_middleware_is_bound () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $global_middleware = TestApp::config('middleware.groups.global');

            $this->assertContains(StartSessionMiddleware::class, $global_middleware);
            $this->assertContains(ShareSessionWithView::class, $global_middleware);

        }

        /** @test */
        public function the_session_manager_is_bound () {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
            ]);

            $this->assertInstanceOf(SessionManager::class, TestApp::resolve(SessionManager::class));
            $this->assertInstanceOf(SessionManager::class, TestApp::resolve(SessionManagerInterface::class));

        }



    }