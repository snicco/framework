<?php


	namespace Tests\integration;

	use BetterWpdb\DbFactory;
	use BetterWpdb\ExtendsIlluminate\MySqlSchemaBuilder;
	use BetterWpdb\WpConnection;
	use Codeception\TestCase\WPTestCase;
	use Illuminate\Database\Eloquent\ModelNotFoundException;
	use Illuminate\Database\Schema\Blueprint;
	use Tests\stubs\IntegrationTestErrorHandler;
	use Tests\stubs\Middleware\FooMiddleware;
	use Tests\stubs\TestApp;
	use WPEmerge\Requests\Request;
	use WPEmerge\Responses\ResponseService;
	use Mockery as m;

	class HttpKernelRouteModelBindingIntegrationTest extends WPTestCase {

		/**
		 * @var \WPEmerge\Kernels\HttpKernel
		 */
		private $kernel;

		/** @var \WPEmerge\Requests\Request */
		private $request;

		/** @var \WPEmerge\Responses\ResponseService */
		private $response_service;

		protected function setUp() : void {


			$GLOBALS['wp_test_case_without_transactions'] = true;

			parent::setUp();

			$this->request = m::mock( Request::class );
			$this->request->shouldReceive( 'getMethod' )->andReturn( 'GET' );
			$this->request->shouldReceive( 'withAttribute' )->andReturn( $this->request );
			$this->response_service = m::mock( ResponseService::class );

			$this->bootstrapTestApp();

			$this->kernel = TestApp::resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );

			$this->createInitialTables();


		}

		protected function tearDown() : void {

			m::close();
			parent::tearDown();

			TestApp::setApplication( null );

			$this->tearDownTables();

			$GLOBALS['wp_test_case_without_transactions'] = false ;
		}



		// /** @test */
		public function an_exception_gets_thrown_before_the_handler_executes_if_we_cant_retrieve_the_model () {

			$this->expectException(ModelNotFoundException::class);
			$this->expectExceptionMessage('No query results for model [Tests\stubs\Team].');

			TestApp::route()
			       ->get()
			       ->url( '/teams/{team}' )
			       ->handle( 'TeamsController@never');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/teams/3' );

			$this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertFalse( isset ($GLOBALS['TeamsControllerExecuted']) );


		}

		// /** @test */
		public function a_model_can_be_retrieved_by_custom_column_names_if_specified_in_the_route() {

			TestApp::route()
			       ->get()
			       ->url( '/teams/{team:name}' )
			       ->handle( 'TeamsController@handle');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/teams/1' );

			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'dortmund', $test_response->body() );

		}


		/** @test */
		public function a_handler_without_type_hinted_model_always_makes_the_model_condition_pass() {

			TestApp::route()
			       ->get()
			       ->url( '/teams/{team}' )
			       ->handle( 'TeamsController@noTypeHint');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/teams/1' );

			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( '1', $test_response->body() );

		}

		/** @test */
		public function a_model_gets_injected_into_the_handler_by_id_when_type_hinted() {

			TestApp::route()
			       ->get()
			       ->url( '/teams/{team}' )
			       ->handle( 'TeamsController@handle');


			$this->request->shouldReceive( 'getUrl' )->andReturn( 'https://wpemerge.test/teams/1' );


			$test_response = $this->kernel->handleRequest( $this->request, [ 'index' ] );

			$this->assertSame( 'dortmund', $test_response->body() );

		}



		private function bootstrapTestApp() {

			TestApp::make()->bootstrap( $this->config() , false );
			TestApp::container()[ WPEMERGE_REQUEST_KEY ]                  = $this->request;
			TestApp::container()[ WPEMERGE_RESPONSE_SERVICE_KEY ]         = $this->response_service;
			TestApp::container()[ WPEMERGE_EXCEPTIONS_ERROR_HANDLER_KEY ] = new IntegrationTestErrorHandler();

		}

		private function config() : array {

			return [

				'controller_namespaces' => [

					'web'   => 'Tests\stubs\Controllers\Web',
					'admin' => 'Tests\stubs\Controllers\Admin',
					'ajax'  => 'Tests\stubs\Controllers\Ajax',

				],

				'middleware' => [

					'foo'  => FooMiddleware::class,

				]

			];


		}

		private function createInitialTables() {

			global $wpdb;

			$db = DbFactory::make($wpdb);

			$connection = new WpConnection($db);

			$schema_builder = new MySqlSchemaBuilder($connection);

			if ( ! $schema_builder->hasTable('teams')) {

				$schema_builder->create('teams', function (Blueprint $table) {

					$table->id();
					$table->string('name')->unique();

				});

				$connection->table('teams')->insert([

					['name' => 'dortmund'],
					['name' => 'bayern'],

				]);

			}

		}

		private function tearDownTables() {

			global $wpdb;

			$db = DbFactory::make($wpdb);

			$connection = new WpConnection($db);

			$schema_builder = new MySqlSchemaBuilder($connection);

			if ( $schema_builder->hasTable('teams')) {

				$schema_builder->drop('teams');

			}

		}

	}