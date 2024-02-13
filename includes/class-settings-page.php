<?php

declare( strict_types = 1 );

namespace Amnesty\CSP;

use CMB2;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings management
 */
class Settings_Page {

	/**
	 * List of supported CSP directives
	 *
	 * @var array<string,array<string,string>>
	 */
	protected static array $directives = [];

	/**
	 * List of supported CSP "on or off" directive values
	 *
	 * @var array<string,string>
	 */
	protected static array $togglable_values = [];

	/**
	 * List of supported CSP configurable directive values
	 *
	 * @var array<string,string>
	 */
	protected static array $inputtable_values = [];

	/**
	 * Bind hooks and setup directives
	 */
	public function __construct() {
		add_action( 'cmb2_admin_init', [ $this, 'settings' ], 15 );

		static::$directives = [
			'default-src'     => /* translators: [admin] */ esc_html__( 'Serves as a fallback for the other directives.', 'aicsp' ),
			'connect-src'     => /* translators: [admin] */ esc_html__( 'Restricts the URLs which can be loaded using script interfaces.', 'aicsp' ),
			'font-src'        => /* translators: [admin] */ esc_html__( 'Specifies valid sources for fonts loaded using @font-face.', 'aicsp' ),
			'frame-src'       => /* translators: [admin] */ esc_html__( 'Specifies valid sources for nested browsing contexts loading using elements such as &lt;frame&gt; and &lt;iframe&gt;.', 'aicsp' ),
			'img-src'         => /* translators: [admin] */ esc_html__( 'Specifies valid sources of images and favicons.', 'aicsp' ),
			'manifest-src'    => /* translators: [admin] */ esc_html__( 'Specifies valid sources of application manifest files.', 'aicsp' ),
			'media-src'       => /* translators: [admin] */ esc_html__( 'Specifies valid sources for loading media using the &lt;audio&gt; , &lt;video&gt; and &lt;track&gt; elements.', 'aicsp' ),
			'object-src'      => /* translators: [admin] */ esc_html__( 'Specifies valid sources for the &lt;object&gt;, &lt;embed&gt;, and &lt;applet&gt; elements.', 'aicsp' ),
			'prefetch-src'    => /* translators: [admin] */ esc_html__( 'Specifies valid sources to be prefetched or prerendered.', 'aicsp' ),
			'script-src'      => /* translators: [admin] */ esc_html__( 'Specifies valid sources for JavaScript.', 'aicsp' ),
			'script-src-attr' => /* translators: [admin] */ esc_html__( 'Specifies valid sources for JavaScript inline event handlers.', 'aicsp' ),
			'script-src-elem' => /* translators: [admin] */ esc_html__( 'Specifies valid sources for JavaScript &lt;script&gt; elements.', 'aicsp' ),
			'style-src'       => /* translators: [admin] */ esc_html__( 'Specifies valid sources for stylesheets.', 'aicsp' ),
			'style-src-attr'  => /* translators: [admin] */ esc_html__( 'Specifies valid sources for inline styles applied to individual DOM elements.', 'aicsp' ),
			'style-src-elem'  => /* translators: [admin] */ esc_html__( 'Specifies valid sources for stylesheets &lt;style&gt; elements and &lt;link&gt; elements with rel="stylesheet".', 'aicsp' ),
			'worker-src'      => /* translators: [admin] */ esc_html__( 'Specifies valid sources for Worker, SharedWorker, or ServiceWorker scripts.', 'aicsp' ),
		];

		static::$togglable_values = [
			'none'           => /* translators: [admin] */ esc_html__( 'Won\'t allow loading of any resources. Takes precedence, and is incompatible with other options.', 'aicsp' ),
			'self'           => /* translators: [admin] */ esc_html__( 'Only allow resources from the current origin.', 'aicsp' ),
			'strict-dynamic' => /* translators: [admin] */ esc_html__( 'The trust granted to a script in the page due to an accompanying nonce or hash is extended to the scripts it loads.', 'aicsp' ),
			'report-sample'  => /* translators: [admin] */ esc_html__( 'Require a sample of the violating code to be included in the violation report.', 'aicsp' ),
			'unsafe-inline'  => /* translators: [admin] */ esc_html__( 'Allow use of inline resources.', 'aicsp' ),
			'unsafe-eval'    => /* translators: [admin] */ esc_html__( 'Allow use of dynamic code evaluation such as eval, setImmediate, and window.execScript.', 'aicsp' ),
			'unsafe-hashes'  => /* translators: [admin] */ esc_html__( 'Allows enabling specific inline event handlers.', 'aicsp' ),
		];

		static::$inputtable_values = [
			'domains' => /* translators: [admin] */ esc_html__( 'Only allow loading of resources from a specific host or hosts, with optional scheme, port, and path.', 'aicsp' ),
		];
	}

