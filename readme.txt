=== GoldAPI Live Gold Price Widgets ===
Contributors: goldapi
Tags: gold price, silver price, precious metals, shortcode, live price, widgets
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Embed live GoldAPI.io precious metals prices using shortcodes and a lightweight refresh widget including scrap gold/silver calculator. No API key required by default.

== Description ==

GoldAPI Live Gold Price Widgets lets website owners add live precious metals prices to posts, pages, and widget areas.

The plugin works immediately after activation using GoldAPI.io static cached endpoints:

`https://www.goldapi.io/api/static/XAU/AUD`

Advanced users can add a GoldAPI.io API key under Settings > GoldAPI Widgets to use authenticated real-time endpoints:

`https://www.goldapi.io/api/XAU/AUD`

Free mode refreshes no faster than every 30 minutes (180 seconds). API key mode supports refresh intervals as low as 10 seconds.

Supported metals:

* XAU - Gold
* XAG - Silver
* XPT - Platinum
* XPD - Palladium

Supported currencies:

* AUD
* AED
* BTC
* CAD
* CHF
* CNY
* CZK
* EGP
* EUR
* GBP
* HKD
* INR
* JOD
* JPY
* KRW
* KWD
* MXN
* MYR
* OMR
* PLN
* RUB
* SAR
* SGD
* THB
* USD
* ZAR

== Installation ==

1. Upload the `goldapi-wordpress-widget` folder to `/wp-content/plugins/`.
2. Activate **GoldAPI Live Gold Price Widgets** from the WordPress Plugins screen.
3. Add a shortcode to a post or page:

`[goldapi_price metal="XAU" currency="AUD"]`

The plugin works without configuration in static cached mode.

Optional API key setup:

1. Go to Settings > GoldAPI Widgets.
2. Enter your GoldAPI.io API key.
3. Set refresh interval, default currency, default layout, and branding preference.
4. Save settings.

== Shortcodes ==

Single price:

`[goldapi_price metal="XAU" currency="AUD"]`

Inline layout:

`[goldapi_price metal="XAG" currency="USD" layout="inline"]`

Ticker layout:

`[goldapi_price metal="XAU" currency="AED" layout="ticker" refresh="1800"]`

Multi-metal table:

`[goldapi_prices metals="XAU,XAG,XPT,XPD" currency="USD" layout="table"]`

Gold karat and weight widget:

`[goldapi_gold_value currency="AUD" karat="22k" unit="ozt" weight="1"]`

Another gold value example:

`[goldapi_gold_value currency="USD" karat="18k" unit="g" weight="10"]`

Scrap metal calculator:

`[goldapi_calculator metal="XAU" currency="USD" weight="1" unit="g" dealer_discount="0" quantity="1"]`

Supported attributes for `[goldapi_price]`:

* `metal` - XAU, XAG, XPT, or XPD
* `currency` - AED, AUD, BTC, CAD, CHF, CNY, CZK, EGP, EUR, GBP, HKD, INR, JOD, JPY, KRW, KWD, MXN, MYR, OMR, PLN, RUB, SAR, SGD, THB, USD, or ZAR
* `layout` - card, inline, ticker, or table
* `refresh` - seconds, clamped to 1800 minimum in free mode and 10 minimum in API key mode
* `show_branding` - yes or no. No only works in API key mode.
* `decimals` - number of decimal places, default 2

Supported attributes for `[goldapi_calculator]`:

* `metal` - XAU, XAG, XPT, or XPD
* `currency` - supported currency symbol
* `weight` - numeric item weight
* `unit` - g, kg, ozt, dwt, ct, tola, tael, grain, baht, mace, or chi
* `dealer_discount` - dealer discount or margin percent
* `quantity` - item quantity
* `purity` - purity ratio such as 0.999, 0.916, 0.75, or 0.585. The dropdown includes 24k, 22k, 21k, 20k, 18k, 16k, 14k, and 10k.
* `refresh` - seconds, clamped to 1800 minimum in free mode and 10 minimum in API key mode
* `show_branding` - yes or no. No only works in API key mode.
* `decimals` - number of decimal places, default 2

Supported attributes for `[goldapi_gold_value]`:

* `currency` - supported currency symbol
* `karat` - 24k, 22k, 21k, 20k, 18k, 16k, 14k, or 10k
* `unit` - g, kg, ozt, dwt, ct, tola, tael, grain, baht, mace, or chi
* `weight` - numeric unit quantity
* `refresh` - seconds, clamped to 1800 minimum in free mode and 10 minimum in API key mode
* `show_branding` - yes or no. No only works in API key mode.
* `decimals` - number of decimal places, default 2

== Frequently Asked Questions ==

= Does this require a GoldAPI.io API key? =

No. The plugin works by default with GoldAPI.io static cached endpoints.

= What changes when I add an API key? =

The plugin uses authenticated GoldAPI.io endpoints and supports refresh intervals as low as 10 seconds.

= Is my API key exposed to visitors? =

No. Browser JavaScript calls the local WordPress REST endpoint. GoldAPI.io requests happen server-side with `wp_remote_get`.

= Can I hide the Powered by GoldAPI.io link? =

Only in API key mode. Branding is visible in free static cached mode.

== Developer Filters ==

`goldapi_public_endpoint_url`

Override the static cached endpoint URL.

`goldapi_authenticated_endpoint_url`

Override the authenticated endpoint URL.

`goldapi_cache_ttl`

Override the transient cache TTL.

`goldapi_supported_metals`

Override supported metals.

`goldapi_supported_currencies`

Override supported currencies.

== Changelog ==

= 1.0.0 =

Initial release.
