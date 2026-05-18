(function () {
	"use strict";

	function ready(callback) {
		if (document.readyState !== "loading") {
			callback();
			return;
		}

		document.addEventListener("DOMContentLoaded", callback);
	}

	function updateSource(widget, source) {
		if (!window.GoldAPIWidgets || !window.GoldAPIWidgets.restUrl) {
			return;
		}

		var metal = source.getAttribute("data-metal") || "XAU";
		var currency = source.getAttribute("data-currency") || "USD";
		var decimals = widget.getAttribute("data-decimals") || "2";
		var refresh = widget.getAttribute("data-refresh") || "60";
		var url = new URL(window.GoldAPIWidgets.restUrl);

		url.searchParams.set("metal", metal);
		url.searchParams.set("currency", currency);
		url.searchParams.set("decimals", decimals);
		url.searchParams.set("refresh", refresh);

		source.classList.add("goldapi-is-loading");
		source.classList.remove("goldapi-has-error");
		setStatusLabel(source, "Updating");

		fetch(url.toString(), {
			method: "GET",
			credentials: "same-origin",
			headers: {
				Accept: "application/json",
				"X-WP-Nonce": window.GoldAPIWidgets.nonce || "",
			},
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error("Request failed");
				}

				return response.json();
			})
			.then(function (data) {
				setText(source, ".goldapi-price-value", data.price_formatted);
				setText(source, ".goldapi-updated", data.updated);
				setText(source, ".goldapi-change", formatChange(data));
				updateGoldValue(source, data, parseInt(decimals, 10) || 2);
				updateCalculatorFromData(source, data, parseInt(decimals, 10) || 2);
				source.classList.remove("goldapi-has-error");
				setStatusLabel(source, "");
			})
			.catch(function () {
				source.classList.add("goldapi-has-error");
				setStatusLabel(source, "Price temporarily unavailable");
			})
			.finally(function () {
				source.classList.remove("goldapi-is-loading");
			});
	}

	function setText(root, selector, value) {
		var element = root.querySelector(selector);
		if (!element || value === undefined || value === null) {
			return;
		}

		element.textContent = String(value);
	}

	function setStatusLabel(root, value) {
		var element = root.querySelector(".goldapi-status");
		if (!element) {
			return;
		}

		element.setAttribute("aria-label", value || "");
		element.setAttribute("title", value || "");
	}

	function formatChange(data) {
		var parts = [];

		if (typeof data.ch === "number") {
			parts.push(data.ch.toFixed(2));
		}

		if (typeof data.chp === "number") {
			parts.push(data.chp.toFixed(2) + "%");
		}

		return parts.join(" ");
	}

	function updateGoldValue(source, data, decimals) {
		var karat = source.getAttribute("data-karat");
		var unitGrams = parseFloat(source.getAttribute("data-unit-grams") || "0");
		var weight = parseFloat(source.getAttribute("data-weight") || "1");

		if (!karat || !unitGrams || typeof data.price !== "number") {
			return;
		}

		var gramPrice = getGramPrice(data, karat);
		var unitPrice = gramPrice * unitGrams;
		var total = unitPrice * weight;

		source.setAttribute("data-gram-price", String(gramPrice));
		source.setAttribute("data-unit-price", String(unitPrice));
		setText(source, ".goldapi-gram-price-value", formatNumber(gramPrice, decimals));
		setText(source, ".goldapi-unit-price-value", formatNumber(unitPrice, decimals));
		setText(source, ".goldapi-total-value", formatNumber(total, decimals));
	}

	function updateGoldValueFromInput(widget, source) {
		var input = source.querySelector(".goldapi-weight-input");
		var unitPrice = parseFloat(source.getAttribute("data-unit-price") || "0");
		var decimals = parseInt(widget.getAttribute("data-decimals") || "2", 10);
		var weight;

		if (!input || !unitPrice) {
			return;
		}

		weight = Math.max(0.000001, parseFloat(input.value || "1"));
		source.setAttribute("data-weight", String(weight));
		setText(source, ".goldapi-total-value", formatNumber(unitPrice * weight, decimals || 2));
	}

	function updateCalculatorFromData(source, data, decimals) {
		if (!source.classList.contains("goldapi-calculator-source") || typeof data.price !== "number") {
			return;
		}

		source.setAttribute("data-spot-price", String(data.price));
		setInputValue(source, ".goldapi-calculator-spot", formatNumber(data.price, decimals));
		updateCalculator(source, decimals);
	}

	function updateCalculator(source, decimals) {
		var spotPrice = parseFloat(source.getAttribute("data-spot-price") || "0");
		var currency = source.getAttribute("data-currency") || "USD";
		var unitSelect = source.querySelector(".goldapi-calculator-unit");
		var selectedUnit = unitSelect ? unitSelect.options[unitSelect.selectedIndex] : null;
		var unitGrams = selectedUnit ? parseFloat(selectedUnit.getAttribute("data-grams") || "1") : 1;
		var weight = readNumber(source, ".goldapi-calculator-weight", 1);
		var discount = Math.min(100, Math.max(0, readNumber(source, ".goldapi-calculator-discount", 0)));
		var quantity = Math.max(1, Math.floor(readNumber(source, ".goldapi-calculator-quantity", 1)));
		var purity = readNumber(source, ".goldapi-calculator-purity", 0.999);
		var pricePerGram = spotPrice / 31.1034768;
		var pureWeight = weight * unitGrams * purity * quantity;
		var beforeMargin = pricePerGram * pureWeight;
		var margin = beforeMargin * (discount / 100);
		var finalValue = Math.max(0, beforeMargin - margin);

		source.setAttribute("data-unit-grams", String(unitGrams));
		setText(source, ".goldapi-calculator-pure-weight", formatNumber(pureWeight, 4) + " g");
		setText(source, ".goldapi-calculator-before-margin", formatNumber(beforeMargin, decimals) + " " + currency);
		setText(source, ".goldapi-calculator-margin", formatNumber(margin, decimals) + " " + currency);
		setText(source, ".goldapi-calculator-final", formatNumber(finalValue, decimals) + " " + currency);
		setText(source, ".goldapi-calculator-price-gram", formatNumber(pricePerGram, 4) + " " + currency);
		setText(source, ".goldapi-calculator-price-ounce", formatNumber(spotPrice, decimals) + " " + currency);
		setText(source, ".goldapi-calculator-price-kg", formatNumber(pricePerGram * 1000, decimals) + " " + currency);
	}

	function readNumber(root, selector, fallback) {
		var field = root.querySelector(selector);
		var value = field ? parseFloat(field.value || field.getAttribute("value") || "") : NaN;

		return Number.isFinite(value) ? value : fallback;
	}

	function setInputValue(root, selector, value) {
		var field = root.querySelector(selector);
		if (!field) {
			return;
		}

		field.value = String(value);
	}

	function getGramPrice(data, karat) {
		var field = "price_gram_" + karat;

		if (typeof data[field] === "number") {
			return data[field];
		}

		return (data.price / 31.1034768) * (parseInt(karat, 10) / 24);
	}

	function formatNumber(value, decimals) {
		return value.toLocaleString(undefined, {
			minimumFractionDigits: decimals,
			maximumFractionDigits: decimals,
		});
	}

	function initWidget(widget) {
		var refresh = parseInt(widget.getAttribute("data-refresh") || "60", 10);
		var sources = widget.querySelectorAll(".goldapi-price-source");

		if (!sources.length || !refresh) {
			return;
		}

		sources.forEach(function (source) {
			var input = source.querySelector(".goldapi-weight-input");
			var calculatorFields = source.querySelectorAll(".goldapi-calculator-weight, .goldapi-calculator-unit, .goldapi-calculator-purity, .goldapi-calculator-discount, .goldapi-calculator-quantity");
			var metalField = source.querySelector(".goldapi-calculator-metal");
			var currencyField = source.querySelector(".goldapi-calculator-currency");

			if (input) {
				input.addEventListener("input", function () {
					updateGoldValueFromInput(widget, source);
				});
			}

			calculatorFields.forEach(function (field) {
				field.addEventListener("input", function () {
					updateCalculator(source, parseInt(widget.getAttribute("data-decimals") || "2", 10) || 2);
				});
				field.addEventListener("change", function () {
					updateCalculator(source, parseInt(widget.getAttribute("data-decimals") || "2", 10) || 2);
				});
			});

			if (metalField) {
				metalField.addEventListener("change", function () {
					source.setAttribute("data-metal", metalField.value || "XAU");
					setText(source, ".goldapi-calculator-pair", (metalField.value || "XAU") + "/" + (source.getAttribute("data-currency") || "USD"));
					updateSource(widget, source);
				});
			}

			if (currencyField) {
				currencyField.addEventListener("change", function () {
					source.setAttribute("data-currency", currencyField.value || "USD");
					setText(source, ".goldapi-calculator-pair", (source.getAttribute("data-metal") || "XAU") + "/" + (currencyField.value || "USD"));
					updateSource(widget, source);
				});
			}
		});

		window.setInterval(function () {
			sources.forEach(function (source) {
				updateSource(widget, source);
			});
		}, refresh * 1000);
	}

	ready(function () {
		document.querySelectorAll(".goldapi-widget[data-refresh]").forEach(initWidget);
	});
})();
