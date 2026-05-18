<?php
/**
 * GoldAPI API client.
 *
 * @package GoldAPILivePriceWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches GoldAPI.io prices with endpoint selection, allowlists, and caching.
 */
final class GoldAPI_API_Client {
	public const BRANDING_URL             = 'https://www.goldapi.io/?utm_source=wordpress_plugin&utm_medium=widget&utm_campaign=live_price_widgets';
	public const FREE_MODE_MIN_REFRESH    = 1800;
	public const API_KEY_MODE_MIN_REFRESH = 10;

	/**
	 * Returns supported metals.
	 *
	 * @return array<string,string>
	 */
	public static function supported_metals(): array {
		$metals = array(
			'XAU' => __( 'Gold', 'goldapi-live-price-widgets' ),
			'XAG' => __( 'Silver', 'goldapi-live-price-widgets' ),
			'XPT' => __( 'Platinum', 'goldapi-live-price-widgets' ),
			'XPD' => __( 'Palladium', 'goldapi-live-price-widgets' ),
		);

		/**
		 * Filters supported metal symbols and labels.
		 *
		 * @param array<string,string> $metals Supported metals.
		 */
		return (array) apply_filters( 'goldapi_supported_metals', $metals );
	}

	/**
	 * Returns supported currencies.
	 *
	 * @return array<int,string>
	 */
	public static function supported_currencies(): array {
		$currencies = array(
			'AED',
			'AUD',
			'BTC',
			'CAD',
			'CHF',
			'CNY',
			'CZK',
			'EGP',
			'EUR',
			'GBP',
			'HKD',
			'INR',
			'JOD',
			'JPY',
			'KRW',
			'KWD',
			'MXN',
			'MYR',
			'OMR',
			'PLN',
			'RUB',
			'SAR',
			'SGD',
			'THB',
			'USD',
			'ZAR',
		);

		/**
		 * Filters supported currency symbols.
		 *
		 * @param array<int,string> $currencies Supported currencies.
		 */
		return array_values( (array) apply_filters( 'goldapi_supported_currencies', $currencies ) );
	}

	/**
	 * Returns supported gold karats.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function supported_karats(): array {
		return array(
			'24k' => array(
				'label'  => '24k',
				'purity' => 1.0,
				'field'  => 'price_gram_24k',
			),
			'22k' => array(
				'label'  => '22k',
				'purity' => 22 / 24,
				'field'  => 'price_gram_22k',
			),
			'21k' => array(
				'label'  => '21k',
				'purity' => 21 / 24,
				'field'  => 'price_gram_21k',
			),
			'20k' => array(
				'label'  => '20k',
				'purity' => 20 / 24,
				'field'  => 'price_gram_20k',
			),
			'18k' => array(
				'label'  => '18k',
				'purity' => 18 / 24,
				'field'  => 'price_gram_18k',
			),
			'16k' => array(
				'label'  => '16k',
				'purity' => 16 / 24,
				'field'  => 'price_gram_16k',
			),
			'14k' => array(
				'label'  => '14k',
				'purity' => 14 / 24,
				'field'  => 'price_gram_14k',
			),
			'10k' => array(
				'label'  => '10k',
				'purity' => 10 / 24,
				'field'  => 'price_gram_10k',
			),
		);
	}

	/**
	 * Returns supported weight units.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function weight_units(): array {
		return array(
			'g'     => array(
				'label' => 'Gram (g)',
				'grams' => 1,
				'group' => 'Metric',
			),
			'kg'    => array(
				'label' => 'Kilogram (kg)',
				'grams' => 1000,
				'group' => 'Metric',
			),
			'ozt'   => array(
				'label' => 'Troy Ounce (oz t)',
				'grams' => 31.1034768,
				'group' => 'Precious Metals',
			),
			'dwt'   => array(
				'label' => 'Pennyweight (dwt)',
				'grams' => 1.55517384,
				'group' => 'Precious Metals',
			),
			'ct'    => array(
				'label' => 'Carat (ct)',
				'grams' => 0.2,
				'group' => 'Jewellery',
			),
			'tola'  => array(
				'label' => 'Tola',
				'grams' => 11.6638038,
				'group' => 'Regional',
			),
			'tael'  => array(
				'label' => 'Tael',
				'grams' => 37.429,
				'group' => 'Regional',
			),
			'grain' => array(
				'label' => 'Grain (gr)',
				'grams' => 0.06479891,
				'group' => 'Legacy',
			),
			'baht'  => array(
				'label' => 'Baht (Thailand)',
				'grams' => 15.244,
				'group' => 'Regional',
			),
			'mace'  => array(
				'label' => 'Mace',
				'grams' => 3.779,
				'group' => 'Regional',
			),
			'chi'   => array(
				'label' => 'Chi',
				'grams' => 37.799,
				'group' => 'Regional',
			),
		);
	}

	/**
	 * Sanitizes and validates a metal symbol.
	 *
	 * @param string $metal Metal symbol.
	 * @return string
	 */
	public static function sanitize_metal( string $metal ): string {
		$metal  = strtoupper( sanitize_key( $metal ) );
		$metals = self::supported_metals();

		return array_key_exists( $metal, $metals ) ? $metal : 'XAU';
	}

