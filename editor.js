'use strict';

(function (wp) {
	const { createElement: h, useEffect, useState } = wp.element;
	const { __, sprintf } = wp.i18n;
	const renderPreview = wp.serverSideRender.ServerSideRender || wp.serverSideRender.default || wp.serverSideRender;

	function products(params, signal) {
		return wp.apiFetch({ path: '/wc/store/v1/products?' + new URLSearchParams(params), signal });
	}

	function option(product) {
		return { value: String(product.id), label: wp.htmlEntities.decodeEntities(product.name) };
	}

	function ProductField({ index, ids, setId }) {
		const id = ids[index] || 0;
		const [query, setQuery] = useState('');
		const [selected, setSelected] = useState(null);
		const [matches, setMatches] = useState([]);
		const [failed, setFailed] = useState(false);

		useEffect(() => {
			if (!id) {
				setSelected(null);
				return;
			}
			const request = new AbortController();
			products({ include: id, catalog_visibility: 'catalog' }, request.signal)
				.then(items => { if (!request.signal.aborted) setSelected(items.length ? option(items[0]) : null); })
				.catch(() => { if (!request.signal.aborted) setSelected(null); });
			return () => request.abort();
		}, [id]);

		useEffect(() => {
			const request = new AbortController();
			setMatches([]);
			setFailed(false);
			const term = query.trim();
			const timer = setTimeout(() => {
				if (term.length < 2) return;
				products({ search: term, per_page: 12, catalog_visibility: 'catalog' }, request.signal)
					.then(items => { if (!request.signal.aborted) setMatches(items.map(option)); })
					.catch(() => { if (!request.signal.aborted) setFailed(true); });
			}, 250);
			return () => { clearTimeout(timer); request.abort(); };
		}, [query]);

		const options = matches.filter(item => !ids.some((other, slot) => slot !== index && String(other) === item.value));
		if (id && !options.some(item => item.value === String(id))) {
			options.unshift(selected?.value === String(id) ? selected : {
				value: String(id),
				label: sprintf(__('Product #%d', 'woo-product-comparison-block'), id),
			});
		}
		return h(wp.components.ComboboxControl, {
			label: sprintf(__('Product %d', 'woo-product-comparison-block'), index + 1),
			help: failed
				? __('Product search is temporarily unavailable.', 'woo-product-comparison-block')
				: __('Type at least two characters to search.', 'woo-product-comparison-block'),
			value: id ? String(id) : null,
			options,
			onFilterValueChange: setQuery,
			onChange: value => setId(index, Number(value) || 0),
		});
	}

	wp.blocks.registerBlockType('codexawp/product-comparison', {
		edit: ({ attributes, setAttributes }) => {
			const blockProps = wp.blockEditor.useBlockProps();
			const ids = attributes.productIds || [];
			function setId(index, value) {
				if (value && ids.some((id, slot) => slot !== index && id === value)) return;
				const next = Array.from({ length: 3 }, (_, slot) => slot === index ? value : ids[slot] || 0);
				setAttributes({ productIds: next });
			}
			return h('div', blockProps,
				...Array.from({ length: 3 }, (_, index) => h(ProductField, { key: index, index, ids, setId })),
				h(renderPreview, { block: 'codexawp/product-comparison', attributes })
			);
		},
		save: () => null,
	});
})(window.wp);
