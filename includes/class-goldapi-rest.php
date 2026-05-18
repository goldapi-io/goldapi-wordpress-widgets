<?php
/**
 * REST API endpoint.
 *
 * @package GoldAPILivePriceWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers REST endpoints.
 */
final class GoldAPI_REST {
	/**
	 * Registers hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Registers REST routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			'goldapi/v1',
			'/price',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_price' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'metal'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'currency' => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'decimals' => array( 'sanitize_callback' => 'absint' ),
					'refresh'  => array( 'sanitize_callback' => 'absint' ),
				),
			)
		);
	}

	/**
	 * Handles price request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_price( WP_REST_Request $request ) {
		$options        = GoldAPI_Settings::get_options();
		$metal_param    = $request->get_param( 'metal' );
		$currency_param = $request->get_param( 'currency' );
		$decimals_param = $request->get_param( 'decimals' );
		$refresh_param  = $request->get_param( 'refresh' );
		$metal          = GoldAPI_API_Client::sanitize_metal( (string) ( $metal_param ? $metal_param : 'XAU' ) );
		$currency       = GoldAPI_API_Client::sanitize_currency( (string) ( $currency_param ? $currency_param : $options['default_currency'] ) );
		$decimals       = max( 0, min( 8, absint( $decimals_param ? $decimals_param : 2 ) ) );
		$refresh        = absint( $refresh_param ? $refresh_param : $options['refresh_interval'] );
		$data           = GoldAPI_API_Client::get_price( $metal, $currency, $decimals, $refresh );

		if ( is_wp_error( $data ) ) {
			return new WP_Error(
				'goldapi_unavailable',
				__( 'Price temporarily unavailable', 'goldapi-live-price-widgets' ),
				array( 'status' => 503 )
			);
		}

		return rest_ensure_response( $data );
	}
}
