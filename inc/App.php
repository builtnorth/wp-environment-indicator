<?php

namespace BuiltNorth\WPEnvironmentIndicator;

class App
{
	/**
	 * Holds the single instance of this class.
	 *
	 * @var App|null
	 */
	protected static $instance = null;

	/**
	 * @var string The current environment
	 */
	private string $environment;

	/**
	 * @var array Environment configurations
	 */
	private array $config = [
		'development' => [
			'color' => '#3858e9',
			'text' => 'Development'
		],
		'staging' => [
			'color' => '#db7800',
			'text' => 'Staging'
		],
		'production' => [
			'color' => '#0cb034',
			'text' => 'Production'
		]
	];

	/**
	 * Get the single instance of this class.
	 *
	 * @return App
	 */
	public static function instance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct()
	{
		// Constructor does nothing - initialization happens in boot()
	}


	/**
	 * Boot the environment indicator.
	 * This method should be called after getting the instance.
	 */
	public function boot(): void
	{
		$this->environment = $this->detect_environment();

		if ($this->environment !== '') {
			add_action('admin_bar_menu', [$this, 'add_environment_menu'], 100);
			add_action('admin_head', [$this, 'add_styles']);
			add_action('wp_head', [$this, 'add_styles']);
		}
	}

	/**
	 * Detect the current environment
	 */
	private function detect_environment(): string
	{
		if (\defined('WP_ENVIRONMENT_TYPE')) {
			$env = \WP_ENVIRONMENT_TYPE;
			return isset($this->config[$env]) ? $env : '';
		}

		return '';
	}

	/**
	 * Add the environment indicator to the admin bar
	 */
	public function add_environment_menu($admin_bar): void
	{
		if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
			return;
		}

		$config = $this->config[$this->environment];

		$admin_bar->add_node([
			'id'    => 'environment-indicator',
			'title' => sprintf('<span class="env-dot"></span> %s', esc_html($config['text'])),
			'parent' => 'top-secondary',
			'meta'  => [
				'class' => 'environment-indicator'
			]
		]);
	}

	/**
	 * Add required CSS styles
	 */
	public function add_styles(): void
	{
		if (!is_admin_bar_showing() || !current_user_can('manage_options')) {
			return;
		}

		$config = $this->config[$this->environment] ?? $this->config['development'];

?>
		<style>
			#wpadminbar #wp-admin-bar-environment-indicator {
				pointer-events: none;
				cursor: default;
			}

			#wpadminbar #wp-admin-bar-environment-indicator .ab-item {
				padding: 0 15px;
			}

			#wpadminbar #wp-admin-bar-environment-indicator .env-dot {
				display: inline-block;
				width: 6px;
				height: 6px;
				border-radius: 50%;
				border: 1px solid #ffffff;
				background: <?php echo esc_attr($config['color']); ?>;
				margin-right: 2px;
				margin-top: -2px;
				vertical-align: middle;
			}
		</style>
<?php
	}

	/**
	 * Get the current environment
	 */
	public function get_environment(): string
	{
		return $this->environment;
	}

	/**
	 * Set custom environment configuration
	 */
	public function set_config(array $config): void
	{
		$this->config = array_merge($this->config, $config);
	}
}
