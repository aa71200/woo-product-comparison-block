'use strict';

(function (wp) {
	const { createElement: h, Fragment } = wp.element;
	const { useBlockProps } = wp.blockEditor;
	const renderPreview = wp.serverSideRender.ServerSideRender || wp.serverSideRender.default || wp.serverSideRender;

	wp.blocks.registerBlockType('codexawp/product-comparison', {
		edit: ({ attributes, setAttributes }) => {
			const blockProps = useBlockProps();
			const ids = attributes.productIds || [];

			function setId(index, value) {
				const next = Array.from({ length: 3 }, (_, i) => ids[i] || 0);
				next[index] = /^[0-9]+$/.test(value) ? Number(value) : 0;
				setAttributes({ productIds: next });
			}

			return h('div', blockProps,
				h(Fragment, null, h('p', null, 'Enter the WooCommerce product IDs to compare.'),
					...Array.from({ length: 3 }, (_, index) => h(wp.components.TextControl, {
						key: index, label: 'Product ' + (index + 1) + ' ID', type: 'number', min: 1,
						value: ids[index] || '', onChange: value => setId(index, value),
					}))
				),
				h(renderPreview, { block: 'codexawp/product-comparison', attributes })
			);
		},
		save: () => null,
	});
})(window.wp);
