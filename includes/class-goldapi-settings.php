<?php
/**
 * Admin settings.
 *
 * @package GoldAPILivePriceWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders plugin settings.
 */
final class GoldAPI_Settings {
	public const OPTION_NAME = 'goldapi_live_price_widgets_options';

	/**
	 * Registers hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	/**
	 * Returns default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'api_key'          => '',
			'refresh_interval' => GoldAPI_API_Client::FREE_MODE_MIN_REFRESH,
			'hide_branding'    => 0,
			'default_currency' => 'USD',
			'default_layout'   => 'card',
		);
	}

	/**
	 * Returns merged plugin options.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_options(): array {
		$options = get_option( self::OPTION_NAME, array() );

		return wp_parse_args( is_array( $options ) ? $options : array(), self::defaults() );
	}

	/**
	 * Adds settings page.
	 */
	public static function add_settings_page(): void {
		add_options_page(
			__( 'GoldAPI Widgets', 'goldapi-live-price-widgets' ),
			__( 'GoldAPI Widgets', 'goldapi-live-price-widgets' ),
			'manage_options',
			'goldapi-live-price-widgets',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Registers settings.
	 */
	public static function register_settings(): void {
		register_setting(
			'goldapi_live_price_widgets',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitizes settings.
	 *
	 * @param array<string,mixed> $input Raw settings.
	 * @return array<string,mixed>
	 */
	public static function sanitize_options( array $input ): array {
		$current      = self::get_options();
		$api_key      = isset( $input['api_key'] ) ? sanitize_text_field( (string) $input['api_key'] ) : '';
		$api_key      = '' !== $api_key ? $api_key : (string) $current['api_key'];
		$api_key      = ! empty( $input['clear_api_key'] ) ? '' : $api_key;
		$api_key_mode = '' !== $api_key;
		$refresh      = isset( $input['refresh_interval'] ) ? absint( $input['refresh_interval'] ) : GoldAPI_API_Client::FREE_MODE_MIN_REFRESH;

		return array(
			'api_key'          => $api_key,
			'refresh_interval' => GoldAPI_API_Client::clamp_refresh_interval( $refresh, $api_key_mode ),
			'hide_branding'    => $api_key_mode && ! empty( $input['hide_branding'] ) ? 1 : 0,
			'default_currency' => isset( $input['default_currency'] ) ? GoldAPI_API_Client::sanitize_currency( (string) $input['default_currency'] ) : 'USD',
			'default_layout'   => isset( $input['default_layout'] ) ? GoldAPI_Shortcodes::sanitize_layout( (string) $input['default_layout'] ) : 'card',
		);
	}

	/**
	 * Renders settings page.
	 */
	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$options      = self::get_options();
		$api_key_mode = GoldAPI_API_Client::is_api_key_mode( $options );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'GoldAPI Live Gold Price Widgets', 'goldapi-live-price-widgets' ); ?></h1>
			<p>
				<?php echo esc_html__( 'The plugin works without an API key using GoldAPI.io static cached endpoints. Add an API key for real-time updates as low as every 10 seconds.', 'goldapi-live-price-widgets' ); ?>
			</p>
			<?php if ( ! $api_key_mode ) : ?>
				<div class="notice notice-info inline">
					<p>
						<?php echo esc_html__( 'Prices can be delayed for up to 30 minutes. Get your GoldAPI.io API key for real-time updates, and upgrade to an unlimited plan to avoid price feed interruptions.', 'goldapi-live-price-widgets' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'goldapi_live_price_widgets' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="goldapi_api_key"><?php echo esc_html__( 'API key', 'goldapi-live-price-widgets' ); ?></label></th>
						<td>
							<input type="password" id="goldapi_api_key" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[api_key]" value="" class="regular-text" autocomplete="off" placeholder="<?php echo esc_attr( $api_key_mode ? __( 'API key saved. Enter a new key to replace it.', 'goldapi-live-price-widgets' ) : '' ); ?>" />
							<p class="description">
								<?php
								printf(
									wp_kses(
										/* translators: %s: GoldAPI dashboard URL. */
										__( 'Optional. Get an API key from the <a href="%s" target="_blank" rel="noopener noreferrer">GoldAPI.io dashboard</a>.', 'goldapi-live-price-widgets' ),
										array(
											'a' => array(
												'href'   => array(),
												'target' => array(),
												'rel'    => array(),
											),
										)
									),
									esc_url( 'https://www.goldapi.io/dashboard' )
								);
								?>
							</p>
							<?php if ( $api_key_mode ) : ?>
								<label>
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[clear_api_key]" value="1" />
									<?php echo esc_html__( 'Clear saved API key', 'goldapi-live-price-widgets' ); ?>
								</label>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="goldapi_refresh_interval"><?php echo esc_html__( 'Refresh interval', 'goldapi-live-price-widgets' ); ?></label></th>
						<td>
							<input type="number" min="<?php echo esc_attr( $api_key_mode ? (string) GoldAPI_API_Client::API_KEY_MODE_MIN_REFRESH : (string) GoldAPI_API_Client::FREE_MODE_MIN_REFRESH ); ?>" id="goldapi_refresh_interval" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[refresh_interval]" value="<?php echo esc_attr( (string) $options['refresh_interval'] ); ?>" class="small-text" />
							<p class="description"><?php echo esc_html__( 'Free mode minimum is 1800 seconds. API key mode minimum is 10 seconds.', 'goldapi-live-price-widgets' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Hide branding', 'goldapi-live-price-widgets' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[hide_branding]" value="1" <?php checked( ! empty( $options['hide_branding'] ) ); ?> <?php disabled( ! $api_key_mode ); ?> />
								<?php echo esc_html__( 'Hide “Powered by GoldAPI.io” links in API key mode.', 'goldapi-live-price-widgets' ); ?>
							</label>
							<?php if ( ! $api_key_mode ) : ?>
								<p class="description"><?php echo esc_html__( 'Branding is required in free static cached mode.', 'goldapi-live-price-widgets' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="goldapi_default_currency"><?php echo esc_html__( 'Default currency', 'goldapi-live-price-widgets' ); ?></label></th>
						<td>
							<select id="goldapi_default_currency" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_currency]">
								<?php foreach ( GoldAPI_API_Client::supported_currencies() as $currency ) : ?>
									<option value="<?php echo esc_attr( $currency ); ?>" <?php selected( $currency, $options['default_currency'] ); ?>><?php echo esc_html( $currency ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="goldapi_default_layout"><?php echo esc_html__( 'Default layout', 'goldapi-live-price-widgets' ); ?></label></th>
						<td>
							<select id="goldapi_default_layout" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_layout]">
								<?php foreach ( GoldAPI_Shortcodes::layouts() as $layout ) : ?>
									<option value="<?php echo esc_attr( $layout ); ?>" <?php selected( $layout, $options['default_layout'] ); ?>><?php echo esc_html( ucfirst( $layout ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php echo esc_html__( 'Shortcode Examples', 'goldapi-live-price-widgets' ); ?></h2>
			<pre>[goldapi_price metal="XAU" currency="AUD"]</pre>
			<pre>[goldapi_price metal="XAG" currency="USD" layout="inline"]</pre>
			<pre>[goldapi_price metal="XAU" currency="AED" layout="ticker" refresh="1800"]</pre>
			<pre>[goldapi_prices metals="XAU,XAG,XPT,XPD" currency="USD" layout="table"]</pre>
			<pre>[goldapi_gold_value currency="AUD" karat="22k" unit="ozt" weight="1"]</pre>
			<pre>[goldapi_calculator metal="XAU" currency="USD" weight="1" unit="g" dealer_discount="0" quantity="1"]</pre>
		</div>
		<?php
	}
}
