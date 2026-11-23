<?php
/**
 * Plugin Name: CodexaWP Product Comparison Block
 * Description: A server-rendered comparison of up to three WooCommerce products.
 * Version: 1.0.0
 * Requires Plugins: woocommerce
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 */
declare(strict_types=1);

namespace CodexaWP\Comparison;
defined( 'ABSPATH' ) || exit;

add_action( 'init', static function (): void {
	wp_register_script( 'codexawp-comparison-edit', plugins_url( 'editor.js', __FILE__ ), [
		'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-server-side-render',
	], '1.0.0', true );
	wp_register_style( 'codexawp-comparison', plugins_url( 'style.css', __FILE__ ), [], '1.1.0' );
	register_block_type( __DIR__, [ 'render_callback' => __NAMESPACE__ . '\render' ] );
} );

function render( array $attributes ): string {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}
	$ids = array_slice( array_unique( array_filter( array_map( 'absint', $attributes['productIds'] ?? [] ) ) ), 0, 3 );
	$products = [];
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( $product && 'publish' === $product->get_status() && $product->is_visible() ) {
			$products[] = $product;
		}
	}
	if ( count( $products ) < 2 ) {
		return '<p ' . get_block_wrapper_attributes() . '>Select at least two available products to compare.</p>';
	}
	$rows = [
		'Price' => static fn( $product ): string => wp_kses_post( $product->get_price_html() ) ?: '—',
		'Availability' => static fn( $product ): string => $product->is_in_stock()
			? '<span class="compare-stock compare-stock-available">In stock</span>'
			: '<span class="compare-stock">Out of stock</span>',
		'Features' => static function ( $product ): string {
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
		<div class="codexawp-comparison-scroll" role="region" aria-label="Product comparison" tabindex="0">
			<table aria-label="Compare products">
				<caption><span class="compare-title">Compare products</span><span class="compare-count"><?php echo esc_html( sprintf( '%d products', count( $products ) ) ); ?></span></caption>
				<thead><tr><th scope="col">Feature</th>
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