	/**
	 * Declare Settings
	 *
	 * @return void
	 */
	public function settings(): void {
		$settings = $this->create_metabox();

		$this->import_export( $settings );
		$this->global( $settings );
		$this->document( $settings );
		$this->navigation( $settings );

		foreach ( static::$directives as $directive => $description ) {
			$this->directive( $directive, $settings );
		}
	}

	/**
	 * Create the metabox
	 *
	 * @return \CMB2
	 */
	protected function create_metabox(): CMB2 {
		if ( is_plugin_active_for_network( Init::$plugin ) ) {
			return new_cmb2_box(
				[
					'id'              => 'amnesty_csp_options',
					'title'           => /* translators: [admin] */ esc_html__( 'CSP Options', 'aicsp' ),
					'object_types'    => [ 'options-page' ],
					'option_key'      => 'amnesty_csp',
					'capability'      => 'manage_network_options',
					'admin_menu_hook' => 'network_admin_menu',
					'parent_slug'     => 'amnesty_network_options',
					'tab_group'       => 'amnesty_network_options',
					'display_cb'      => 'amnesty_network_options_display_with_tabs',
				]
			);
		}

		return new_cmb2_box(
			[
				'id'              => 'amnesty_csp_options',
				'title'           => /* translators: [admin] */ esc_html__( 'CSP Options', 'aicsp' ),
				'object_types'    => [ 'options-page' ],
				'option_key'      => 'amnesty_csp',
				'capability'      => 'manage_options',
				'admin_menu_hook' => 'admin_menu',
				'parent_slug'     => 'amnesty_theme_options_page',
				'tab_group'       => 'amnesty_theme_options_page',
				'display_cb'      => 'amnesty_options_display_with_tabs',
			]
		);
	}

	/**
	 * Register import/export inputs
	 *
	 * @param \CMB2 $settings the metabox object
	 *
	 * @return void
	 */
	protected function import_export( CMB2 $settings ): void {
		$import_export = $settings->add_field(
			[
				'id'         => 'import_export',
				'name'       => /* translators: [admin] */ esc_html__( 'Import/Export', 'aicsp' ),
				'desc'       => /* translators: [admin] */ esc_html__( 'Import or Export your CSP configuration', 'aicsp' ),
				'type'       => 'group',
				'repeatable' => false,
				'options'    => [
					'closed' => true,
				],
			]
		);

		$settings->add_group_field(
			$import_export,
			[
				'id'      => 'export',
				'name'    => /* translators: [admin] */ esc_html__( 'Export', 'aicsp' ),
				'type'    => 'message',
				'message' => $this->load_partial( 'export' ),
			]
		);

		$settings->add_group_field(
			$import_export,
			[
				'id'      => 'import',
				'name'    => /* translators: [admin] */ esc_html__( 'Import', 'aicsp' ),
				'type'    => 'message',
				'message' => $this->load_partial( 'import' ),
				'kses'    => [
					'input' => [
						'accept' => true,
						'id'     => true,
						'type'   => true,
					],
				],
			]
		);
	}

