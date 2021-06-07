<?php


    declare(strict_types = 1);


    namespace Tests\integration\Session;

    use Illuminate\Support\Carbon;
    use Tests\integration\Blade\traits\InteractsWithWordpress;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\ExceptionHandling\Exceptions\NotFoundException;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\Exceptions\InvalidSignatureException;
    use WPEmerge\Session\SessionServiceProvider;

    class ConfirmAuthMagicLinkControllerTest extends IntegrationTest
    {

        use InteractsWithWordpress;

        private function createSignedUrl(int $user_id, $intended = '') : string
        {

            /** @var UrlGenerator $url */
            $url = TestApp::resolve(UrlGenerator::class);

            return $url->signedRoute('auth.confirm.magic-login',
                [
                    'user_id' => $user_id,
                    'query' => [
                        'intended' => $intended,
                    ],
                ]);

        }

        private function newApp()
        {

            $this->newTestApp([
                'session' => [
                    'enabled' => true,
                    'driver' => 'array',
                ],
                'providers' => [
                    SessionServiceProvider::class,
                ],
                'exception_handling' => [
                    'enable' => false,
                ],
            ]);
        }

        /** @test */
        public function the_route_cant_be_accessed_without_valid_signature()
        {

            $this->expectException(InvalidSignatureException::class);

            $this->newApp();

            $this->withoutExceptionHandling();

            $this->registerRoutes();
            $url = TestApp::routeUrl('auth.confirm.magic-login', ['user_id' => 1]);


            $this->runKernel(TestRequest::fromFullUrl('GET', $url));


        }

        /** @test */
        public function a_404_exception_is_created_for_user_ids_that_dont_exist()
        {

            $this->expectException(NotFoundException::class);

            $this->newApp();
            $this->withoutExceptionHandling();

            $this->registerRoutes();
            $url = $this->createSignedUrl(999);

            $this->runKernel(TestRequest::fromFullUrl('GET', $url));

        }

        /** @test */
        public function users_get_redirected_to_the_intended_url_from_the_query_string()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, 'https://foobar.com?bar=baz');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', 'https://foobar.com?bar=baz');
            HeaderStack::assertHasStatusCode(302);

            $this->logout($calvin);

        }

        /** @test */
        public function a_user_gets_redirected_to_the_intended_url_from_the_session_if_not_present_in_query_string()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            TestApp::session()->setIntendedUrl('https://intended-url.com');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', 'https://intended-url.com');
            HeaderStack::assertHasStatusCode(302);

            $this->logout($calvin);

        }

        /** @test */
        public function a_user_gets_redirected_to_the_admin_dashboard_if_no_intended_url_can_be_generated()
        {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->logout($calvin);

        }

        /** @test */
        public function the_auth_confirm_token_gets_saved_to_the_session () {

            $calvin = $this->newAdmin();
            $this->login($calvin);
            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertSame(
                Carbon::now()->addMinutes(180)->getTimestamp(),
                TestApp::session()->get('auth.confirm.until')
            );

            $this->logout($calvin);

        }

        /** @test */
        public function a_user_that_is_not_logged_in_gets_logged_in () {

            $calvin = $this->newAdmin();

            $this->assertUserLoggedOut();

            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertUserLoggedIn($calvin->ID);

            $this->logout($calvin);


        }

        /** @test */
        public function the_current_session_is_migrated()
        {

            $calvin = $this->newAdmin();

            $this->newApp();

            $session = TestApp::session();
            $id_old  = $session->getId();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            $request = TestRequest::fromFullUrl('GET', $url)
                                  ->withAddedHeader('Cookie', 'wp_mvc_session='.$id_old);


            $this->seeKernelOutput('', $request);
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertNotSame($id_old, $session->getId());

            $this->logout();

        }

        /** @test */
        public function the_wp_auth_cookie_is_set () {

            $calvin = $this->newAdmin();

            $this->newApp();

            $this->registerRoutes();
            $url = $this->createSignedUrl($calvin->ID, '');

            add_action('set_auth_cookie', function () {
                $GLOBALS['test']['auth_cookie'] = true;
            });

            $this->seeKernelOutput('', TestRequest::fromFullUrl('GET', $url));
            HeaderStack::assertHas('Location', WP::adminUrl());
            HeaderStack::assertHasStatusCode(302);

            $this->assertTrue($GLOBALS['test']['auth_cookie']);

            $this->logout();

        }


    }
