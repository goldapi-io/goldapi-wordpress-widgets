<?php
/**
 * Shortcodes and frontend markup.
 *
 * @package GoldAPILivePriceWidgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders shortcodes.
 */
final class GoldAPI_Shortcodes {
	/**
	 * Tracks whether frontend assets were enqueued for the current request.
	 *
	 * @var bool
	 */
	private static bool $assets_enqueued = false;

	/**
	 * Registers hooks.
	 */
	public static function init(): void {
		add_shortcode( 'goldapi_price', array( __CLASS__, 'render_price_shortcode' ) );
		add_shortcode( 'goldapi_prices', array( __CLASS__, 'render_prices_shortcode' ) );
		add_shortcode( 'goldapi_gold_value', array( __CLASS__, 'render_gold_value_shortcode' ) );
		add_shortcode( 'goldapi_calculator', array( __CLASS__, 'render_calculator_shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_block' ) );
	}

	/**
	 * Supported layouts.
	 *
	 * @return array<int,string>
	 */
	public static function layouts(): array {
		return array( 'card', 'inline', 'ticker', 'table' );
	}

	/**
	 * Supported gold value layouts.
	 *
	 * @return array<int,string>
	 */
	public static function value_layouts(): array {
		return array( 'karat', 'card' );
	}

	/**
	 * Sanitizes layout.
	 *
	 * @param string $layout Layout name.
	 * @return string
	 */
	public static function sanitize_layout( string $layout ): string {
		$layout = sanitize_key( $layout );

		return in_array( $layout, self::layouts(), true ) ? $layout : 'card';
	}

	/**
	 * Sanitizes a gold value layout.
	 *
	 * @param string $layout Layout name.
	 * @return string
	 */
	public static function sanitize_value_layout( string $layout ): string {
		$layout = sanitize_key( $layout );

		return in_array( $layout, self::value_layouts(), true ) ? $layout : 'karat';
	}

