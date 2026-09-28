/* Spoznaj Svet – Letenky: filtre, zoradenie, „pred X min“ a automatická obnova bez reloadu. */
(function () {
	'use strict';

	function minutesAgo(ts) {
		var min = Math.floor((Date.now() / 1000 - ts) / 60);
		if (min < 1) return 'práve teraz';
		if (min < 60) return 'pred ' + min + ' min';
		var h = Math.floor(min / 60);
		if (h < 24) return 'pred ' + h + ' h';
		return 'pred ' + Math.floor(h / 24) + ' d';
	}

	function setPressed(group, btn) {
		group.querySelectorAll('button').forEach(function (b) {
			var on = b === btn;
			b.classList.toggle('is-active', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
	}

	function applyEurope(block, expand) {
		var list = block.querySelector('.svl-eu-list');
		if (!list) return;
		var origin = block.dataset.origin || 'all';
		var sort = block.dataset.sort || 'price';
		var limit = parseInt(block.dataset.limit, 10) || 12;
		if (expand) block.dataset.expanded = '1';
		var items = Array.prototype.slice.call(list.children);

		items.sort(function (a, b) {
			if (sort === 'date') {
				return a.dataset.date < b.dataset.date ? -1 : a.dataset.date > b.dataset.date ? 1 : a.dataset.price - b.dataset.price;
			}
			return a.dataset.price - b.dataset.price;
		});
		items.forEach(function (li) { list.appendChild(li); });

		var shown = 0;
		var matching = 0;
		items.forEach(function (li) {
			var match = origin === 'all' ? li.dataset.best === '1' : li.dataset.origin === origin;
			if (match) matching++;
			var visible = match && (block.dataset.expanded === '1' || shown < limit);
			li.hidden = !visible;
			if (visible) shown++;
		});
		var more = block.querySelector('[data-svl-more]');
		if (more) more.hidden = shown >= matching;
		block.classList.add('is-js');
	}

	function init(root) {
		(root || document).querySelectorAll('[data-svl-eu]').forEach(function (b) { applyEurope(b); });
		tick();
	}

	document.addEventListener('click', function (e) {
		var chip = e.target.closest('.svl-chip');
		if (chip) {
			var block = chip.closest('[data-svl-eu]');
			if (!block) return;
			if (chip.dataset.origin) block.dataset.origin = chip.dataset.origin;
			if (chip.dataset.sort) block.dataset.sort = chip.dataset.sort;
			block.dataset.expanded = '';
			setPressed(chip.parentElement, chip);
			applyEurope(block);
			return;
		}
		var more = e.target.closest('[data-svl-more]');
		if (more) applyEurope(more.closest('[data-svl-eu]'), true);
	});

	function tick() {
		var now = Date.now() / 1000;
		document.querySelectorAll('[data-svl]').forEach(function (sec) {
			var updated = parseInt(sec.dataset.updated, 10);
			var next = parseInt(sec.dataset.next, 10);
			var ago = sec.querySelector('[data-svl-ago]');
			if (ago && updated) ago.textContent = minutesAgo(updated);
			var nx = sec.querySelector('[data-svl-next]');
			if (nx && next) {
				var m = Math.ceil((next - now) / 60);
				nx.textContent = m > 0 ? 'o ' + m + ' min' : 'o chvíľu';
			}
			// Nové dáta by už mali byť k dispozícii → potichu načítame.
			if (next && now > next + 90 && !sec.dataset.loading && document.visibilityState === 'visible') {
				refresh(sec);
			}
		});
	}

	function refresh(sec) {
		var url = sec.dataset.rest;
		if (!url || !window.fetch) return;
		sec.dataset.loading = '1';
		fetch(url, { headers: { Accept: 'application/json' } })
			.then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
			.then(function (json) {
				if (!json || !json.html || json.updated <= parseInt(sec.dataset.updated, 10)) {
					// Zatiaľ nič nové – skúsime znova o 5 minút.
					sec.dataset.next = String(Math.floor(Date.now() / 1000) + 300 - 90);
					sec.dataset.loading = '';
					return;
				}
				var tpl = document.createElement('template');
				tpl.innerHTML = json.html.trim();
				var fresh = tpl.content.firstElementChild;
				sec.replaceWith(fresh);
				fresh.classList.add('svl--fresh');
				init(fresh.parentNode);
			})
			.catch(function () { sec.dataset.loading = ''; sec.dataset.next = String(Math.floor(Date.now() / 1000) + 300); });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { init(); });
	} else {
		init();
	}
	setInterval(tick, 30000);
	document.addEventListener('visibilitychange', tick);
})();
