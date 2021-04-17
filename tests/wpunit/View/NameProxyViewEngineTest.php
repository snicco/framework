<?php

namespace WPEmergeTests\View;

use Codeception\TestCase\WPTestCase;
use Mockery;
use SniccoAdapter\BaseContainerAdapter;
use WPEmerge\Application\Application;
use WPEmerge\View\NameProxyViewEngine;

/**
 * @coversDefaultClass \WPEmerge\View\NameProxyViewEngine
 */
class NameProxyViewEngineTest extends WPTestCase {
	public $container;

	public $app;

	public function setUp() :void  {
		parent::setUp();

		$this->container = new BaseContainerAdapter();
		$this->app = new Application( $this->container );
		$this->app->bootstrap( [], false );
	}

	public function tearDown() :void  {

		parent::setUp();



	}

	/**
	 * @covers ::__construct
	 * @covers ::getBindings
	 */
	public function testConstruct_Bindings_Accepted() {

		$expected = ['.foo' => 'foo', '.bar' => 'bar'];

		$subject = new NameProxyViewEngine( $this->app, $expected );

		$this->assertEquals( $expected, $subject->getBindings() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::getDefaultBinding
	 */
	public function testConstruct_Default_Accepted() {
		$expected = 'foo';

		$subject = new NameProxyViewEngine( $this->app, [], $expected );

		$this->assertEquals( $expected, $subject->getDefaultBinding() );
	}

	/**
	 * @covers ::__construct
	 * @covers ::getDefaultBinding
	 */
	public function testConstruct_EmptyDefault_Ignored() {
		$subject = new NameProxyViewEngine( $this->app, [], '' );

		$this->assertNotEquals( '', $subject->getDefaultBinding() );
	}

	/**
	 * @covers ::getBindingForFile
	 */
	public function testGetBindingForFile() {
		$subject = new NameProxyViewEngine(
			$this->app,
			[
				'.blade.php' => 'blade',
				'.twig.php' => 'twig',
			],
			'default'
		);

		$this->assertEquals( 'blade', $subject->getBindingForFile( 'test.blade.php' ) );
		$this->assertEquals( 'twig', $subject->getBindingForFile( 'test.twig.php' ) );
		$this->assertEquals( 'default', $subject->getBindingForFile( 'test.php' ) );
	}

	/**
	 * @covers ::exists
	 */
	public function testExists() {

		$view = 'foo';
		$this->container['engine_mockup'] = function () use ( $view ) {
			$mock = Mockery::mock();

			$mock->shouldReceive( 'exists' )
				->with( $view )
				->andReturn( true )
				->ordered();

			return $mock;
		};

		$subject = new NameProxyViewEngine( $this->app, [], 'engine_mockup' );

		$this->assertTrue( $subject->exists( $view ) );


	}

	/**
	 * @covers ::canonical
	 */
	public function testCanonical() {
		$view = 'foo';
		$expected = 'foo.php';

		$this->container['engine_mockup'] = function() use ( $view, $expected ) {
			$mock = Mockery::mock();

			$mock->shouldReceive( 'canonical' )
				->with( $view )
				->andReturn( $expected )
				->ordered();

			return $mock;
		};

		$subject = new NameProxyViewEngine( $this->app, [], 'engine_mockup' );

		$this->assertEquals( $expected, $subject->canonical( $view ) );
	}

	/**
	 * @covers ::make
	 */
	public function testMake() {

		$view = 'file.php';
		$result = 'foobar';

		$this->container['engine_mockup'] = function() use ( $view, $result ) {
			$mock = Mockery::mock();

			$mock->shouldReceive( 'exists' )
				->with( $view )
				->andReturn( true );

			$mock->shouldReceive( 'make' )
				->with( [$view] )
				->andReturn( $result );

			return $mock;
		};

		$subject = new NameProxyViewEngine( $this->app, [], 'engine_mockup' );

		$this->assertEquals( $result, $subject->make( [$view] ) );
	}

	/**
	 * @covers ::make
	 */
	public function testMake_NoView_EmptyString() {


		$this->expectExceptionMessage('View not found');

		$view = '';

		$this->container['engine_mockup'] = function() use ( $view ) {
			$mock = Mockery::mock();

			$mock->shouldReceive( 'exists' )
				->with( $view )
				->andReturn( false );

			return $mock;
		};

		$subject = new NameProxyViewEngine( $this->app, [], 'engine_mockup' );

		$subject->make( [$view] );
	}
}
