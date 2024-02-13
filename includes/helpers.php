<?php

declare( strict_types = 1 );

if ( ! function_exists( 'is_login' ) ) {
	/**
	 * Check whether the current page is the login screen
	 *
	 * @return bool
	 */
	function is_login(): bool {
		return isset( $GLOBALS['pagenow'] ) && 'wp-login.php' === $GLOBALS['pagenow'];
	}
}

if ( ! function_exists( 'csp_is_bool' ) ) {
	/**
	 * Validate a variable as a boolean
	 *
	 * @param mixed $value the variable to validate
	 *
	 * @return bool
	 *
	 * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
	 */
	function csp_is_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		switch ( $value ) {
			case 'off':
			case 'no':
			case 'false':
			case 'n':
			case '0':
			case null:
				return false;

			case 'on':
			case 'yes':
			case 'true':
			case 'y':
			case '1':
				return true;

			default:
				break;
		}

		return ! ! $value;
	}
	// phpcs:enable Generic.Metrics.CyclomaticComplexity.TooHigh
}

if ( ! function_exists( 'current_url' ) ) {
	/**
	 * Retrieve the current URL
	 *
	 * @return string|null
	 */
	function current_url() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return null;
		}

		$link = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );
		$path = wp_parse_url( $link, PHP_URL_PATH );

		$query = wp_parse_url( $link, PHP_URL_QUERY ) ?: '';
		$query = query_string_to_array( $query );

		if ( ! is_multisite() ) {
			$home = home_url( $path, 'https' );

			return empty( $query ) ? $home : add_query_arg( $query, $home );
		}

		$meta = rtrim( get_network_option( null, 'siteurl' ), '/' );
		$link = $meta . $path;

		return wp_validate_redirect( empty( $query ) ? $link : add_query_arg( $query, $link ) );
	}
}
