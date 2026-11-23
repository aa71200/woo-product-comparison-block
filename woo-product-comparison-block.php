<?php
/**
 * Plugin Name: CodexaWP Product Comparison Block
 * Description: A server-rendered comparison of up to three WooCommerce products.
 * Version: 1.1.0
 * Requires Plugins: woocommerce
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 * Text Domain: woo-product-comparison-block
 */
declare(strict_types=1);

namespace CodexaWP\Comparison;
defined( 'ABSPATH' ) || exit;

add_action( 'init', static function (): void {
	wp_register_script( 'codexawp-comparison-edit', plugins_url( 'editor.js', __FILE__ ), [
		'wp-api-fetch', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-html-entities', 'wp-i18n', 'wp-server-side-render',
	], '1.1.0', true );
	wp_set_script_translations( 'codexawp-comparison-edit', 'woo-product-comparison-block' );
	wp_register_style( 'codexawp-comparison', plugins_url( 'style.css', __FILE__ ), [], '1.2.0' );
	register_block_type( __DIR__, [ 'render_callback' => __NAMESPACE__ . '\render' ] );
} );

function render( array $attributes ): string {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}
	$selection = $attributes['productIds'] ?? [];
	$ids = [];
	foreach ( is_array( $selection ) ? $selection : [] as $candidate ) {
		if ( is_int( $candidate ) || ( is_string( $candidate ) && ctype_digit( $candidate ) ) ) {
			$id = (int) $candidate;
			if ( $id > 0 ) {
				$ids[ $id ] = $id;
			}
		}
	}
	$ids = array_slice( $ids, 0, 3 );
	$products = [];
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product && 'publish' === $product->get_status() && $product->is_visible() ) {
			$products[] = $product;
		}
	}
	if ( count( $products ) < 2 ) {
		return '<p ' . get_block_wrapper_attributes() . '>' . esc_html__( 'Select at least two available products to compare.', 'woo-product-comparison-block' ) . '</p>';
	}
	$rows = [
		__( 'Price', 'woo-product-comparison-block' ) => static fn( $product ): string => wp_kses_post( $product->get_price_html() ) ?: '—',
		__( 'Availability', 'woo-product-comparison-block' ) => static fn( $product ): string => $product->is_in_stock()
			? '<span class="compare-stock compare-stock-available">' . esc_html__( 'In stock', 'woo-product-comparison-block' ) . '</span>'
			: '<span class="compare-stock">' . esc_html__( 'Out of stock', 'woo-product-comparison-block' ) . '</span>',
		__( 'Features', 'woo-product-comparison-block' ) => static function ( $product ): string {
			$parts = [];
			foreach ( $product->get_attributes() as $attribute ) {
				if ( $attribute instanceof \WC_Product_Attribute && $attribute->get_visible() ) {
					$name = $attribute->get_name();
					$value = $product->get_attribute( $name );
					if ( $value ) {
						$parts[] = wc_attribute_label( $name ) . ': ' . $value;
					}
				}
			}
			return esc_html( $parts ? implode( ' · ', $parts ) : '—' );
		},
	];
	ob_start();
	?>
	<section <?php echo get_block_wrapper_attributes(); ?> >
		<div class="codexawp-comparison-scroll" data-products="<?php echo esc_attr( count( $products ) ); ?>" role="region" aria-label="<?php esc_attr_e( 'Product comparison', 'woo-product-comparison-block' ); ?>" tabindex="0">
			<table aria-label="<?php esc_attr_e( 'Compare products', 'woo-product-comparison-block' ); ?>">
				<caption><span class="compare-title"><?php esc_html_e( 'Compare products', 'woo-product-comparison-block' ); ?></span><span class="compare-count"><?php
					/* translators: %d: Number of products in the comparison. */
					echo esc_html( sprintf( _n( '%d product', '%d products', count( $products ), 'woo-product-comparison-block' ), count( $products ) ) );
				?></span></caption>
				<thead><tr><th scope="col"><?php esc_html_e( 'Feature', 'woo-product-comparison-block' ); ?></th>
				<?php foreach ( $products as $product ) : ?>
					<th scope="col">
						<?php if ( $product->get_image_id() ) : ?>
							<?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', [ 'class' => 'compare-thumbnail', 'alt' => '' ] ) ); ?>
						<?php endif; ?>
						<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
					</th>
				<?php endforeach; ?></tr></thead>
				<tbody><?php foreach ( $rows as $label => $output ) : ?>
					<tr><th scope="row"><?php echo esc_html( $label ); ?></th>
					<?php foreach ( $products as $product ) : ?>
						<td><?php echo wp_kses_post( $output( $product ) ); ?></td>
					<?php endforeach; ?></tr>
				<?php endforeach; ?></tbody>
			</table>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
