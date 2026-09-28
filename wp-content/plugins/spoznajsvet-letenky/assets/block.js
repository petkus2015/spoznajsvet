/* Blok „Letenky“ pre editor (bez build kroku). */
(function (wp) {
	var el = wp.element.createElement;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var C = wp.components;
	var ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType('spoznajsvet/letenky', {
		apiVersion: 3,
		title: 'Letenky',
		description: 'Najlacnejšie aktuálne letenky (aktualizácia každých 30 minút).',
		category: 'widgets',
		icon: 'airplane',
		keywords: ['letenky', 'lety', 'ryanair', 'wizzair'],
		attributes: {
			skupina: { type: 'string', default: 'vsetko' },
			limit: { type: 'number', default: 12 },
			nadpis: { type: 'boolean', default: true },
			kompaktne: { type: 'boolean', default: false }
		},
		edit: function (props) {
			var a = props.attributes;
			var set = props.setAttributes;
			return el('div', useBlockProps(),
				el(InspectorControls, {},
					el(C.PanelBody, { title: 'Nastavenia' },
						el(C.SelectControl, {
							label: 'Čo zobraziť',
							value: a.skupina,
							options: [
								{ label: 'Všetko', value: 'vsetko' },
								{ label: 'Exotika (Bangkok, Dubaj, Abu Dhabi)', value: 'exotika' },
								{ label: 'Európske mestá', value: 'europa' }
							],
							onChange: function (v) { set({ skupina: v }); }
						}),
						el(C.RangeControl, {
							label: 'Počet európskych destinácií',
							min: 3, max: 60, value: a.limit,
							onChange: function (v) { set({ limit: v }); }
						}),
						el(C.ToggleControl, {
							label: 'Zobraziť nadpisy',
							checked: a.nadpis,
							onChange: function (v) { set({ nadpis: v }); }
						}),
						el(C.ToggleControl, {
							label: 'Kompaktné zobrazenie',
							checked: a.kompaktne,
							onChange: function (v) { set({ kompaktne: v }); }
						})
					)
				),
				el(C.Disabled, {}, el(ServerSideRender, { block: 'spoznajsvet/letenky', attributes: a }))
			);
		},
		save: function () { return null; }
	});
})(window.wp);
