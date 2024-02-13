<?php

// phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh

declare( strict_types = 1 );

namespace Amnesty\CSP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generate the Security Policy
 */
class Security_Policy {

	/**
	 * The nonce for the current request
	 *
	 * @var string
	 */
	protected static string $nonce = '';

	/**
	 * List of supported policy types
	 *
	 * @var array<int,string>
	 */
	protected static array $directives = [
		'default-src',
		'connect-src',
		'font-src',
		'frame-src',
		'img-src',
		'manifest-src',
		'media-src',
		'object-src',
		'prefetch-src',
		'script-src',
		'script-src-attr',
		'script-src-elem',
		'style-src',
		'style-src-attr',
		'style-src-elem',
		'worker-src',
	];

	/**
	 * List of flags for a directive
	 *
	 * @var array<int,string>
	 */
	protected static array $values = [
		'self',
		'strict-dynamic',
		'report-sample',
		'unsafe-inline',
		'unsafe-eval',
		'unsafe-hashes',
	];

	/**
	 * List of input fields for a directive
	 *
	 * @var array<int,string>
	 */
	protected static array $inputs = [
		'domains',
	];

	/**
	 * "Global" settings
	 *
	 * @var array<string,string>
	 */
	protected array $global = [];

	/**
	 * Document settings
	 *
	 * @var array<string,string>
	 */
	protected array $document = [];

	/**
	 * Navigation settings
	 *
	 * @var array<string,string>
	 */
	protected array $navigation = [];

	/**
	 * Directive settings
	 *
	 * @var array
	 */
	protected array $settings = [];

	/**
	 * Bind hooks
	 *
	 * @param string $nonce the nonce for the current request
	 */
	public function __construct( string $nonce ) {
		static::$nonce = $nonce;

		add_action( 'send_headers', [ $this, 'send_headers' ], 100 );
		add_filter( 'amnesty_csp_script-src', [ $this, 'add_nonce' ] );
	}

	/**
	 * Generate and output the CSP headers
	 *
	 * @return void
	 */
	public function send_headers(): void {
		// can't do anything if output has already started
		if ( headers_sent() ) {
			return;
		}

		// we only want to target the front-end
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		if ( is_plugin_active_for_network( Init::$plugin ) ) {
			$all_settings = get_site_option( 'amnesty_csp' ) ?: [];
		} else {
			$all_settings = get_option( 'amnesty_csp' ) ?: [];
		}

		foreach ( $all_settings as $type => $settings ) {
			if ( ! isset( $settings[0] ) || ! count( $settings[0] ) ) {
				unset( $all_settings[ $type ] );
			}
		}

		// can't do anything if there are no settings set
		if ( ! $all_settings ) {
			return;
		}

		// pull out special settings
		foreach ( [ 'global', 'document', 'navigation' ] as $special ) {
			if ( isset( $all_settings[ $special ][0] ) ) {
				$this->{$special} = $all_settings[ $special ][0];
				unset( $all_settings[ $special ] );
			}
		}

		// pull out directive settings
		foreach ( static::$directives as $directive ) {
			if ( isset( $all_settings[ $directive ][0] ) ) {
				$this->settings[ $directive ] = $all_settings[ $directive ][0];
			}
		}

		$headers = wp_cache_get( 'amnesty_csp_headers' ) ?: [];

		if ( ! $headers ) {
			$headers = $this->build_headers();

			wp_cache_set( 'amnesty_csp_headers', $headers, '', 180 );
		}

		array_map( fn ( $h ) => $h && header( $h, true ), $headers );
	}

	/**
	 * Add nonce to script-src, if enabled
	 *
	 * @param array<int,string> $directive the directive parts
	 *
	 * @return array<int,string>
	 */
	public function add_nonce( array $directive ): array {
		$enabled = $this->global['enable_nonces'] ?? false;

		if ( ! csp_is_bool( $enabled ) ) {
			return $directive;
		}

		return array_merge( $directive, [ sprintf( 'nonce-%s', static::$nonce ) ] );
	}

	/**
	 * Generate the CSP headers
	 *
	 * @return array<int,string>
	 */
	protected function build_headers(): array {
		return array_filter(
			[
				$this->build_csp_header(),
				$this->build_report_to_header(),
				$this->build_nel_header(),
			] 
		);
	}

	/**
	 * Build the Content-Security-Policy header
	 *
	 * @return string
	 */
	protected function build_csp_header(): string {
		$global = wp_parse_args(
			$this->global,
			[
				'allow_gtm'    => false,
				'https_only'   => false,
				'report_only'  => false,
				'report_to'    => '',
				'report_uri'   => '',
				'trusted_only' => false,
			] 
		);

		$report_only = csp_is_bool( $global['report_only'] ) && $global['report_uri'];

		$header = 'Content-Security-Policy: ';

		if ( $report_only ) {
			$header = 'Content-Security-Policy-Report-Only: ';
		}

		$header .= $this->build_document_directive();
		$header .= $this->build_navigation_directive();
		$header .= $this->build_global_directive();

		foreach ( $this->settings as $name => $spec ) {
			$directive = $this->build_directive( $name, $spec );

			if ( ! $directive ) {
				continue;
			}

			$header .= "{$directive}; ";
		}

		$header .= $this->build_csp_reporting_directives();

		return trim( (string) apply_filters( 'amnesty_csp_header', $header, 'csp' ) );
	}

