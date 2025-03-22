<?php

namespace WPEnvironmentIndicator;

class App
{
	/**
	 * @var string The current environment
	 */
	private string $environment;

	/**
	 * @var array Environment configurations
	 */
	private array $config = [
		'development' => [
			'color' => '#4f9fe4',
			'text' => 'Development'
		],
		'staging' => [
			'color' => '#e38a1e',
			'text' => 'Staging'
		],
		'production' => [
			'color' => '#82cd2a',
			'text' => 'Production'
		]
	];

	/**
	 * Initialize the plugin
	 */
	public function init(): void
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
				width: 8px;
				height: 8px;
				border-radius: 50%;
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