	/**
	 * Sanitizes and validates a currency symbol.
	 *
	 * @param string $currency Currency symbol.
	 * @return string
	 */
	public static function sanitize_currency( string $currency ): string {
		$currency   = strtoupper( sanitize_key( $currency ) );
		$currencies = self::supported_currencies();

		return in_array( $currency, $currencies, true ) ? $currency : 'USD';
	}

	/**
	 * Sanitizes and validates a karat value.
	 *
	 * @param string $karat Karat value.
	 * @return string
	 */
	public static function sanitize_karat( string $karat ): string {
		$karat  = strtolower( sanitize_key( $karat ) );
		$karats = self::supported_karats();

		return array_key_exists( $karat, $karats ) ? $karat : '24k';
	}

	/**
	 * Sanitizes and validates a weight unit.
	 *
	 * @param string $unit Weight unit.
	 * @return string
	 */
	public static function sanitize_weight_unit( string $unit ): string {
		$unit  = strtolower( sanitize_key( $unit ) );
		$units = self::weight_units();

		return array_key_exists( $unit, $units ) ? $unit : 'g';
	}

	/**
	 * Returns the display label for a metal.
	 *
	 * @param string $metal Metal symbol.
	 * @return string
	 */
	public static function metal_label( string $metal ): string {
		$metals = self::supported_metals();

		return $metals[ $metal ] ?? $metal;
	}

	/**
	 * Calculates karat and unit values from normalized API data.
	 *
	 * @param array<string,mixed> $data Normalized price data.
	 * @param string              $karat Karat value.
	 * @param string              $unit Weight unit.
	 * @param float               $weight Unit quantity.
	 * @param int                 $decimals Decimal places.
	 * @return array<string,mixed>
	 */
	public static function calculate_weight_value( array $data, string $karat, string $unit, float $weight, int $decimals ): array {
		$karat  = self::sanitize_karat( $karat );
		$unit   = self::sanitize_weight_unit( $unit );
		$weight = max( 0.000001, $weight );
		$karats = self::supported_karats();
		$units  = self::weight_units();
		$field  = (string) $karats[ $karat ]['field'];

		if ( isset( $data[ $field ] ) && is_numeric( $data[ $field ] ) ) {
			$price_per_gram = (float) $data[ $field ];
		} else {
			$price_per_gram = ( (float) $data['price'] / 31.1034768 ) * (float) $karats[ $karat ]['purity'];
		}

		$unit_grams = (float) $units[ $unit ]['grams'];
		$unit_price = $price_per_gram * $unit_grams;
		$total      = $unit_price * $weight;
		$decimals   = max( 0, min( 8, $decimals ) );

		return array(
			'karat'                    => $karat,
			'karat_label'              => (string) $karats[ $karat ]['label'],
			'unit'                     => $unit,
			'unit_label'               => (string) $units[ $unit ]['label'],
			'unit_grams'               => $unit_grams,
			'weight'                   => $weight,
			'price_per_gram'           => $price_per_gram,
			'price_per_gram_formatted' => number_format_i18n( $price_per_gram, $decimals ),
			'unit_price'               => $unit_price,
			'unit_price_formatted'     => number_format_i18n( $unit_price, $decimals ),
			'total'                    => $total,
			'total_formatted'          => number_format_i18n( $total, $decimals ),
		);
	}

