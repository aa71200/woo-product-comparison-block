<?php
declare(strict_types=1);

if ( PHP_SAPI !== 'cli' || ! getenv( 'CODEXAWP_TEST_WP_LOAD' ) ) {
	throw new RuntimeException( 'Set CODEXAWP_TEST_WP_LOAD to a disposable WordPress installation.' );
}
require getenv( 'CODEXAWP_TEST_WP_LOAD' );
if ( 'local' !== wp_get_environment_type() || ! function_exists( 'WC' ) ) {
	throw new RuntimeException( 'This fixture requires local WordPress with WooCommerce active.' );
}

function check( bool $ok, string $message ): void {
	if ( ! $ok ) {
		throw new RuntimeException( $message );
	}
	echo "PASS: $message\n";
}

$create = static function ( string $price ): int {
	$product = new WC_Product_Simple();
	$product->set_name( 'Comparison sample ' . $price );
	$product->set_regular_price( $price );
	$product->set_status( 'publish' );
	$product->save();
	return $product->get_id();
};
$a = $create( '21.34' );
$b = $create( '56.78' );
$attrs = [ 'productIds' => [ $a, $b ] ];
$html = \CodexaWP\Comparison\render( $attrs );
check( str_contains( $html, '21.34' ) && str_contains( $html, '56.78' ) && str_contains( $html, 'scope="row"' ), 'Renders price and semantic table headers' );
$product = wc_get_product( $b );
$product->set_regular_price( '64.35' );
$product->set_price( '64.35' );
$product->save();
check( str_contains( \CodexaWP\Comparison\render( $attrs ), '64.35' ), 'Rendering reads the current price' );
$product->set_status( 'draft' );
$product->save();
check( str_contains( \CodexaWP\Comparison\render( $attrs ), 'Select at least two' ), 'Unpublished product produces a graceful fallback' );