	/**
	 * Generate the CSP header document directive
	 *
	 * @return string
	 */
	protected function build_document_directive(): string {
		$document = wp_parse_args(
			$this->document,
			[
				'base_uri' => false,
				'sandbox'  => false,
			] 
		);

		$report_only = csp_is_bool( $this->global['report_only'] ?? false );

		if ( ! array_filter( $document ) ) {
			return '';
		}

		$directive = [];

		if ( $document['base_uri'] ) {
			$directive[] = sprintf( 'base-uri %s;', $document['base_uri'] );
		}

		if ( $document['sandbox'] && ! $report_only ) {
			$directive[] = sprintf( 'sandbox %s', $document['sandbox'] );
		}

		if ( ! count( $directive ) ) {
			return '';
		}

		return implode( ' ', $directive );
	}

	/**
	 * Generate the CSP header navigation directive
	 *
	 * @return string
	 */
	protected function build_navigation_directive(): string {
		$navigation = wp_parse_args(
			$this->navigation,
			[
				'form-action'     => false,
				'frame-ancestors' => false,
				'navigate-to'     => false,
			] 
		);

		if ( ! array_filter( $navigation ) ) {
			return '';
		}

		$directive = [];

		foreach ( $navigation as $prop => $value ) {
			if ( ! $value ) {
				continue;
			}

			$directive[] = "{$prop} $value;";
		}

		if ( ! count( $directive ) ) {
			return '';
		}

		return implode( ' ', $directive );
	}

	/**
	 * Generate the CSP header global directives
	 *
	 * @return string
	 */
	protected function build_global_directive(): string {
		$global = wp_parse_args(
			$this->global,
			[
				'allow_gtm'    => false,
				'https_only'   => false,
				'report_only'  => false,
				'trusted_only' => false,
			] 
		);

		$directives = [];

		if ( csp_is_bool( $global['https_only'] ) && ! csp_is_bool( $global['report_only'] ) ) {
			$directives[] = 'upgrade-insecure-requests; ';
		}

		if ( csp_is_bool( $global['trusted_only'] ) ) {
			$directives[] = "require-trusted-types-for 'script'; ";

			$types = 'trusted-types dompurify default';

			if ( csp_is_bool( $global['allow_gtm'] ) ) {
				$types .= ' goog#html; ';
			}

			$directives[] = $types;
		}

		return implode( ' ', $directives );
	}

	/**
	 * Generate the CSP header reporting directives
	 *
	 * @return string
	 */
	protected function build_csp_reporting_directives(): string {
		$global = wp_parse_args(
			$this->global,
			[
				'report_only' => false,
				'report_to'   => '',
				'report_uri'  => '',
			] 
		);

		$directives = [];

		if ( $global['report_only'] ) {
			$directives[] = sprintf( 'report-uri %s;', $global['report_uri'] );
		}

		if ( $global['report_to'] ) {
			$report_to = json_decode( $this->global['report_to'] );

			// should we add support for array of objects for report to?

			if ( isset( $report_to->group ) ) {
				$directives[] = sprintf( 'report-to %s;', trim( $report_to->group ) );
			}
		}

		return implode( ' ', $directives );
	}

	/**
	 * Build an individual directive for the CSP header
	 *
	 * @param string $name the directive name
	 * @param array  $spec the specified values
	 *
	 * @return string
	 */
	protected function build_directive( string $name, array $spec ): string {
		// 'none' takes precedence
		if ( isset( $spec['none'] ) && csp_is_bool( $spec['none'] ) ) {
			return sprintf( "%s 'none'", $name );
		}

		$directive = [];

		foreach ( static::$values as $value ) {
			if ( ! isset( $spec[ $value ] ) || ! csp_is_bool( $spec[ $value ] ) ) {
				continue;
			}

			$directive[] = sprintf( "'%s'", $value );
		}

		foreach ( static::$inputs as $input ) {
			if ( ! isset( $spec[ $input ] ) || ! $input ) {
				continue;
			}

			$directive[] = $spec[ $input ];
		}

		if ( ! count( $directive ) ) {
			return '';
		}

		$directive = apply_filters( 'amnesty_csp_directive', $directive, $name );
		$directive = apply_filters( "amnesty_csp_{$name}", $directive );

		// add name of directive to front of list
		array_unshift( $directive, $name );

		return implode( ' ', $directive );
	}

	/**
	 * Build the Report-To header
	 *
	 * @return string
	 */
	protected function build_report_to_header(): string {
		if ( ! isset( $this->global['report_to'] ) ) {
			return '';
		}

		return sprintf( 'Report-To: %s', $this->global['report_to'] );
	}

	/**
	 * Build the NEL header
	 *
	 * @return string
	 */
	protected function build_nel_header(): string {
		if ( ! isset( $this->global['net_error'] ) ) {
			return '';
		}

		return sprintf( 'NEL: %s', $this->global['net_error'] );
	}

}