	/**
	 * Returns whether API key mode is enabled.
	 *
	 * @param array<string,mixed>|null $options Plugin options.
	 * @return bool
	 */
	public static function is_api_key_mode( ?array $options = null ): bool {
		$options = $options ?? GoldAPI_Settings::get_options();

		return ! empty( $options['api_key'] );
	}

	/**
	 * Clamps a refresh interval according to current mode.
	 *
	 * @param int  $refresh Refresh interval seconds.
	 * @param bool $api_key_mode Whether authenticated mode is active.
	 * @return int
	 */
	public static function clamp_refresh_interval( int $refresh, bool $api_key_mode ): int {
		$minimum = $api_key_mode ? self::API_KEY_MODE_MIN_REFRESH : self::FREE_MODE_MIN_REFRESH;
		$refresh = max( $minimum, $refresh );

		return min( 3600, $refresh );
	}

	/**
	 * Fetches a normalized price response.
	 *
	 * @param string $metal Metal symbol.
	 * @param string $currency Currency symbol.
	 * @param int    $decimals Number of decimals for formatted output.
	 * @param int    $refresh Requested refresh interval.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function get_price( string $metal, string $currency, int $decimals = 2, int $refresh = self::FREE_MODE_MIN_REFRESH ) {
		$options      = GoldAPI_Settings::get_options();
		$api_key_mode = self::is_api_key_mode( $options );
		$metal        = self::sanitize_metal( $metal );
		$currency     = self::sanitize_currency( $currency );
		$refresh      = self::clamp_refresh_interval( $refresh, $api_key_mode );
		$cache_ttl    = self::cache_ttl( $refresh, $api_key_mode );
		$cache_key    = self::cache_key( $metal, $currency, $api_key_mode );

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return self::normalize_payload( $cached, $metal, $currency, $decimals );
		}

		$endpoint = $api_key_mode
			? self::authenticated_endpoint_url( $metal, $currency )
			: self::public_endpoint_url( $metal, $currency );

		$args = array(
			'timeout' => 15,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);

		if ( $api_key_mode ) {
			$args['headers']['x-access-token'] = (string) $options['api_key'];
		}

		$response = wp_remote_get( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			self::debug_log( 'GoldAPI request failed: ' . $response->get_error_message() );
			return new WP_Error( 'goldapi_unavailable', __( 'Price temporarily unavailable', 'goldapi-live-price-widgets' ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$payload     = json_decode( $body, true );

		if ( $status_code < 200 || $status_code >= 300 || ! is_array( $payload ) ) {
			self::debug_log( 'GoldAPI invalid response. HTTP ' . $status_code . ' Body: ' . $body );
			return new WP_Error( 'goldapi_unavailable', __( 'Price temporarily unavailable', 'goldapi-live-price-widgets' ) );
		}

		set_transient( $cache_key, $payload, $cache_ttl );

		return self::normalize_payload( $payload, $metal, $currency, $decimals );
	}

	/**
	 * Returns static endpoint URL.
	 *
	 * @param string $metal Metal symbol.
	 * @param string $currency Currency symbol.
	 * @return string
	 */
	private static function public_endpoint_url( string $metal, string $currency ): string {
		$url = sprintf( 'https://www.goldapi.io/api/static/%s/%s', rawurlencode( $metal ), rawurlencode( $currency ) );

		/**
		 * Filters the static cached GoldAPI endpoint URL.
		 *
		 * @param string $url Public endpoint URL.
		 * @param string $metal Metal symbol.
		 * @param string $currency Currency symbol.
		 */
		return (string) apply_filters( 'goldapi_public_endpoint_url', $url, $metal, $currency );
	}