	/**
	 * Load a template partial
	 *
	 * @param string $name the partial name
	 * @param array  $args data to pass to partial
	 *
	 * @return string
	 */
	protected function load_partial( string $name, array $args = [] ): string {
		$path = sprintf( '%s/views/partials/%s.php', dirname( Init::$file ), $name );

		if ( ! file_exists( $path ) ) {
			return '';
		}

		$args = wp_parse_args( $args, [] );

		ob_start();
		require $path;
		return ob_get_clean();
	}

	/**
	 * Register "global" settings
	 *
	 * @param \CMB2 $settings the metabox object
	 *
	 * @return void
	 */
	protected function global( CMB2 $settings ): void {
		$global = $settings->add_field(
			[
				'id'         => 'global',
				'name'       => /* translators: [admin] */ esc_html__( 'Global CSP Flags', 'aicsp' ),
				'desc'       => /* translators: [admin] */ esc_html__( 'Manage global CSP flags (i.e. ones that don\'t require value strings)', 'aicsp' ),
				'type'       => 'group',
				'repeatable' => false,
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'report_uri',
				'name' => /* translators: [admin] */ esc_html__( 'Report URI', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'Set the destination of violation reports, if required', 'aicsp' ),
				'type' => 'text_url',
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'report_to',
				'name' => /* translators: [admin] */ esc_html__( 'Report To', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'To enable the Reporting API and collect deprecation, intervention and crash reports', 'aicsp' ),
				'type' => 'text',
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'net_error',
				'name' => /* translators: [admin] */ esc_html__( 'NEL', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'To enable Network Error Logging. You must also enable the Reporting API above', 'aicsp' ),
				'type' => 'text',
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'report_only',
				'name' => /* translators: [admin] */ esc_html__( 'Report Only', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'Report CSP violations without enforcing the policy.', 'aicsp' ),
				'type' => 'checkbox',
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'https_only',
				'name' => /* translators: [admin] */ esc_html__( 'Upgrade Insecure Requests', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'Force all requests to use HTTPS.', 'aicsp' ),
				'type' => 'checkbox',
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'trusted_only',
				'name' => /* translators: [admin] */ esc_html__( 'Require Trusted Types', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'Prevent scripts from writing to the DOM.', 'aicsp' ),
				'type' => 'checkbox',
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'allow_gtm',
				'name' => /* translators: [admin] */ esc_html__( 'Allow GTM/GA', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'Allow Google to register its Trusted Type for scripts.', 'aicsp' ),
				'type' => 'checkbox',
			]
		);

		$settings->add_group_field(
			$global,
			[
				'id'   => 'enable_nonces',
				'name' => /* translators: [admin] */ esc_html__( 'Enable Script Nonces', 'aicsp' ),
				'desc' => /* translators: [admin] */ esc_html__( 'Add nonce values to all script tags.', 'aiscp' ),
				'type' => 'checkbox',
			]
		);
	}

	/**
	 * Register document settings
	 *
	 * @param \CMB2 $settings the metabox object
	 *
	 * @return void
	 */
	protected function document( CMB2 $settings ): void {
		$document = $settings->add_field(
			[
				'id'         => 'document',
				/* translators: [admin] */
				'name'       => esc_html__( 'Document Directives', 'aicsp' ),
				/* translators: [admin] */
				'desc'       => esc_html__( 'Document directives govern the properties of a document or worker environment to which a policy applies', 'aicsp' ),
				'type'       => 'group',
				'repeatable' => false,
				'options'    => [
					'closed' => true,
				],
			]
		);

		$settings->add_group_field(
			$document,
			[
				'id'   => 'base_uri',
				/* translators: [admin] */
				'name' => esc_html__( 'Base URI', 'aicsp' ),
				/* translators: [admin] */
				'desc' => esc_html__( 'Restrict which URLs can be used in a document\'s &lt;base&gt; element', 'aicsp' ),
				'type' => 'text',
			]
		);

		$settings->add_group_field(
			$document,
			[
				'id'      => 'sandbox',
				'name'    => 'sandbox',
				'desc'    => esc_html__( 'Applies restrictions to a page\'s actions including preventing popups, preventing the execution of plugins and scripts, and enforcing a same-origin policy', 'aicsp' ),
				'type'    => 'select',
				'options' => [
					'' => esc_html__( 'None (disabled)', 'aicsp' ),
					'allow-downloads',
					'allow-downloads-without-user-activation',
					'allow-forms',
					'allow-modals',
					'allow-orientation-lock',
					'allow-pointer-lock',
					'allow-popups',
					'allow-popups-to-escape-sandbox',
					'allow-presentation',
					'allow-same-origin',
					'allow-scripts',
					'allow-storage-access-by-user-activation',
					'allow-top-navigation',
					'allow-top-navigation-by-user-activation',
					'allow-top-navigation-to-custom-protocols',
				],
			]
		);
	}