	/**
	 * Renders single-price shortcode.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_price_shortcode( $atts ): string {
		self::enqueue_assets();

		$options      = GoldAPI_Settings::get_options();
		$api_key_mode = GoldAPI_API_Client::is_api_key_mode( $options );
		$atts         = shortcode_atts(
			array(
				'metal'         => 'XAU',
				'currency'      => (string) $options['default_currency'],
				'layout'        => (string) $options['default_layout'],
				'refresh'       => (string) $options['refresh_interval'],
				'show_branding' => 'yes',
				'decimals'      => '2',
			),
			is_array( $atts ) ? $atts : array(),
			'goldapi_price'
		);

		$metal    = GoldAPI_API_Client::sanitize_metal( (string) $atts['metal'] );
		$currency = GoldAPI_API_Client::sanitize_currency( (string) $atts['currency'] );
		$layout   = self::sanitize_layout( (string) $atts['layout'] );
		$refresh  = GoldAPI_API_Client::clamp_refresh_interval( absint( $atts['refresh'] ), $api_key_mode );
		$decimals = max( 0, min( 8, absint( $atts['decimals'] ) ) );
		$data     = GoldAPI_API_Client::get_price( $metal, $currency, $decimals, $refresh );

		if ( is_wp_error( $data ) ) {
			$data = self::unavailable_data( $metal, $currency );
		}

		$show_branding = self::should_show_branding( (string) $atts['show_branding'], $api_key_mode, $options );

		if ( 'table' === $layout ) {
			return self::render_table_widget( array( $data ), $currency, $refresh, $decimals, $show_branding );
		}

		return self::render_single_widget( $data, $layout, $refresh, $decimals, $show_branding );
	}

	/**
	 * Renders multi-price shortcode.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_prices_shortcode( $atts ): string {
		self::enqueue_assets();

		$options      = GoldAPI_Settings::get_options();
		$api_key_mode = GoldAPI_API_Client::is_api_key_mode( $options );
		$atts         = shortcode_atts(
			array(
				'metals'        => 'XAU,XAG,XPT,XPD',
				'currency'      => (string) $options['default_currency'],
				'layout'        => 'table',
				'refresh'       => (string) $options['refresh_interval'],
				'show_branding' => 'yes',
				'decimals'      => '2',
			),
			is_array( $atts ) ? $atts : array(),
			'goldapi_prices'
		);

		$currency = GoldAPI_API_Client::sanitize_currency( (string) $atts['currency'] );
		$refresh  = GoldAPI_API_Client::clamp_refresh_interval( absint( $atts['refresh'] ), $api_key_mode );
		$decimals = max( 0, min( 8, absint( $atts['decimals'] ) ) );
		$metals   = self::parse_metals( (string) $atts['metals'] );
		$rows     = array();

		foreach ( $metals as $metal ) {
			$data   = GoldAPI_API_Client::get_price( $metal, $currency, $decimals, $refresh );
			$rows[] = is_wp_error( $data ) ? self::unavailable_data( $metal, $currency ) : $data;
		}

		$show_branding = self::should_show_branding( (string) $atts['show_branding'], $api_key_mode, $options );

		return self::render_table_widget( $rows, $currency, $refresh, $decimals, $show_branding );
	}

	/**
	 * Renders gold value shortcode with karat and weight unit configuration.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_gold_value_shortcode( $atts ): string {
		self::enqueue_assets();

		$options      = GoldAPI_Settings::get_options();
		$api_key_mode = GoldAPI_API_Client::is_api_key_mode( $options );
		$atts         = shortcode_atts(
			array(
				'currency'      => (string) $options['default_currency'],
				'karat'         => '24k',
				'unit'          => 'g',
				'weight'        => '1',
				'layout'        => 'karat',
				'refresh'       => (string) $options['refresh_interval'],
				'show_branding' => 'yes',
				'decimals'      => '2',
			),
			is_array( $atts ) ? $atts : array(),
			'goldapi_gold_value'
		);

		$metal    = 'XAU';
		$currency = GoldAPI_API_Client::sanitize_currency( (string) $atts['currency'] );
		$karat    = GoldAPI_API_Client::sanitize_karat( (string) $atts['karat'] );
		$unit     = GoldAPI_API_Client::sanitize_weight_unit( (string) $atts['unit'] );
		$weight   = max( 0.000001, (float) $atts['weight'] );
		$layout   = self::sanitize_value_layout( (string) $atts['layout'] );
		$refresh  = GoldAPI_API_Client::clamp_refresh_interval( absint( $atts['refresh'] ), $api_key_mode );
		$decimals = max( 0, min( 8, absint( $atts['decimals'] ) ) );
		$data     = GoldAPI_API_Client::get_price( $metal, $currency, $decimals, $refresh );

		if ( is_wp_error( $data ) ) {
			$data = self::unavailable_data( $metal, $currency );
		}

		$value = isset( $data['price'] ) && null !== $data['price']
			? GoldAPI_API_Client::calculate_weight_value( $data, $karat, $unit, $weight, $decimals )
			: self::unavailable_weight_value( $karat, $unit, $weight );

		$show_branding = self::should_show_branding( (string) $atts['show_branding'], $api_key_mode, $options );

		return self::render_gold_value_widget( $data, $value, $layout, $refresh, $decimals, $show_branding );
	}

	/**
	 * Renders an interactive scrap metal calculator.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function render_calculator_shortcode( $atts ): string {
		self::enqueue_assets();

		$options      = GoldAPI_Settings::get_options();
		$api_key_mode = GoldAPI_API_Client::is_api_key_mode( $options );
		$atts         = shortcode_atts(
			array(
				'metal'           => 'XAU',
				'currency'        => (string) $options['default_currency'],
				'weight'          => '1',
				'unit'            => 'g',
				'dealer_discount' => '0',
				'quantity'        => '1',
				'purity'          => '0.999',
				'refresh'         => (string) $options['refresh_interval'],
				'show_branding'   => 'yes',
				'decimals'        => '2',
			),
			is_array( $atts ) ? $atts : array(),
			'goldapi_calculator'
		);

		$metal           = GoldAPI_API_Client::sanitize_metal( (string) $atts['metal'] );
		$currency        = GoldAPI_API_Client::sanitize_currency( (string) $atts['currency'] );
		$unit            = GoldAPI_API_Client::sanitize_weight_unit( (string) $atts['unit'] );
		$weight          = max( 0.000001, (float) $atts['weight'] );
		$dealer_discount = max( 0, min( 100, (float) $atts['dealer_discount'] ) );
		$quantity        = max( 1, absint( $atts['quantity'] ) );
		$purity          = self::sanitize_purity( (string) $atts['purity'] );
		$refresh         = GoldAPI_API_Client::clamp_refresh_interval( absint( $atts['refresh'] ), $api_key_mode );
		$decimals        = max( 0, min( 8, absint( $atts['decimals'] ) ) );
		$data            = GoldAPI_API_Client::get_price( $metal, $currency, $decimals, $refresh );

		if ( is_wp_error( $data ) ) {
			$data = self::unavailable_data( $metal, $currency );
		}

		$show_branding = self::should_show_branding( (string) $atts['show_branding'], $api_key_mode, $options );

		return self::render_calculator_widget( $data, $unit, $weight, $dealer_discount, $quantity, $purity, $refresh, $decimals, $show_branding );
	}

	/**
	 * Registers a build-free Gutenberg block that renders a shortcode.
	 */
	public static function register_block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		wp_register_script(
			'goldapi-live-price-widgets-block',
			GOLDAPI_LIVE_PRICE_WIDGETS_URL . 'assets/goldapi-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ),
			GOLDAPI_LIVE_PRICE_WIDGETS_VERSION,
			true
		);

