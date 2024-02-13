<?php

declare( strict_types = 1 );

namespace Amnesty\CSP;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API receiver for CSP import/export
 */
class REST_Endpoint {

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
	 * List of possible file errors
	 *
	 * @var array<int,string>
	 */
	protected static array $errors = [];
	/**
	 * Bind hooks
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register' ] );

		static::$errors = [
			UPLOAD_ERR_INI_SIZE   => /* translators: [admin] shown when file fails to upload */ esc_html__( 'The uploaded file exceeds the maximum allowed size.', 'aicsp' ),
			UPLOAD_ERR_FORM_SIZE  => /* translators: [admin] shown when file fails to upload */ esc_html__( 'The uploaded file exceeds the maximum allowed size.', 'aicsp' ),
			UPLOAD_ERR_PARTIAL    => /* translators: [admin] shown when file fails to upload */ esc_html__( 'The uploaded file was only partially uploaded.', 'aicsp' ),
			UPLOAD_ERR_NO_FILE    => /* translators: [admin] shown when file fails to upload */ esc_html__( 'No file was uploaded.', 'aicsp' ),
			UPLOAD_ERR_NO_TMP_DIR => /* translators: [admin] shown when file fails to upload */ esc_html__( 'Missing a temporary folder.', 'aicsp' ),
			UPLOAD_ERR_CANT_WRITE => /* translators: [admin] shown when file fails to upload */ esc_html__( 'Failed to write file to disk.', 'aicsp' ),
			UPLOAD_ERR_EXTENSION  => /* translators: [admin] shown when file fails to upload */ esc_html__( 'A PHP extension stopped the file upload.', 'aicsp' ),
		];
	}

	/**
	 * Register the routes
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'amnesty/v1',
			'csp',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'export' ],
				'permission_callback' => [ $this, 'permissions' ],
			] 
		);

		register_rest_route(
			'amnesty/v1',
			'csp',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'import' ],
				'permission_callback' => [ $this, 'permissions' ],
			] 
		);
	}

	/**
	 * Check whether user has permission to export/import
	 *
	 * @return bool
	 */
	public function permissions(): bool {
		if ( is_plugin_active_for_network( Init::$plugin ) ) {
			return current_user_can( 'manage_network_options' );
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Export the current CSP
	 *
	 * @return \WP_REST_Response
	 */
	public function export(): WP_REST_Response {
		if ( is_plugin_active_for_network( Init::$plugin ) ) {
			$raw_settings = get_site_option( 'amnesty_csp' );
		} else {
			$raw_settings = get_option( 'amnesty_csp' );
		}

		if ( ! is_array( $raw_settings ) || ! $raw_settings ) {
			return rest_ensure_response( false );
		}

		foreach ( $raw_settings as $type => $settings ) {
			if ( ! isset( $settings[0] ) || ! count( $settings[0] ) ) {
				unset( $raw_settings[ $type ] );
			}
		}

		// can't do anything if there are no settings set
		if ( ! $raw_settings ) {
			return rest_ensure_response( false );
		}

		$settings = [];

		// pull out special settings
		foreach ( [ 'global', 'document', 'navigation' ] as $special ) {
			if ( isset( $raw_settings[ $special ][0] ) ) {
				$settings[ $special ] = $raw_settings[ $special ][0];
				unset( $raw_settings[ $special ] );
			}
		}

		// pull out directive settings
		foreach ( static::$directives as $directive ) {
			if ( isset( $raw_settings[ $directive ][0] ) ) {
				$settings[ $directive ] = $raw_settings[ $directive ][0];
			}
		}

		return rest_ensure_response( [ 'data' => $settings ] );
	}

	/**
	 * Import a CSP
	 *
	 * @param \WP_REST_Request $request the network request
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function import( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		$json  = $this->get_valid_json( $files );

		if ( is_wp_error( $json ) ) {
			return $json;
		}

		$settings = [];

		foreach ( [ 'global', 'document', 'navigation' ] as $special ) {
			if ( ! isset( $json[ $special ] ) || ! count( $json[ $special ] ) ) {
				continue;
			}

			if ( ! isset( $settings[ $special ] ) ) {
				$settings[ $special ][0] = [];
			}

			foreach ( $json[ $special ] as $key => $value ) {
				// should possibly sanitise
				$settings[ $special ][0][ $key ] = $value;
			}
		}

		$settings = array_merge( $settings, $this->process_import( $json ) );

		$this->save_settings( $settings );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/**
	 * Attempt to retrieve valid json from files array
	 *
	 * @param array<int,array<string,mixed>> $files the files array
	 *
	 * @return \WP_Error|array<string,mixed>
	 */
	protected function get_valid_json( array $files ) {
		if ( empty( $files ) ) {
			return new WP_Error( 'rest_upload_no_data', esc_html__( 'No data supplied.' ), [ 'status' => 400 ] );
		}

		if ( empty( $files['csp'] ) ) {
			// translators: [admin]
			return new WP_Error( 'rest_upload_invalid_data', esc_html__( 'Invalid file supplied.', 'aicsp' ), [ 'status' => 400 ] );
		}

		if ( UPLOAD_ERR_OK !== $files['csp']['error'] ) {
			return new WP_Error( 'rest_upload_file_error', static::$errors[ $files['csp']['error'] ], [ 'status' => 500 ] );
		}

		$finfo = finfo_open( FILEINFO_MIME );
		$mime  = finfo_file( $finfo, $files['csp']['tmp_name'] );
		finfo_close( $finfo );

		if ( 0 !== strpos( $mime, 'application/json' ) ) {
			return new WP_Error( 'rest_upload_file_error', esc_html__( 'Invalid file type.', 'aicsp' ), [ 'status' => 400 ] );
		}

		$json = json_decode( file_get_contents( $files['csp']['tmp_name'] ), true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_error', esc_html( json_last_error_msg() ), [ 'status' => 500 ] );
		}

		return $json;
	}

	/**
	 * Process directives from JSON input into format for settings
	 *
	 * @param array<mixed> $json the inputted JSON
	 *
	 * @return array
	 */
	protected function process_import( array $json ): array {
		$settings = [];

		foreach ( static::$directives as $directive ) {
			if ( ! isset( $json[ $directive ] ) ) {
				continue;
			}

			$settings[ $directive ] = $this->process_import_directive( $directive, $json );
		}

		return $settings;
	}

	/**
	 * Process a single directive from JSON input into format for settings
	 *
	 * @param string       $directive the diretive to process
	 * @param array<mixed> $json      the inputted JSON
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function process_import_directive( string $directive, array $json ): array {
		$settings = [ [] ];

		// process each flag
		foreach ( static::$values as $value ) {
			if ( ! isset( $json[ $directive ][ $value ] ) ) {
				continue;
			}

			if ( ! csp_is_bool( $json[ $directive ][ $value ] ) ) {
				continue;
			}

			$settings[0][ $value ] = 'on';
		}

		// process each input
		foreach ( static::$inputs as $input ) {
			if ( ! isset( $json[ $directive ][ $input ] ) ) {
				continue;
			}

			if ( ! isset( $settings[0][ $input ] ) ) {
				$settings[0][ $input ] = '';
			}

			if ( 'domains' === $input ) {
				$settings[0][ $input ] = $this->get_valid_domains( $json[ $directive ][ $input ] );
			}
		}

		return $settings;
	}

	/**
	 * Save the settings to the DB
	 *
	 * @param array<string,mixed> $settings the settings to save
	 *
	 * @return void
	 */
	protected function save_settings( array $settings ): void {
		// clear out empties
		foreach ( static::$directives as $directive ) {
			if ( ! isset( $settings[ $directive ][0] ) ) {
				continue;
			}

			if ( ! is_array( $settings[ $directive ][0] ) ) {
				continue;
			}

			if ( ! count( $settings[ $directive ][0] ) ) {
				unset( $settings[ $directive ] );
			}
		}

		if ( is_plugin_active_for_network( Init::$plugin ) ) {
			update_site_option( 'amnesty_csp', $settings );
		} else {
			update_option( 'amnesty_csp', $settings );
		}
	}

	/**
	 * Parse domain list and return valid string
	 *
	 * @param string $input the raw input
	 *
	 * @return string
	 */
	protected function get_valid_domains( string $input ): string {
		$input_values = explode( ' ', $input );

		$setting = '';

		foreach ( $input_values as $input_value ) {
			switch ( $input_value ) {
				case 'none':
				case "'none'":
					$setting = "'none'";
					break 2;
				case 'self':
				case "'self'":
					$setting .= "'self' ";
					break;
				case 'data':
				case 'data:':
					$setting .= 'data: ';
					break;
				default:
					$setting .= esc_url( $input_value, 'https' ) . ' ';
					break;
			}
		}

		return $setting;
	}

}
