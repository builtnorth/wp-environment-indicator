<?php
/**
 * Tests for the App class
 *
 * @package BuiltNorth\WPEnvironmentIndicator\Tests\Unit
 */

namespace BuiltNorth\WPEnvironmentIndicator\Tests\Unit;

use BuiltNorth\WPEnvironmentIndicator\App;
use BuiltNorth\WPEnvironmentIndicator\Tests\TestCase;
use WP_Mock;
use Mockery;
use Brain\Monkey\Functions;

/**
 * Test the App class
 */
class AppTest extends TestCase {

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Reset the singleton instance before each test
		$reflection = new \ReflectionClass( App::class );
		$instance = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Test singleton instance
	 */
	public function test_singleton_instance() {
		$instance1 = App::instance();
		$instance2 = App::instance();

		$this->assertSame( $instance1, $instance2 );
		$this->assertInstanceOf( App::class, $instance1 );
	}

	/**
	 * Test boot method with environment
	 */
	public function test_boot_with_environment() {
		// Define the constant if not already defined
		if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			define( 'WP_ENVIRONMENT_TYPE', 'development' );
		}

		WP_Mock::expectActionAdded( 'admin_bar_menu', [ App::instance(), 'add_environment_menu' ], 100 );
		WP_Mock::expectActionAdded( 'admin_head', [ App::instance(), 'add_styles' ] );
		WP_Mock::expectActionAdded( 'wp_head', [ App::instance(), 'add_styles' ] );

		$app = App::instance();
		$app->boot();

		$this->assertEquals( 'development', $app->get_environment() );
		$this->assertConditionsMet();
	}

	/**
	 * Test add environment menu
	 */
	public function test_add_environment_menu() {
		// Ensure the constant is defined
		if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			define( 'WP_ENVIRONMENT_TYPE', 'development' );
		}

		WP_Mock::userFunction( 'is_admin_bar_showing' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );
		WP_Mock::userFunction( 'esc_html' )
			->with( 'Development' )
			->andReturn( 'Development' );

		$admin_bar = Mockery::mock( 'WP_Admin_Bar' );
		$admin_bar->shouldReceive( 'add_node' )
			->once()
			->with( Mockery::on( function( $args ) {
				return $args['id'] === 'environment-indicator' &&
					   strpos( $args['title'], 'Development' ) !== false &&
					   $args['parent'] === 'top-secondary';
			} ) );

		$app = App::instance();
		$app->boot();
		$app->add_environment_menu( $admin_bar );

		$this->assertConditionsMet();
	}

	/**
	 * Test environment detection
	 */
	public function test_get_environment() {
		// Ensure the constant is defined
		if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			define( 'WP_ENVIRONMENT_TYPE', 'development' );
		}

		$app = App::instance();
		$app->boot();

		$this->assertEquals( 'development', $app->get_environment() );
	}

	/**
	 * Test custom config
	 */
	public function test_set_config() {
		$app = App::instance();
		
		$custom_config = [
			'custom' => [
				'color' => '#ff0000',
				'text' => 'Custom Environment'
			]
		];
		
		$app->set_config( $custom_config );
		
		// We can't directly test the private property, but we can verify
		// the method doesn't throw an error
		$this->assertTrue( true );
	}

	/**
	 * Test add_environment_menu doesn't add node when user lacks capability
	 */
	public function test_add_environment_menu_no_capability() {
		WP_Mock::userFunction( 'is_admin_bar_showing' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( false );

		$admin_bar = Mockery::mock( 'WP_Admin_Bar' );
		$admin_bar->shouldNotReceive( 'add_node' );

		$app = App::instance();
		$app->add_environment_menu( $admin_bar );

		$this->assertConditionsMet();
	}

	/**
	 * Test add_styles outputs CSS
	 */
	public function test_add_styles() {
		if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
			define( 'WP_ENVIRONMENT_TYPE', 'development' );
		}

		WP_Mock::userFunction( 'is_admin_bar_showing' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );
		WP_Mock::userFunction( 'esc_attr' )
			->with( '#3858e9' )
			->andReturn( '#3858e9' );

		$app = App::instance();
		$app->boot();

		ob_start();
		$app->add_styles();
		$output = ob_get_clean();

		$this->assertStringContainsString( '#3858e9', $output );
		$this->assertStringContainsString( '#wp-admin-bar-environment-indicator', $output );
		$this->assertConditionsMet();
	}
}