	/**
	 * Returns authenticated endpoint URL.
	 *
	 * @param string $metal Metal symbol.
	 * @param string $currency Currency symbol.
	 * @return string
	 */
	private static function authenticated_endpoint_url( string $metal, string $currency ): string {
		$url = sprintf( 'https://www.goldapi.io/api/%s/%s', rawurlencode( $metal ), rawurlencode( $currency ) );

		/**
		 * Filters the authenticated GoldAPI endpoint URL.
		 *
		 * @param string $url Authenticated endpoint URL.
		 * @param string $metal Metal symbol.
		 * @param string $currency Currency symbol.
		 */
		return (string) apply_filters( 'goldapi_authenticated_endpoint_url', $url, $metal, $currency );
	}

	/**
	 * Returns cache TTL.
	 *
	 * @param int  $refresh Clamped refresh interval.
	 * @param bool $api_key_mode Whether authenticated mode is active.
	 * @return int
	 */
	private static function cache_ttl( int $refresh, bool $api_key_mode ): int {
		$ttl = $api_key_mode ? max( self::API_KEY_MODE_MIN_REFRESH, $refresh ) : max( self::FREE_MODE_MIN_REFRESH, $refresh );

		/**
		 * Filters GoldAPI cache TTL seconds.
		 *
		 * @param int  $ttl Cache TTL.
		 * @param bool $api_key_mode Whether authenticated mode is active.
		 */
		return max( 1, absint( apply_filters( 'goldapi_cache_ttl', $ttl, $api_key_mode ) ) );
	}

	/**
	 * Builds a transient key.
	 *
	 * @param string $metal Metal symbol.
	 * @param string $currency Currency symbol.
	 * @param bool   $api_key_mode Whether authenticated mode is active.
	 * @return string
	 */
	private static function cache_key( string $metal, string $currency, bool $api_key_mode ): string {
		$mode = $api_key_mode ? 'auth' : 'public';

		return 'goldapi_' . md5( $mode . '_' . $metal . '_' . $currency );
	}

	/**
	 * Normalizes raw GoldAPI response.
	 *
	 * @param array<string,mixed> $payload Raw payload.
	 * @param string              $metal Metal symbol.
	 * @param string              $currency Currency symbol.
	 * @param int                 $decimals Decimal places.
	 * @return array<string,mixed>|WP_Error
	 */
	private static function normalize_payload( array $payload, string $metal, string $currency, int $decimals ) {
		if ( ! isset( $payload['price'] ) || ! is_numeric( $payload['price'] ) ) {
			return new WP_Error( 'goldapi_unavailable', __( 'Price temporarily unavailable', 'goldapi-live-price-widgets' ) );
		}

		$timestamp = isset( $payload['timestamp'] ) && is_numeric( $payload['timestamp'] ) ? (int) $payload['timestamp'] : time();
		$price     = (float) $payload['price'];

		$normalized = array(
			'metal'           => isset( $payload['metal'] ) ? self::sanitize_metal( (string) $payload['metal'] ) : $metal,
			'metal_label'     => self::metal_label( $metal ),
			'currency'        => isset( $payload['currency'] ) ? self::sanitize_currency( (string) $payload['currency'] ) : $currency,
			'price'           => $price,
			'price_formatted' => number_format_i18n( $price, max( 0, min( 8, $decimals ) ) ),
			'timestamp'       => $timestamp,
			'updated'         => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ),
			'ch'              => isset( $payload['ch'] ) && is_numeric( $payload['ch'] ) ? (float) $payload['ch'] : null,
			'chp'             => isset( $payload['chp'] ) && is_numeric( $payload['chp'] ) ? (float) $payload['chp'] : null,
		);

		foreach ( self::supported_karats() as $karat ) {
			$field = (string) $karat['field'];
			if ( isset( $payload[ $field ] ) && is_numeric( $payload[ $field ] ) ) {
				$normalized[ $field ] = (float) $payload[ $field ];
			}
		}

		return $normalized;
	}

	/**
	 * Logs debug details only when WP_DEBUG is enabled.
	 *
	 * @param string $message Debug message.
	 */
	private static function debug_log( string $message ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[GoldAPI Live Price Widgets] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
