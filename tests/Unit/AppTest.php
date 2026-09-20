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
		$instance   = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}

	/**
	 * Stub wp_get_environment_type for the current test.
	 *
	 * @param string $env Environment slug to return.
	 */
	private function mock_environment_type( string $env ): void {
		WP_Mock::userFunction( 'wp_get_environment_type' )->andReturn( $env );
	}

	/**
	 * Stub the common "show indicator" gate as allowed.
	 *
	 * @param string $environment Environment slug passed to the filter.
	 */
	private function mock_show_allowed( string $environment = 'development' ): void {
		WP_Mock::userFunction( 'is_admin_bar_showing' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );
		WP_Mock::userFunction( 'apply_filters' )
			->with( 'builtnorth/wp_environment_indicator/show', true, $environment )
			->andReturnUsing(
				static function ( $tag, $value ) {
					return $value;
				}
			);
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
		$this->mock_environment_type( 'development' );

		WP_Mock::expectActionAdded( 'admin_bar_menu', [ App::instance(), 'add_environment_menu' ], 100 );
		WP_Mock::expectActionAdded( 'admin_head', [ App::instance(), 'add_styles' ] );
		WP_Mock::expectActionAdded( 'wp_head', [ App::instance(), 'add_styles' ] );

		$app = App::instance();
		$app->boot();

		$this->assertEquals( 'development', $app->get_environment() );
		$this->assertConditionsMet();
	}

	/**
	 * Test boot with local environment registers hooks
	 */
	public function test_boot_with_local_environment() {
		$this->mock_environment_type( 'local' );

		WP_Mock::expectActionAdded( 'admin_bar_menu', [ App::instance(), 'add_environment_menu' ], 100 );
		WP_Mock::expectActionAdded( 'admin_head', [ App::instance(), 'add_styles' ] );
		WP_Mock::expectActionAdded( 'wp_head', [ App::instance(), 'add_styles' ] );

		$app = App::instance();
		$app->boot();

		$this->assertEquals( 'local', $app->get_environment() );
		$this->assertConditionsMet();
	}

	/**
	 * Test boot with unknown environment does not register hooks
	 */
	public function test_boot_with_unknown_environment() {
		$this->mock_environment_type( 'qa' );

		WP_Mock::expectActionNotAdded( 'admin_bar_menu', [ App::instance(), 'add_environment_menu' ] );
		WP_Mock::expectActionNotAdded( 'admin_head', [ App::instance(), 'add_styles' ] );
		WP_Mock::expectActionNotAdded( 'wp_head', [ App::instance(), 'add_styles' ] );

		$app = App::instance();
		$app->boot();

		$this->assertSame( '', $app->get_environment() );
		$this->assertConditionsMet();
	}

	/**
	 * Test add environment menu
	 */
	public function test_add_environment_menu() {
		$this->mock_environment_type( 'development' );
		$this->mock_show_allowed( 'development' );

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
		$this->mock_environment_type( 'development' );

		$app = App::instance();
		$app->boot();

		$this->assertEquals( 'development', $app->get_environment() );
	}

	/**
	 * Test set_config merges partial overrides and affects menu title
	 */
	public function test_set_config() {
		$this->mock_environment_type( 'development' );
		$this->mock_show_allowed( 'development' );

		WP_Mock::userFunction( 'esc_html' )
			->with( 'Development' )
			->andReturn( 'Development' );

		$admin_bar = Mockery::mock( 'WP_Admin_Bar' );
		$admin_bar->shouldReceive( 'add_node' )
			->once()
			->with( Mockery::on( static function ( $args ) {
				return strpos( $args['title'], 'Development' ) !== false
					&& strpos( $args['title'], 'Custom' ) === false;
			} ) );

		$app = App::instance();
		$app->set_config(
			[
				'development' => [
					'color' => '#ff0000',
				],
			]
		);
		$app->boot();
		$app->add_environment_menu( $admin_bar );

		$this->assertConditionsMet();
	}

	/**
	 * Test set_config can replace the label
	 */
	public function test_set_config_replaces_text() {
		$this->mock_environment_type( 'development' );
		$this->mock_show_allowed( 'development' );

		WP_Mock::userFunction( 'esc_html' )
			->with( 'Custom Dev' )
			->andReturn( 'Custom Dev' );

		$admin_bar = Mockery::mock( 'WP_Admin_Bar' );
		$admin_bar->shouldReceive( 'add_node' )
			->once()
			->with( Mockery::on( static function ( $args ) {
				return strpos( $args['title'], 'Custom Dev' ) !== false;
			} ) );

		$app = App::instance();
		$app->set_config(
			[
				'development' => [
					'text' => 'Custom Dev',
				],
			]
		);
		$app->boot();
		$app->add_environment_menu( $admin_bar );

		$this->assertConditionsMet();
	}

	/**
	 * Test add_environment_menu doesn't add node when user lacks capability
	 */
	public function test_add_environment_menu_no_capability() {
		$this->mock_environment_type( 'development' );

		WP_Mock::userFunction( 'is_admin_bar_showing' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( false );
		WP_Mock::userFunction( 'apply_filters' )
			->andReturnUsing(
				static function ( $tag, $value ) {
					return $value;
				}
			);

		$admin_bar = Mockery::mock( 'WP_Admin_Bar' );
		$admin_bar->shouldNotReceive( 'add_node' );

		$app = App::instance();
		$app->boot();
		$app->add_environment_menu( $admin_bar );

		$this->assertConditionsMet();
	}

	/**
	 * Test filter can deny visibility even when capability would allow
	 */
	public function test_add_environment_menu_filter_denies() {
		$this->mock_environment_type( 'development' );

		WP_Mock::userFunction( 'is_admin_bar_showing' )->andReturn( true );
		WP_Mock::userFunction( 'current_user_can' )
			->with( 'manage_options' )
			->andReturn( true );
		WP_Mock::onFilter( 'builtnorth/wp_environment_indicator/show' )
			->with( true, 'development' )
			->reply( false );

		$admin_bar = Mockery::mock( 'WP_Admin_Bar' );
		$admin_bar->shouldNotReceive( 'add_node' );

		$app = App::instance();
		$app->boot();
		$app->add_environment_menu( $admin_bar );

		$this->assertConditionsMet();
	}

	/**
	 * Test indicator is hidden when admin bar is not showing
	 */
	public function test_add_environment_menu_admin_bar_hidden() {
		$this->mock_environment_type( 'development' );

		WP_Mock::userFunction( 'is_admin_bar_showing' )->andReturn( false );

		$admin_bar = Mockery::mock( 'WP_Admin_Bar' );
		$admin_bar->shouldNotReceive( 'add_node' );

		$app = App::instance();
		$app->boot();
		$app->add_environment_menu( $admin_bar );

		$this->assertConditionsMet();
	}

	/**
	 * Test add_styles outputs CSS
	 */
	public function test_add_styles() {
		$this->mock_environment_type( 'development' );
		$this->mock_show_allowed( 'development' );

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
