<?php


	namespace Tests\integration\Application;

	use Codeception\TestCase\WPTestCase;
	use Tests\stubs\TestApp;

	class StrictModeSettingsTest extends WPTestCase {


		protected function setUp() : void {

			parent::setUp();


		}

		protected function tearDown() : void {

			parent::tearDown();

			TestApp::setApplication(null );
		}

		/** @test */
		public function strict_mode_is_disabled_by_default () {

			TestApp::make()->bootstrap(TEST_CONFIG);

			$strict_mode_enabled = TestApp::resolve('strict.mode');

			$this->assertFalse($strict_mode_enabled);


		}

		/** @test */
		public function strict_mode_can_be_enabled_via_the_config() {

			TestApp::make()->bootstrap($this->config());

			$strict_mode_enabled = TestApp::resolve('strict.mode');

			$this->assertTrue($strict_mode_enabled);

		}


		public function config() : array {

			return array_merge(TEST_CONFIG, ['strict.mode' => true ]);

		}

	}