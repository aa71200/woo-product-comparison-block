# Product Comparison Block

An original CodexaWP Gutenberg block for comparing up to three published, visible WooCommerce products. PHP renders current price, availability and public attributes every time the page is requested.

## Storefront preview

<p align="center">
  <a href="docs/comparison.png"><img src="docs/comparison.png" width="644" alt="Two WooCommerce products compared by price, availability and features"></a>
  <br>
  <em>Storefront block · current price, availability and features side by side</em>
</p>

## Try it

Activate on a test shop and add **Product Comparison** in the editor. Enter the IDs of two or three sample products in the labeled controls. Publish a test page to see the semantic table, including a named scroll region on narrow screens.

## Verification

Activate this plugin on a disposable WordPress site with WooCommerce active and `WP_ENVIRONMENT_TYPE` set to `local`. Set `CODEXAWP_TEST_WP_LOAD` to the absolute path to that site's `wp-load.php`, then run `php tests/check.php` from this repository.

The fixture checks semantic table headers, current prices after product edits, and a graceful fallback when fewer than two products remain available. Browser checks verified the keyboard-focusable mobile table and product picker in the editor.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
