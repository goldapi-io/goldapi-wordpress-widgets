wp.blocks.registerBlockType("goldapi/live-price", {
	title: "GoldAPI Price",
	icon: "chart-line",
	category: "widgets",
	attributes: {
		metal: { type: "string", default: "XAU" },
		currency: { type: "string", default: "USD" },
		layout: { type: "string", default: "card" },
		refresh: { type: "number", default: 1800 },
	},
	edit: function (props) {
		var el = wp.element.createElement;
		var attrs = props.attributes;

		function set(name, value) {
			var update = {};
			update[name] = value;
			props.setAttributes(update);
		}

		return el(
			"div",
			{ className: "goldapi-block-editor" },
			el("p", {}, "GoldAPI live price widget"),
			el(wp.components.TextControl, {
				label: "Metal",
				value: attrs.metal,
				onChange: function (value) {
					set("metal", value);
				},
			}),
			el(wp.components.TextControl, {
				label: "Currency",
				value: attrs.currency,
				onChange: function (value) {
					set("currency", value);
				},
			}),
			el(wp.components.SelectControl, {
				label: "Layout",
				value: attrs.layout,
				options: [
					{ label: "Card", value: "card" },
					{ label: "Inline", value: "inline" },
					{ label: "Ticker", value: "ticker" },
					{ label: "Table", value: "table" },
				],
				onChange: function (value) {
					set("layout", value);
				},
			}),
			el(wp.components.TextControl, {
				label: "Refresh seconds",
				type: "number",
				value: attrs.refresh,
				onChange: function (value) {
					set("refresh", parseInt(value, 10) || 1800);
				},
			})
		);
	},
	save: function () {
		return null;
	},
});
