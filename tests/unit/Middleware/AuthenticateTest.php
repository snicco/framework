<?php


	namespace Tests\unit\Middleware;

	use Codeception\TestCase\WPTestCase;
	use Tests\TestRequest;
	use WPEmerge\Middleware\Authenticate;
	use WPEmerge\Responses\RedirectResponse;

	class AuthenticateTest extends WPTestCase {

		use WordpressFixtures;

		/**
		 * @var \WPEmerge\Middleware\Authenticate
		 */
		private $middleware;

		/**
		 * @var \Closure
		 */
		private $route_action;

		/**
		 * @var \Tests\TestRequest
		 */
		private $request;

		protected function setUp() : void {

			parent::setUp();

			$this->middleware   = new Authenticate();
			$this->route_action = function () {

				return 'foo';

			};
			$this->request      = TestRequest::from( 'GET', '/foo' );

		}

		/** @test */
		public function logged_in_users_can_access_the_route() {


			$calvin = $this->newAdmin();
			$this->login( $calvin );

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$this->assertSame( 'foo', $response );


		}


		/** @test */
		public function logged_out_users_cant_access_the_route() {

			$calvin = $this->newAdmin();
			$this->logout( $calvin );

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$this->assertInstanceOf( RedirectResponse::class, $response );


		}


		/** @test */
		public function by_default_users_get_redirected_to_wp_login_with_the_current_url_added_to_the_query_args () {

			$calvin = $this->newAdmin();
			$this->logout( $calvin );

			$response = $this->middleware->handle( $this->request, $this->route_action );

			$expected = wp_login_url($this->request->getUrl());

			$this->assertSame($expected, $response->getHeaderLine('Location'));


		}


		/** @test */
		public function users_can_be_redirected_to_a_custom_url () {

			$calvin = $this->newAdmin();
			$this->logout( $calvin );

			$response = $this->middleware->handle( $this->request, $this->route_action, 'https://example.com');

			$this->assertSame('https://example.com', $response->getHeaderLine('Location'));

		}

	}
