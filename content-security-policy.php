<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1 );

namespace Amnesty\CSP;

/*
Plugin Name:       Humanity Content Security Policy
Plugin URI:        https://github.com/amnestywebsite/humanity-content-security-policy
Description:       This plugin allows management of a site's Content Security Policy
Version:           1.0.0
Author:            Amnesty International
Author URI:        https://www.amnesty.org
Textdomain:        aicsp
Requires at least: 5.8.0
Tested up to:      6.4.2
Requires PHP:      8.2
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

require_once realpath( __DIR__ . '/includes/helpers.php' );
require_once realpath( __DIR__ . '/includes/class-rest-endpoint.php' );
require_once realpath( __DIR__ . '/includes/class-settings-page.php' );
require_once realpath( __DIR__ . '/includes/class-security-policy.php' );

new Init();

/**
 * Plugin instantiation class
 */
class Init {

	/**
	 * Static reference to this file
	 *
	 * @var string
	 */
	public static string $file = __FILE__;

	/**
	 * Plugin path
	 *
	 * @var string
	 */
	public static string $plugin = '';

	/**
	 * The nonce for the current request
	 *
	 * @var string
	 */
	protected static string $nonce = '';

	/**
	 * Whether nonces have been enabled
	 *
	 * @var bool
	 */
	protected static bool $nonces_enabled = false;

	/**
	 * Bind hooks and instantiate pages
	 */
	public function __construct() {
		static::$nonce  = bin2hex( random_bytes( 16 ) );
		static::$plugin = sprintf( '%s/%s', basename( __DIR__ ), basename( __FILE__ ) );

		new REST_Endpoint();
		new Security_Policy( static::$nonce );
		new Settings_Page();

		// allow a network-level CSP, but also allow site-level
		if ( is_plugin_active_for_network( static::$plugin ) ) {
			$settings = get_site_option( 'amnesty_csp' ) ?: [];
		} else {
			$settings = get_option( 'amnesty_csp' ) ?: [];
		}

		$enabled = $settings['global'][0]['enable_nonces'] ?? false;

		static::$nonces_enabled = csp_is_bool( $enabled );

		add_filter( 'register_translatable_package', [ $this, 'register_translatable_package' ], 12 );

		add_action( 'plugins_loaded', [ $this, 'textdomain' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );

		add_action( 'plugins_loaded', [ $this, 'maybe_ob_start' ], 0 );
		add_action( 'shutdown', [ $this, 'maybe_ob_end_clean' ], PHP_INT_MAX );
	}

	/**
	 * Register this plugin as a translatable package
	 *
	 * @param array<int,array<string,string>> $packages existing packages
	 *
	 * @return array<int,array<string,string>>
	 */
	public function register_translatable_package( array $packages = [] ): array {
		$packages[] = [
			'id'     => 'humanity-content-security-policy',
			'path'   => realpath( __DIR__ ),
			'pot'    => realpath( __DIR__ ) . '/languages/aicsp.pot',
			'domain' => 'aicsp',
		];

		return $packages;
	}

	/**
	 * Register textdomain
	 *
	 * @return void
	 */
	public function textdomain(): void {
		load_plugin_textdomain( 'aicsp', false, basename( __DIR__ ) . '/languages' );
	}

	/**
	 * Register plugin script
	 *
	 * @return void
	 */
	public function register_frontend_assets(): void {
		if ( is_admin() ) {
			return;
		}

		$data = get_plugin_data( static::$file );
		wp_enqueue_script( 'amnesty-csp', plugins_url( '/assets/main.js', static::$file ), [], $data['Version'], false );
	}

	/**
	 * Enqueue admin-specific assets
	 *
	 * @return void
	 */
	public function register_admin_assets(): void {
		$version = get_plugin_data( static::$file, false, false )['Version'];

		wp_enqueue_script( 'aicsp-admin', plugins_url( '/assets/admin.js', static::$file ), [ 'wp-api-fetch' ], $version, true );
	}

	/**
	 * Enable output buffering if nonces are enabled
	 *
	 * @return void
	 */
	public function maybe_ob_start(): void {
		if ( static::$nonces_enabled ) {
			ob_start( [ $this, 'add_nonces' ] );
		}
	}

	/**
	 * Add nonces to scripts, if enabled in settings
	 *
	 * @return void
	 */
	public function maybe_ob_end_clean(): void {
		if ( static::$nonces_enabled ) {
			ob_end_clean();
		}
	}

	/**
	 * Add nonces to script tags
	 *
	 * @param string $output the document HTML
	 *
	 * @return string
	 */
	public function add_nonces( string $output ): string {
		return preg_replace_callback(
			'/<script.*?>.*?<\/script>/s',
			function ( array $found ): string {
			// phpcs:ignore WordPressVIPMinimum.Variables.VariableAnalysis.StaticInsideClosure
				return str_replace( '<script ', sprintf( '<script nonce="%s" ', static::$nonce ), $found[0] );
			},
			$output
		);
	}

}
