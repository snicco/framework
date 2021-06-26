<?php


    declare(strict_types = 1);


    namespace Tests\integration\Auth;

    use Tests\helpers\HashesSessionIds;
    use Tests\helpers\TravelsTime;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Auth\Authenticators\PasswordAuthenticator;
    use WPEmerge\Auth\AuthServiceProvider;
    use WPEmerge\Auth\Contracts\Authenticator;
    use WPEmerge\Auth\Events\Login;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Auth\Responses\SuccessfulLoginResponse;
    use WPEmerge\Auth\Traits\ResolvesUser;
    use WPEmerge\Http\Delegate;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\Psr7\Response;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;

    class AuthSessionControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;
        use HashesSessionIds;
        use TravelsTime;

        private $config = [
            'session' => [
                'enabled' => true,
                'driver' => 'array',
                'lifetime' => 3600
            ],
            'providers' => [
                SessionServiceProvider::class,
                AuthServiceProvider::class,
            ],
            'auth' => [

                'confirmation' => [
                    'duration' => 10
                ],

                'remember' => [
                    'enabled' => false,
                ]
            ]
        ];

        /**
         * @var TestRequest
         */
        private $request;

        protected function afterSetup()
        {

           $this->request = TestRequest::from('GET', '/auth/login');
        }

        protected function postLoginRequest(array $body) {

            TestApp::session()->put('csrf', $csrf = ['csrf_secret_name' => 'csrf_secret_value']);

            $body = array_merge($body, [
                'csrf_name' => Arr::firstKey($csrf),
                'csrf_value' => Arr::firstEl($csrf),
            ]);

            return TestRequest::from('POST', '/auth/login')->withParsedBody($body);

        }

        /** @test */
        public function the_login_screen_can_be_rendered () {

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $this->assertOutputContains('Login', $this->request);
            HeaderStack::assertHasStatusCode(200);


        }

        /** @test */
        public function the_login_route_can_not_be_accessed_while_logged_in () {

            $this->login($calvin = $this->newAdmin());

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $this->assertOutput('', $this->request);
            HeaderStack::assertHasStatusCode(302);

            $this->logout($calvin);

        }

        /** @test */
        public function reauth_works_when_present_in_the_query_parameter () {

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $request = $this->request->withQueryParams(['reauth' => 1]);

            $GLOBALS['test']['auth_cookies_cleared'] = false;

            add_action('clear_auth_cookie', function () {
                $GLOBALS['test']['auth_cookies_cleared'] = true;
            });

            $this->assertOutputContains('Login', $request);
            HeaderStack::assertHasStatusCode(200);
            $this->assertTrue($GLOBALS['test']['auth_cookies_cleared']);

        }

        /** @test */
        public function the_redirect_to_url_is_saved_to_the_session () {

            $this->newTestApp($this->config);
            $this->loadRoutes();

            $request = $this->request->withQueryParams(['redirect_to' => '/foo/bar']);

            $this->assertOutputContains('Login', $request);
            HeaderStack::assertHasStatusCode(200);

            $this->assertSame('/foo/bar', TestApp::session()->getIntendedUrl());


        }

        /** @test */
        public function a_user_can_log_in () {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();
            $this->assertUserLoggedOut();


            $request = $this->postLoginRequest([
                'pwd' => 'password',
                'log' => $calvin->user_login
            ]);

            $this->assertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);
            $this->assertUserLoggedIn($calvin);

            $this->logout($calvin);

        }

        /** @test */
        public function a_wrong_password_throws_a_generic_exception () {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();
            $this->assertUserLoggedOut();


            $request = $this->postLoginRequest([
                'pwd' => 'wrong_password',
                'log' => $calvin->user_login
            ]);

            $this->expectException(FailedAuthenticationException::class);
            $this->expectExceptionMessage('Your password or username is not correct.');

            $this->runKernel($request);

        }

        /** @test */
        public function a_wrong_user_login_throws_a_generic_exception () {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();
            $this->assertUserLoggedOut();

            $request = $this->postLoginRequest([
                'pwd' => 'password',
                'log' => 'wrong'
            ]);

            $this->expectException(FailedAuthenticationException::class);
            $this->expectExceptionMessage('Your password or username is not correct.');

            $this->runKernel($request);

        }

        /** @test */
        public function the_session_is_updated_on_login () {

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();

            ApplicationEvent::fake([Login::class]);

            $session = TestApp::session();
            $array_handler = $session->getDriver();
            $array_handler->write($this->hashedSessionId(), serialize(['foo' => 'bar']));

            $request = $this->postLoginRequest([
                'pwd' => 'password',
                'log' => $calvin->user_login
            ]);
            $request = $request->withAddedHeader('Cookie', 'wp_mvc_session='.$this->testSessionId() );

            $this->assertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);
            $this->assertUserLoggedIn($calvin);

            // Session regenerated
            $this->assertNotSame($session->getId(), $this->getSessionId());

            // Auth confirmed
            $this->travelIntoFuture(9);
            $this->assertTrue($session->hasValidAuthConfirmToken());
            $this->travelIntoFuture(1);
            $this->assertFalse($session->hasValidAuthConfirmToken());

            // User id
            $this->assertSame($calvin->ID, $session->userId());

            // remember me preference
            $this->assertFalse($session->hasRememberMeToken());

            ApplicationEvent::assertDispatched(function (Login $login ) use ($calvin){

                return $login->user->ID === $calvin->ID && $login->remember === false;

            });

            $this->logout($calvin);

        }

        /** @test */
        public function a_user_can_be_remembered_if_he_chooses_too () {

            Arr::set($this->config, 'auth.remember.enabled', true );
            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = $this->postLoginRequest([
                'pwd' => 'password',
                'log' => $calvin->user_login,
                'remember_me' =>'1'
            ]);

            $this->assertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);

            // remember me preference
            $this->assertTrue(TestApp::session()->hasRememberMeToken());

        }

        /** @test */
        public function a_user_will_not_be_remembered_if_disabled_in_the_config () {

            Arr::set($this->config, 'auth.remember.enabled', false );
            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();

            $request = $this->postLoginRequest([
                'pwd' => 'password',
                'log' => $calvin->user_login,
                'remember_me' =>'1'
            ]);

            $this->assertOutput('', $request);
            HeaderStack::assertHasStatusCode(302);

            // remember me preference
            $this->assertFalse(TestApp::session()->hasRememberMeToken());

        }

        /** @test */
        public function if_its_an_interim_login_the_user_is_not_redirected () {


            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();
            $this->assertUserLoggedOut();

            $request = $this->postLoginRequest([
                'pwd' => 'password',
                'log' => $calvin->user_login,
                'is_interim_login' =>'1'
            ]);

            $this->runKernel($request);
            HeaderStack::assertHasStatusCode(200);
            $this->assertUserLoggedIn($calvin);

            $this->assertTrue(TestApp::session()->has('interim_login_success'));

            $this->logout($calvin);

        }

        /** @test */
        public function the_user_can_be_logged_in_through_multiple_authenticators () {

            Arr::set($this->config, 'auth.through', [
                CustomAuthenticator::class,
                PasswordAuthenticator::class,
            ]);

            $this->newTestApp($this->config);
            $this->loadRoutes();
            $calvin = $this->newAdmin();
            $this->assertUserLoggedOut();

            $request = $this->postLoginRequest([
                'pwd' => 'bogus',
                'log' => $calvin->user_login,
                'allow_login_for_id' => $calvin->ID
            ]);

            $this->runKernel($request);
            HeaderStack::assertHasStatusCode(302);
            $this->assertUserLoggedIn($calvin);

            $this->logout($calvin);

            $request = $this->postLoginRequest([
                'pwd' => 'password',
                'log' => $calvin->user_login,
            ]);

            $this->runKernel($request);
            HeaderStack::assertHasStatusCode(302);
            $this->assertUserLoggedIn($calvin);

            $this->logout($calvin);



        }

    }

    class CustomAuthenticator extends Authenticator {

        use ResolvesUser;

        public function attempt(Request $request, $next) : Response
        {
            if ( $request->has('allow_login_for_id') ) {

                $user = $this->getUserById($request->input('allow_login_for_id'));

                return $this->login( $user, false);
            }

            return $next($request);


        }

    }