	/**
	 * Register navigation settings
	 *
	 * @param \CMB2 $settings the metabox object
	 *
	 * @return void
	 */
	protected function navigation( CMB2 $settings ): void {
		$navigation = $settings->add_field(
			[
				'id'         => 'navigation',
				'name'       => /* translators: [admin] */ esc_html__( 'Navigation Directives', 'aicsp' ),
				'desc'       => /* translators: [admin] */ esc_html__( 'Navigation directives govern to which locations a user can navigate or submit a form, for example', 'aicsp' ),
				'type'       => 'group',
				'repeatable' => false,
				'options'    => [
					'closed' => true,
				],
			]
		);

		$settings->add_group_field(
			$navigation,
			[
				'id'   => 'form-action',
				'name' => 'form-action',
				'desc' => /* translators: [admin] */ esc_html__( 'Restricts the URLs which can be used as the target of form submissions from a given context.', 'aicsp' ),
				'type' => 'text',
			]
		);

		$settings->add_group_field(
			$navigation,
			[
				'id'   => 'frame-ancestors',
				'name' => 'frame-ancestors',
				'desc' => /* translators: [admin] */ esc_html__( 'Specifies valid parents that may embed a page using &lt;frame&gt;, &lt;iframe&gt;, &lt;object&gt;, &lt;embed&gt;, or &lt;applet&gt;.', 'aicsp' ),
				'type' => 'text',
			]
		);

		$settings->add_group_field(
			$navigation,
			[
				'id'   => 'navigate-to',
				'name' => 'navigate-to',
				'desc' => /* translators: [admin] */ esc_html__( 'Restricts the URLs to which a document can initiate navigations by any means including &lt;form&gt; (if form-action is not specified), &lt;a&gt;, window.location, window.open, etc. This is an enforcement on what navigations this document initiates, not on what this document is allowed to navigate to.', 'aicsp' ),
				'type' => 'text',
			]
		);
	}

	/**
	 * Register the settings for a directive
	 *
	 * @param string $directive the directive name
	 * @param \CMB2  $settings  the metabox object
	 *
	 * @return void
	 */
	protected function directive( string $directive, CMB2 $settings ): void {
		$group = $settings->add_field(
			[
				'id'         => $directive,
				'name'       => $directive,
				'desc'       => static::$directives[ $directive ],
				'type'       => 'group',
				'repeatable' => false,
				'options'    => [
					'closed' => true,
				],
			]
		);

		foreach ( static::$togglable_values as $key => $desc ) {
			$settings->add_group_field(
				$group,
				[
					'id'   => $key,
					'name' => $key,
					'desc' => $desc,
					'type' => 'checkbox',
				]
			);
		}

		foreach ( static::$inputtable_values as $key => $desc ) {
			$settings->add_group_field(
				$group,
				[
					'id'   => $key,
					'name' => $key,
					'desc' => $desc,
					'type' => 'text',
				]
			);
		}
	}
}