		register_block_type(
			'goldapi/live-price',
			array(
				'editor_script'   => 'goldapi-live-price-widgets-block',
				'render_callback' => array( __CLASS__, 'render_block' ),
				'attributes'      => array(
					'metal'    => array(
						'type'    => 'string',
						'default' => 'XAU',
					),
					'currency' => array(
						'type'    => 'string',
						'default' => 'USD',
					),
					'layout'   => array(
						'type'    => 'string',
						'default' => 'card',
					),
					'refresh'  => array(
						'type'    => 'number',
						'default' => GoldAPI_API_Client::FREE_MODE_MIN_REFRESH,
					),
				),
			)
		);
	}

	/**
	 * Renders the block using shortcode logic.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render_block( array $attributes ): string {
		return self::render_price_shortcode(
			array(
				'metal'    => $attributes['metal'] ?? 'XAU',
				'currency' => $attributes['currency'] ?? 'USD',
				'layout'   => $attributes['layout'] ?? 'card',
				'refresh'  => $attributes['refresh'] ?? GoldAPI_API_Client::FREE_MODE_MIN_REFRESH,
			)
		);
	}

	/**
	 * Enqueues frontend assets once.
	 */
	private static function enqueue_assets(): void {
		if ( self::$assets_enqueued ) {
			return;
		}

		self::$assets_enqueued = true;

		wp_enqueue_style(
			'goldapi-live-price-widgets',
			GOLDAPI_LIVE_PRICE_WIDGETS_URL . 'assets/goldapi-widgets.css',
			array(),
			GOLDAPI_LIVE_PRICE_WIDGETS_VERSION
		);

		wp_enqueue_script(
			'goldapi-live-price-widgets',
			GOLDAPI_LIVE_PRICE_WIDGETS_URL . 'assets/goldapi-widgets.js',
			array(),
			GOLDAPI_LIVE_PRICE_WIDGETS_VERSION,
			true
		);

		wp_localize_script(
			'goldapi-live-price-widgets',
			'GoldAPIWidgets',
			array(
				'restUrl' => esc_url_raw( rest_url( 'goldapi/v1/price' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	/**
	 * Renders a single widget.
	 *
	 * @param array<string,mixed> $data Price data.
	 * @param string              $layout Layout.
	 * @param int                 $refresh Refresh interval.
	 * @param int                 $decimals Decimal places.
	 * @param bool                $show_branding Whether branding should render.
	 * @return string
	 */
	private static function render_single_widget( array $data, string $layout, int $refresh, int $decimals, bool $show_branding ): string {
		$classes = 'goldapi-widget goldapi-layout-' . $layout;
		$change  = self::format_change( $data );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-refresh="<?php echo esc_attr( (string) $refresh ); ?>" data-decimals="<?php echo esc_attr( (string) $decimals ); ?>">
			<div class="goldapi-price-source" data-metal="<?php echo esc_attr( (string) $data['metal'] ); ?>" data-currency="<?php echo esc_attr( (string) $data['currency'] ); ?>">
				<?php if ( 'inline' !== $layout ) : ?>
					<div class="goldapi-metal"><?php echo esc_html( (string) $data['metal_label'] ); ?></div>
				<?php endif; ?>
				<div class="goldapi-main">
					<span class="goldapi-symbol"><?php echo esc_html( (string) $data['metal'] . '/' . (string) $data['currency'] ); ?></span>
					<span class="goldapi-price-value"><?php echo esc_html( (string) $data['price_formatted'] ); ?></span>
					<span class="goldapi-currency"><?php echo esc_html( (string) $data['currency'] ); ?></span>
					<span class="goldapi-status" aria-live="polite" aria-label=""></span>
				</div>
				<div class="goldapi-meta">
					<span class="goldapi-change"><?php echo esc_html( $change ); ?></span>
					<span class="goldapi-updated"><?php echo esc_html( (string) $data['updated'] ); ?></span>
				</div>
			</div>
			<?php echo self::branding_html( $show_branding ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Renders table widget.
	 *
	 * @param array<int,array<string,mixed>> $rows Price rows.
	 * @param string                         $currency Currency.
	 * @param int                            $refresh Refresh interval.
	 * @param int                            $decimals Decimal places.
	 * @param bool                           $show_branding Whether branding should render.
	 * @return string
	 */
	private static function render_table_widget( array $rows, string $currency, int $refresh, int $decimals, bool $show_branding ): string {
		ob_start();
		?>
		<div class="goldapi-widget goldapi-layout-table" data-refresh="<?php echo esc_attr( (string) $refresh ); ?>" data-decimals="<?php echo esc_attr( (string) $decimals ); ?>">
			<table class="goldapi-table">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Metal', 'goldapi-live-price-widgets' ); ?></th>
						<th><?php echo esc_html__( 'Price', 'goldapi-live-price-widgets' ); ?></th>
						<th><?php echo esc_html__( 'Currency', 'goldapi-live-price-widgets' ); ?></th>
						<th><?php echo esc_html__( 'Updated', 'goldapi-live-price-widgets' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr class="goldapi-price-source" data-metal="<?php echo esc_attr( (string) $row['metal'] ); ?>" data-currency="<?php echo esc_attr( $currency ); ?>">
							<td><span class="goldapi-metal"><?php echo esc_html( (string) $row['metal_label'] ); ?></span> <span class="goldapi-symbol"><?php echo esc_html( (string) $row['metal'] ); ?></span></td>
							<td><span class="goldapi-price-value"><?php echo esc_html( (string) $row['price_formatted'] ); ?></span></td>
							<td><span class="goldapi-currency"><?php echo esc_html( $currency ); ?></span></td>
							<td><span class="goldapi-updated"><?php echo esc_html( (string) $row['updated'] ); ?></span><span class="goldapi-status" aria-live="polite"></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php echo self::branding_html( $show_branding ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Renders a karat/weight gold value widget.
	 *
	 * @param array<string,mixed> $data Spot price data.
	 * @param array<string,mixed> $value Calculated value data.
	 * @param string              $layout Layout.
	 * @param int                 $refresh Refresh interval.
	 * @param int                 $decimals Decimal places.
	 * @param bool                $show_branding Whether branding should render.
	 * @return string
	 */
	private static function render_gold_value_widget( array $data, array $value, string $layout, int $refresh, int $decimals, bool $show_branding ): string {
		$classes = 'goldapi-widget goldapi-layout-' . $layout . ' goldapi-gold-value-widget';

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-refresh="<?php echo esc_attr( (string) $refresh ); ?>" data-decimals="<?php echo esc_attr( (string) $decimals ); ?>">
			<div
				class="goldapi-price-source"
				data-metal="XAU"
				data-currency="<?php echo esc_attr( (string) $data['currency'] ); ?>"
				data-karat="<?php echo esc_attr( (string) $value['karat'] ); ?>"
				data-unit="<?php echo esc_attr( (string) $value['unit'] ); ?>"
				data-unit-grams="<?php echo esc_attr( (string) $value['unit_grams'] ); ?>"
				data-weight="<?php echo esc_attr( (string) $value['weight'] ); ?>"
				data-gram-price="<?php echo esc_attr( isset( $value['price_per_gram'] ) ? (string) $value['price_per_gram'] : '' ); ?>"
				data-unit-price="<?php echo esc_attr( isset( $value['unit_price'] ) ? (string) $value['unit_price'] : '' ); ?>"
			>
				<div class="goldapi-metal"><?php echo esc_html__( 'Gold Value', 'goldapi-live-price-widgets' ); ?></div>
				<div class="goldapi-main">
					<span class="goldapi-symbol"><?php echo esc_html( (string) $value['karat_label'] . ' ' . (string) $value['unit_label'] ); ?></span>
					<span class="goldapi-total-value"><?php echo esc_html( (string) $value['total_formatted'] ); ?></span>
					<span class="goldapi-currency"><?php echo esc_html( (string) $data['currency'] ); ?></span>
					<span class="goldapi-status" aria-live="polite" aria-label=""></span>
				</div>
				<div class="goldapi-value-grid">
					<div>
						<label class="goldapi-value-label"><?php echo esc_html__( 'Weight', 'goldapi-live-price-widgets' ); ?></label>
						<span class="goldapi-weight-control">
							<input class="goldapi-weight-input" type="number" min="0.000001" step="any" value="<?php echo esc_attr( (string) $value['weight'] ); ?>" inputmode="decimal" />
							<span class="goldapi-weight-unit"><?php echo esc_html( (string) $value['unit'] ); ?></span>
						</span>
					</div>
					<div>
						<span class="goldapi-value-label">
							<?php
							printf(
								/* translators: %s: selected weight unit. */
								esc_html__( 'Per %s', 'goldapi-live-price-widgets' ),
								esc_html( (string) $value['unit'] )
							);
							?>
						</span>
						<span class="goldapi-unit-price-value"><?php echo esc_html( (string) $value['unit_price_formatted'] ); ?></span>
						<span class="goldapi-tile-currency"><?php echo esc_html( (string) $data['currency'] ); ?></span>
					</div>
					<div>
						<span class="goldapi-value-label"><?php echo esc_html__( 'Per gram', 'goldapi-live-price-widgets' ); ?></span>
						<span class="goldapi-gram-price-value"><?php echo esc_html( (string) $value['price_per_gram_formatted'] ); ?></span>
						<span class="goldapi-tile-currency"><?php echo esc_html( (string) $data['currency'] ); ?></span>
					</div>
					<div>
						<span class="goldapi-value-label"><?php echo esc_html__( 'Updated', 'goldapi-live-price-widgets' ); ?></span>
						<span class="goldapi-updated goldapi-updated-small"><?php echo esc_html( (string) $data['updated'] ); ?></span>
					</div>
				</div>
			</div>
			<?php echo self::branding_html( $show_branding ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Renders scrap metal calculator widget.
	 *
	 * @param array<string,mixed> $data Price data.
	 * @param string              $unit Weight unit.
	 * @param float               $weight Weight.
	 * @param float               $dealer_discount Dealer discount percentage.
	 * @param int                 $quantity Quantity.
	 * @param float               $purity Purity ratio.
	 * @param int                 $refresh Refresh interval.
	 * @param int                 $decimals Decimal places.
	 * @param bool                $show_branding Whether branding should render.
	 * @return string
	 */
	private static function render_calculator_widget( array $data, string $unit, float $weight, float $dealer_discount, int $quantity, float $purity, int $refresh, int $decimals, bool $show_branding ): string {
		$units           = GoldAPI_API_Client::weight_units();
		$metals          = GoldAPI_API_Client::supported_metals();
		$currencies      = GoldAPI_API_Client::supported_currencies();
		$spot_price      = isset( $data['price'] ) && is_numeric( $data['price'] ) ? (float) $data['price'] : 0.0;
		$unit_grams      = (float) $units[ $unit ]['grams'];
		$pure_weight     = $weight * $unit_grams * $purity * $quantity;
		$price_per_gram  = $spot_price > 0 ? $spot_price / 31.1034768 : 0.0;
		$value_before    = $price_per_gram * $pure_weight;
		$dealer_margin   = $value_before * ( $dealer_discount / 100 );
		$estimated_value = max( 0, $value_before - $dealer_margin );
		$price_per_kg    = $price_per_gram * 1000;
		$currency        = (string) $data['currency'];
		$metal           = (string) $data['metal'];
		$purity_options  = self::purity_options();
		$selected_purity = self::purity_key_from_value( $purity );
		$api_key_mode    = GoldAPI_API_Client::is_api_key_mode();

		ob_start();
		?>
		<div class="goldapi-widget goldapi-layout-scrap-calculator" data-refresh="<?php echo esc_attr( (string) $refresh ); ?>" data-decimals="<?php echo esc_attr( (string) $decimals ); ?>">
			<div
				class="goldapi-price-source goldapi-calculator-source"
				data-metal="<?php echo esc_attr( $metal ); ?>"
				data-currency="<?php echo esc_attr( $currency ); ?>"
				data-spot-price="<?php echo esc_attr( (string) $spot_price ); ?>"
				data-unit-grams="<?php echo esc_attr( (string) $unit_grams ); ?>"
			>
				<div class="goldapi-calculator-kicker"><span class="goldapi-calculator-pair"><?php echo esc_html( $metal . '/' . $currency ); ?></span> <?php echo esc_html__( 'Calculator', 'goldapi-live-price-widgets' ); ?></div>
				<div class="goldapi-calculator-title">
					<img class="goldapi-calculator-logo" src="<?php echo esc_url( GOLDAPI_LIVE_PRICE_WIDGETS_URL . 'assets/goldapi-logo.svg' ); ?>" alt="<?php echo esc_attr__( 'GoldAPI.io', 'goldapi-live-price-widgets' ); ?>" />
					<h3><?php echo esc_html__( 'Scrap Metal Calculator', 'goldapi-live-price-widgets' ); ?></h3>
					<span class="goldapi-status" aria-live="polite" aria-label=""></span>
				</div>

				<?php if ( ! $api_key_mode ) : ?>
					<div class="goldapi-calculator-note">
						<strong><?php echo esc_html__( 'Note:', 'goldapi-live-price-widgets' ); ?></strong>
						<?php echo esc_html__( 'Prices can be delayed for up to 30 minutes. Get your GoldAPI.io API key for real-time updates, and upgrade to an unlimited plan to avoid price feed interruptions.', 'goldapi-live-price-widgets' ); ?>
					</div>
				<?php endif; ?>

				<div class="goldapi-calculator-grid">
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Metal', 'goldapi-live-price-widgets' ); ?></label>
						<select class="goldapi-calculator-metal">
							<?php foreach ( $metals as $symbol => $label ) : ?>
								<option value="<?php echo esc_attr( $symbol ); ?>" <?php selected( $symbol, $metal ); ?>><?php echo esc_html( $label . ' (' . $symbol . ')' ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Currency', 'goldapi-live-price-widgets' ); ?></label>
						<select class="goldapi-calculator-currency">
							<?php foreach ( $currencies as $currency_option ) : ?>
								<option value="<?php echo esc_attr( $currency_option ); ?>" <?php selected( $currency_option, $currency ); ?>><?php echo esc_html( $currency_option ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Spot price per troy ounce', 'goldapi-live-price-widgets' ); ?></label>
						<span><?php echo esc_html__( 'Cached GoldAPI.io price', 'goldapi-live-price-widgets' ); ?></span>
						<input class="goldapi-calculator-spot" type="text" value="<?php echo esc_attr( number_format_i18n( $spot_price, $decimals ) ); ?>" readonly />
					</div>
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Weight', 'goldapi-live-price-widgets' ); ?></label>
						<span><?php echo esc_html__( 'Item weight', 'goldapi-live-price-widgets' ); ?></span>
						<input class="goldapi-calculator-weight" type="number" min="0.000001" step="any" value="<?php echo esc_attr( (string) $weight ); ?>" inputmode="decimal" />
					</div>
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Weight unit', 'goldapi-live-price-widgets' ); ?></label>
						<select class="goldapi-calculator-unit">
							<?php foreach ( $units as $unit_value => $unit_data ) : ?>
								<option value="<?php echo esc_attr( $unit_value ); ?>" data-grams="<?php echo esc_attr( (string) $unit_data['grams'] ); ?>" <?php selected( $unit_value, $unit ); ?>><?php echo esc_html( (string) $unit_data['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Purity', 'goldapi-live-price-widgets' ); ?></label>
						<select class="goldapi-calculator-purity">
							<?php foreach ( $purity_options as $purity_key => $purity_data ) : ?>
								<option value="<?php echo esc_attr( (string) $purity_data['value'] ); ?>" <?php selected( $purity_key, $selected_purity ); ?>><?php echo esc_html( (string) $purity_data['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Dealer discount / margin %', 'goldapi-live-price-widgets' ); ?></label>
						<input class="goldapi-calculator-discount" type="number" min="0" max="100" step="any" value="<?php echo esc_attr( (string) $dealer_discount ); ?>" inputmode="decimal" />
					</div>
					<div class="goldapi-calculator-field">
						<label><?php echo esc_html__( 'Quantity', 'goldapi-live-price-widgets' ); ?></label>
						<input class="goldapi-calculator-quantity" type="number" min="1" step="1" value="<?php echo esc_attr( (string) $quantity ); ?>" inputmode="numeric" />
					</div>
				</div>

				<div class="goldapi-calculator-results">
					<?php
					self::calculator_result_tile( __( 'Pure metal weight', 'goldapi-live-price-widgets' ), number_format_i18n( $pure_weight, 4 ) . ' g', 'goldapi-calculator-pure-weight' );
					self::calculator_result_tile( __( 'Value before margin', 'goldapi-live-price-widgets' ), number_format_i18n( $value_before, $decimals ) . ' ' . $currency, 'goldapi-calculator-before-margin' );
					self::calculator_result_tile( __( 'Dealer margin amount', 'goldapi-live-price-widgets' ), number_format_i18n( $dealer_margin, $decimals ) . ' ' . $currency, 'goldapi-calculator-margin' );
					self::calculator_result_tile( __( 'Estimated final value', 'goldapi-live-price-widgets' ), number_format_i18n( $estimated_value, $decimals ) . ' ' . $currency, 'goldapi-calculator-final', true );
					?>
				</div>

				<div class="goldapi-calculator-rates">
					<?php
					self::calculator_result_tile( __( 'Price per gram', 'goldapi-live-price-widgets' ), number_format_i18n( $price_per_gram, 4 ) . ' ' . $currency, 'goldapi-calculator-price-gram' );
					self::calculator_result_tile( __( 'Price per ounce', 'goldapi-live-price-widgets' ), number_format_i18n( $spot_price, $decimals ) . ' ' . $currency, 'goldapi-calculator-price-ounce' );
					self::calculator_result_tile( __( 'Price per kilogram', 'goldapi-live-price-widgets' ), number_format_i18n( $price_per_kg, $decimals ) . ' ' . $currency, 'goldapi-calculator-price-kg' );
					self::calculator_result_tile( __( 'Updated', 'goldapi-live-price-widgets' ), (string) $data['updated'], 'goldapi-updated goldapi-updated-small' );
					?>
				</div>
			</div>
			<?php echo self::branding_html( $show_branding ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Renders a calculator result tile.
	 *
	 * @param string $label Label.
	 * @param string $value Value.
	 * @param string $value_class Value class.
	 * @param bool   $strong Whether value should be emphasized.
	 */
	private static function calculator_result_tile( string $label, string $value, string $value_class, bool $strong = false ): void {
		?>
		<div class="goldapi-calculator-tile<?php echo $strong ? ' goldapi-calculator-tile-strong' : ''; ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<strong class="<?php echo esc_attr( $value_class ); ?>"><?php echo esc_html( $value ); ?></strong>
		</div>
		<?php
	}

	/**
	 * Parses metals list.
	 *
	 * @param string $metals Comma-separated metals.
	 * @return array<int,string>
	 */
	private static function parse_metals( string $metals ): array {
		$items = array_filter( array_map( 'trim', explode( ',', $metals ) ) );
		$items = array_map( array( 'GoldAPI_API_Client', 'sanitize_metal' ), $items );
		$items = array_values( array_unique( $items ) );

		return $items ? $items : array( 'XAU' );
	}

	/**
	 * Returns unavailable placeholder data.
	 *
	 * @param string $metal Metal.
	 * @param string $currency Currency.
	 * @return array<string,mixed>
	 */
	private static function unavailable_data( string $metal, string $currency ): array {
		return array(
			'metal'           => $metal,
			'metal_label'     => GoldAPI_API_Client::metal_label( $metal ),
			'currency'        => $currency,
			'price'           => null,
			'price_formatted' => __( 'Price temporarily unavailable', 'goldapi-live-price-widgets' ),
			'timestamp'       => time(),
			'updated'         => '',
			'ch'              => null,
			'chp'             => null,
		);
	}

	/**
	 * Returns unavailable placeholder value data.
	 *
	 * @param string $karat Karat.
	 * @param string $unit Weight unit.
	 * @param float  $weight Weight.
	 * @return array<string,mixed>
	 */
	private static function unavailable_weight_value( string $karat, string $unit, float $weight ): array {
		$units  = GoldAPI_API_Client::weight_units();
		$karats = GoldAPI_API_Client::supported_karats();

		return array(
			'karat'                    => $karat,
			'karat_label'              => (string) $karats[ $karat ]['label'],
			'unit'                     => $unit,
			'unit_label'               => (string) $units[ $unit ]['label'],
			'unit_grams'               => (float) $units[ $unit ]['grams'],
			'weight'                   => $weight,
			'price_per_gram_formatted' => __( 'Unavailable', 'goldapi-live-price-widgets' ),
			'unit_price_formatted'     => __( 'Unavailable', 'goldapi-live-price-widgets' ),
			'total_formatted'          => __( 'Price temporarily unavailable', 'goldapi-live-price-widgets' ),
		);
	}

	/**
	 * Returns supported calculator purity options.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function purity_options(): array {
		return array(
			'24k' => array(
				'label' => '24k / 100%',
				'value' => 1,
			),
			'999' => array(
				'label' => '999 / 99.9%',
				'value' => 0.999,
			),
			'995' => array(
				'label' => '995 / 99.5%',
				'value' => 0.995,
			),
			'23k' => array(
				'label' => '23k / 95.8%',
				'value' => 23 / 24,
			),
			'22k' => array(
				'label' => '22k / 91.6%',
				'value' => 22 / 24,
			),
			'21k' => array(
				'label' => '21k / 87.5%',
				'value' => 21 / 24,
			),
			'20k' => array(
				'label' => '20k / 83.3%',
				'value' => 20 / 24,
			),
			'18k' => array(
				'label' => '18k / 75.0%',
				'value' => 0.75,
			),
			'16k' => array(
				'label' => '16k / 66.7%',
				'value' => 16 / 24,
			),
			'14k' => array(
				'label' => '14k / 58.5%',
				'value' => 0.585,
			),
			'10k' => array(
				'label' => '10k / 41.7%',
				'value' => 10 / 24,
			),
		);
	}

	/**
	 * Sanitizes calculator purity.
	 *
	 * @param string $purity Raw purity.
	 * @return float
	 */
	private static function sanitize_purity( string $purity ): float {
		$value = (float) $purity;

		if ( $value > 1 ) {
			$value = $value / 100;
		}

		return max( 0.000001, min( 1, $value ) );
	}

	/**
	 * Finds purity option key from numeric value.
	 *
	 * @param float $value Purity ratio.
	 * @return string
	 */
	private static function purity_key_from_value( float $value ): string {
		foreach ( self::purity_options() as $key => $purity ) {
			if ( abs( (float) $purity['value'] - $value ) < 0.00001 ) {
				return $key;
			}
		}

		return '999';
	}

	/**
	 * Determines branding visibility.
	 *
	 * @param string              $attribute Shortcode show_branding attribute.
	 * @param bool                $api_key_mode Whether authenticated mode is active.
	 * @param array<string,mixed> $options Plugin options.
	 * @return bool
	 */
	private static function should_show_branding( string $attribute, bool $api_key_mode, array $options ): bool {
		if ( ! $api_key_mode ) {
			return true;
		}

		if ( ! empty( $options['hide_branding'] ) ) {
			return false;
		}

		return 'no' !== strtolower( sanitize_text_field( $attribute ) );
	}

	/**
	 * Returns branding HTML.
	 *
	 * @param bool $show_branding Whether branding should render.
	 * @return string
	 */
	private static function branding_html( bool $show_branding ): string {
		if ( ! $show_branding ) {
			return '';
		}

		return sprintf(
			'<div class="goldapi-branding"><a href="%s" target="_blank" rel="noopener noreferrer"><span>%s</span><img src="%s" alt="" aria-hidden="true" loading="lazy" /><span>%s</span></a></div>',
			esc_url( GoldAPI_API_Client::BRANDING_URL ),
			esc_html__( 'Powered by', 'goldapi-live-price-widgets' ),
			esc_url( GOLDAPI_LIVE_PRICE_WIDGETS_URL . 'assets/goldapi-logo.svg' ),
			esc_html__( 'GoldAPI.io', 'goldapi-live-price-widgets' )
		);
	}

	/**
	 * Formats change text.
	 *
	 * @param array<string,mixed> $data Price data.
	 * @return string
	 */
	private static function format_change( array $data ): string {
		if ( null === $data['ch'] && null === $data['chp'] ) {
			return '';
		}

		$change = null !== $data['ch'] ? number_format_i18n( (float) $data['ch'], 2 ) : '';
		$chp    = null !== $data['chp'] ? number_format_i18n( (float) $data['chp'], 2 ) . '%' : '';

		return trim( $change . ' ' . $chp );
	}
}
