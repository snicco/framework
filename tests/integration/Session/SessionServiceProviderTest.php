<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Slim\Csrf\Guard;
    use Tests\stubs\TestApp;
    use Tests\TestCase;
    use WPEmerge\Session\Contracts\SessionDriver;
    use WPEmerge\Session\Contracts\SessionManagerInterface;
    use WPEmerge\Session\CsrfField;
    use WPEmerge\Session\Drivers\DatabaseSessionDriver;
    use WPEmerge\Session\Middleware\CsrfMiddleware;
    use WPEmerge\Session\EncryptedSession;
    use WPEmerge\Session\Middleware\ShareSessionWithView;
    use WPEmerge\Session\SessionManager;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\Middleware\StartSessionMiddleware;
    use WPEmerge\View\GlobalContext;

    class SessionServiceProviderTest extends TestCase
    {

        protected $defer_boot = true;

        public function packageProviders() : array
        {
            return [
                SessionServiceProvider::class
            ];
        }

        /** @test */
        public function sessions_are_disabled_by_default()
        {

            $this->boot();

            $this->assertNull(TestApp::config('session.enable'));

        }

        /** @test */
        public function sessions_can_be_enabled_in_the_config()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertTrue(TestApp::config('session.enabled'));

        }

        /** @test */
        public function nothing_is_bound_if_session_are_not_enabled()
        {

            $this->boot();

            $global = TestApp::config('middleware.groups.global');

            $this->assertNotContains(StartSessionMiddleware::class, $global);


        }

        /** @test */
        public function the_cookie_name_has_a_default_value()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertSame('wp_mvc_session', TestApp::config('session.cookie'));

        }

        /** @test */
        public function a_cookie_name_can_be_set()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
                'session.cookie' => 'test_cookie'
            ])->boot();


            $this->assertSame('test_cookie', TestApp::config('session.cookie'));

        }

        /** @test */
        public function the_session_table_has_a_default_value()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertSame('sessions', TestApp::config('session.table'));

        }

        /** @test */
        public function the_default_absolute_timeout_is_eight_hours () {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertSame(28800, TestApp::config('session.lifetime'));

        }

        /** @test */
        public function the_rotation_timeout_is_half_of_the_absolute_timeout_by_default () {

            $this->withAddedConfig(['session.enabled' => true])->boot();


            $this->assertSame(14400, TestApp::config('session.rotate'));

        }

        /** @test */
        public function the_default_lottery_chance_is_2_percent()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();


            $this->assertSame([2, 100], TestApp::config('session.lottery'));


        }

        /** @test */
        public function the_session_cookie_path_is_root_by_default()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertSame('/', TestApp::config('session.path'));

        }

        /** @test */
        public function the_session_cookie_domain_is_null_by_default()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertNull(TestApp::config('session.domain', ''));

        }

        /** @test */
        public function the_session_cookie_is_set_to_only_secure()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();


            $this->assertTrue(TestApp::config('session.secure'));

        }

        /** @test */
        public function the_session_cookie_is_set_to_http_only()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();


            $this->assertTrue(TestApp::config('session.http_only'));

        }

        /** @test */
        public function same_site_is_set_to_lax()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertSame('lax', TestApp::config('session.same_site'));

        }

        /** @test */
        public function session_lifetime_is_set()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $this->assertSame(SessionManager::HOUR_IN_SEC * 8, TestApp::config('session.lifetime'));

        }

        /** @test */
        public function the_session_store_can_be_resolved()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $store = TestApp::resolve(Session::class);

            $this->assertInstanceOf(Session::class, $store);

        }

        /** @test */
        public function the_database_driver_is_used_by_default()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();

            $driver = TestApp::resolve(SessionDriver::class);

            $this->assertInstanceOf(DatabaseSessionDriver::class, $driver);


        }

        /** @test */
        public function the_session_store_is_not_encrypted_by_default()
        {

            $this->withAddedConfig(['session.enabled' => true])->boot();


            $this->assertFalse(TestApp::config('session.encrypt', ''));

        }

        /** @test */
        public function the_session_store_can_be_encrypted()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
                'session.encrypt' => true,
            ])->boot();


            $driver = TestApp::resolve(Session::class);

            $this->assertInstanceOf(EncryptedSession::class, $driver);

        }

        /** @test */
        public function the_session_middleware_is_added_if_enabled()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $this->assertContains(StartSessionMiddleware::class, TestApp::config('middleware.groups.global'));

        }

        /** @test */
        public function the_csrf_middleware_is_bound()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $this->assertInstanceOf(CsrfMiddleware::class, TestApp::resolve(CsrfMiddleware::class));

        }

        /** @test */
        public function the_slim_guard_is_bound()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $this->assertInstanceOf(Guard::class, TestApp::resolve(Guard::class));

        }

        /** @test */
        public function the_session_can_be_resolved_as_an_alias()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $this->assertInstanceOf(Session::class, TestApp::session());

        }

        /** @test */
        public function a_csrf_field_can_be_created_as_an_alias()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $html = TestApp::csrfField();
            $this->assertStringContainsString('csrf', $html);
            $this->assertStringStartsWith('<input', $html);


        }

        /** @test */
        public function a_csrf_token_can_be_generated_as_ajax_token  () {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $token = TestApp::csrf()->asStringToken();

            $this->assertStringContainsString('csrf_name', $token);
            $this->assertStringContainsString('csrf_value', $token);

        }

        /** @test */
        public function middleware_aliases_are_bound()
        {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $middleware_aliases = TestApp::config('middleware.aliases');

            $this->assertArrayHasKey('csrf', $middleware_aliases);

        }

        /** @test */
        public function global_middleware_is_bound () {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $global_middleware = TestApp::config('middleware.groups.global');

            $this->assertContains(StartSessionMiddleware::class, $global_middleware);
            $this->assertContains(ShareSessionWithView::class, $global_middleware);

        }

        /** @test */
        public function the_session_manager_is_bound () {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $this->assertInstanceOf(SessionManager::class, TestApp::resolve(SessionManager::class));
            $this->assertInstanceOf(SessionManager::class, TestApp::resolve(SessionManagerInterface::class));

        }

        /** @test */
        public function the_csrf_field_is_bound_to_the_global_view_context () {

            $this->withAddedConfig([
                'session.enabled' => true,
            ])->boot();

            $context = TestApp::resolve(GlobalContext::class)->get();

            $this->assertInstanceOf(CsrfField::class, $context['csrf']);

        }


    }