# GoldAPI Live Gold Price Widgets

Production-ready WordPress plugin for embedding live precious metals prices from [GoldAPI.io](https://www.goldapi.io/) using shortcodes and a lightweight REST-powered refresh script.

The plugin works immediately after activation without an API key by using GoldAPI.io static cached endpoints:

```text
https://www.goldapi.io/api/static/XAU/AUD
```

Advanced users can add a GoldAPI.io API key under **Settings > GoldAPI Widgets** to use authenticated endpoints and refresh as frequently as every 10 seconds. Free mode refreshes no faster than every 30 minutes (1800 seconds).

## Examples

![](https://private-user-images.githubusercontent.com/13550565/593772541-edc6dc67-3d28-4da2-b56b-16523adab830.png?jwt=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJnaXRodWIuY29tIiwiYXVkIjoicmF3LmdpdGh1YnVzZXJjb250ZW50LmNvbSIsImtleSI6ImtleTUiLCJleHAiOjE3NzkwNjUyMzUsIm5iZiI6MTc3OTA2NDkzNSwicGF0aCI6Ii8xMzU1MDU2NS81OTM3NzI1NDEtZWRjNmRjNjctM2QyOC00ZGEyLWI1NmItMTY1MjNhZGFiODMwLnBuZz9YLUFtei1BbGdvcml0aG09QVdTNC1ITUFDLVNIQTI1NiZYLUFtei1DcmVkZW50aWFsPUFLSUFWQ09EWUxTQTUzUFFLNFpBJTJGMjAyNjA1MTglMkZ1cy1lYXN0LTElMkZzMyUyRmF3czRfcmVxdWVzdCZYLUFtei1EYXRlPTIwMjYwNTE4VDAwNDIxNVomWC1BbXotRXhwaXJlcz0zMDAmWC1BbXotU2lnbmF0dXJlPThmZmYxMDI2YmVhNDJiMTZmZjhjYmUzNjEyMzVmMTRkNDQ0ZmE2NzQyMjQxYTcyMmM2MWM2MWY3MjEwYmM5MzMmWC1BbXotU2lnbmVkSGVhZGVycz1ob3N0JnJlc3BvbnNlLWNvbnRlbnQtdHlwZT1pbWFnZSUyRnBuZyJ9.iGhLfGs--IIvOdw7xtoPjCWgSBiZAVQiGXol2uSotV4)

## Shortcodes

Single widget:

```text
[goldapi_price metal="XAU" currency="AUD"]
```

Inline layout:

```text
[goldapi_price metal="XAG" currency="USD" layout="inline"]
```

Ticker layout:

```text
[goldapi_price metal="XAU" currency="AED" layout="ticker" refresh="1800"]
```

Multi-metal table:

```text
[goldapi_prices metals="XAU,XAG,XPT,XPD" currency="USD" layout="table"]
```

Gold karat and weight widget:

```text
[goldapi_gold_value currency="AUD" karat="22k" unit="ozt" weight="1"]
[goldapi_gold_value currency="USD" karat="18k" unit="g" weight="10"]
```

Supported currencies:

```text
AED, AUD, BTC, CAD, CHF, CNY, CZK, EGP, EUR, GBP, HKD, INR, JOD, JPY, KRW, KWD, MXN, MYR, OMR, PLN, RUB, SAR, SGD, THB, USD, ZAR
```

Supported gold karats:

```text
24k, 22k, 21k, 20k, 18k, 16k, 14k, 10k
```

Supported weight units:

```text
g, kg, ozt, dwt, ct, tola, tael, grain, baht, mace, chi
```

Scrap metal calculator:

```text
[goldapi_calculator metal="XAU" currency="USD" weight="1" unit="g" dealer_discount="0" quantity="1"]
```

The calculator lets visitors select metal, currency, weight, weight unit, purity, dealer discount, and quantity.
Purity includes standard gold karats: 24k, 22k, 21k, 20k, 18k, 16k, 14k, and 10k.

## Local Installation

Copy the plugin into a local WordPress install:

```bash
cp -R goldapi-wordpress-widget /path/to/wordpress/wp-content/plugins/
```

Activate with WP-CLI:

```bash
cd /path/to/wordpress
wp plugin activate goldapi-wordpress-widget
```

Or activate **GoldAPI Live Gold Price Widgets** in **Plugins > Installed Plugins**.

## Local Testing

Create a test page:

```bash
wp post create --post_type=page --post_status=publish --post_title="Gold Price" --post_content='[goldapi_price metal="XAU" currency="AUD"]'
```

Test the REST endpoint:

```bash
curl "http://localhost:8888/wp-json/goldapi/v1/price?metal=XAU&currency=AUD"
```

Set authenticated mode from WP-CLI:

```bash
wp option update goldapi_live_price_widgets_options '{"api_key":"your_goldapi_token_here","refresh_interval":10,"hide_branding":1,"default_currency":"USD","default_layout":"card"}' --format=json
```

Create a zip for upload:

```bash
cd /path/to/plugins
zip -r goldapi-wordpress-widget.zip goldapi-wordpress-widget -x "goldapi-wordpress-widget/.git/*" "goldapi-wordpress-widget/node_modules/*" "goldapi-wordpress-widget/vendor/*"
```

## Manual Acceptance Check

- Activate plugin without an API key.
- Add `[goldapi_price metal="XAU" currency="AUD"]` to a page.
- Confirm the widget renders and shows “Powered by GoldAPI.io”.
- Confirm free mode refresh is clamped to at least 1800 seconds.
- Add an API key in **Settings > GoldAPI Widgets**.
- Set refresh interval to 10 seconds.
- Confirm the REST endpoint returns safe JSON and does not expose the API key.
- Confirm branding can be hidden only in API key mode.
- Add `[goldapi_gold_value currency="AUD" karat="22k" unit="ozt" weight="1"]` and confirm gram/unit/total values render.
- Change the weight input in the widget and confirm the total updates without a page reload.
- Add `[goldapi_calculator metal="XAU" currency="USD"]` and confirm changing metal/currency fetches new data while weight/unit/discount/quantity recalculate instantly.

## Developer Filters

- `goldapi_public_endpoint_url`
- `goldapi_authenticated_endpoint_url`
- `goldapi_cache_ttl`
- `goldapi_supported_metals`
- `goldapi_supported_currencies`

## Notes

The plugin intentionally has no build step. PHP, CSS, vanilla JavaScript, and a build-free Gutenberg block script are committed directly.
