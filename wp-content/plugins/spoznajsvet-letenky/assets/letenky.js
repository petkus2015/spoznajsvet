/* Spoznaj Svet – Letenky: filtre, zoradenie, „pred X min“, obnova na vyžiadanie (ceny max. 15 min) bez reloadu. */
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
	}

	// Ponuky sú tlačidlá (nie odkazy), aby prehliadač pri prejdení myšou neukazoval adresu v rohu.
	function go(btn) {
		var url = btn.getAttribute('data-svl-go');
		if (!url) return;
		// Bez „noopener“ vo window.open – s ním prehliadač vždy vráti null a nevieme, či sa okno otvorilo.
		var w = window.open(url, '_blank');
		if (w) {
			try { w.opener = null; } catch (err) { /* nič */ }
		} else {
			window.location.href = url; // blokované vyskakovacie okno
		}
	}
	document.addEventListener('auxclick', function (e) {
		var btn = e.button === 1 && e.target.closest('[data-svl-go]');
		if (btn) { e.preventDefault(); go(btn); }
	});

	document.addEventListener('click', function (e) {
		var goBtn = e.target.closest('[data-svl-go]');
		if (goBtn) { go(goBtn); return; }
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

	function age(sec) {
		var updated = parseInt(sec.dataset.updated, 10) || 0;
		return updated ? Date.now() / 1000 - updated : Infinity;
	}

	function maxAge(sec) {
		return parseInt(sec.dataset.maxAge, 10) || 900;
	}

	function setStatus(sec, text, busy) {
		var el = sec.querySelector('[data-svl-status]');
		if (el) el.textContent = text;
		sec.classList.toggle('is-refreshing', !!busy);
		var btn = sec.querySelector('[data-svl-refresh]');
		if (btn) btn.disabled = !!busy;
	}

	function showAgo(sec) {
		var updated = parseInt(sec.dataset.updated, 10);
		var el = sec.querySelector('[data-svl-status]');
		if (!el || !updated) return;
		el.textContent = 'Aktualizované ';
		var t = document.createElement('time');
		t.setAttribute('data-svl-ago', '');
		t.textContent = minutesAgo(updated);
		el.appendChild(t);
	}

	function tick() {
		var now = Date.now() / 1000;
		document.querySelectorAll('[data-svl]').forEach(function (sec) {
			if (sec.dataset.loading) return;
			var ago = sec.querySelector('[data-svl-ago]');
			var updated = parseInt(sec.dataset.updated, 10);
			if (ago && updated) ago.textContent = minutesAgo(updated);
			if (document.visibilityState !== 'visible') return;
			// Stránka otvorená dlhšie: ceny staršie ako 15 min → obnova na vyžiadanie.
			if (age(sec) > maxAge(sec) && now - (parseFloat(sec.dataset.tried) || 0) > maxAge(sec)) {
				requestRefresh(sec);
				return;
			}
			var next = parseInt(sec.dataset.next, 10);
			if (next && now > next + 90) load(sec);
		});
	}

	function url(base) {
		return base + (base.indexOf('?') < 0 ? '?' : '&') + '_=' + Date.now();
	}

	// Načíta aktuálne HTML; ak sú novšie ceny, vymení blok bez obnovenia stránky.
	function load(sec, onDone) {
		if (!sec.dataset.rest || !window.fetch) return;
		sec.dataset.loading = '1';
		return fetch(url(sec.dataset.rest), { headers: { Accept: 'application/json' }, cache: 'no-store' })
			.then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
			.then(function (json) {
				sec.dataset.loading = '';
				if (!json || !json.html || json.updated <= (parseInt(sec.dataset.updated, 10) || 0)) {
					sec.dataset.next = String(Math.floor(Date.now() / 1000) + 300 - 90);
					if (onDone) onDone(false);
					return;
				}
				var tpl = document.createElement('template');
				tpl.innerHTML = json.html.trim();
				var fresh = tpl.content.firstElementChild;
				fresh.dataset.tried = String(Date.now() / 1000);
				fresh.removeAttribute('data-refreshing');
				sec.replaceWith(fresh);
				fresh.classList.add('svl--fresh');
				init(fresh.parentNode);
				if (onDone) onDone(true);
			})
			.catch(function () {
				sec.dataset.loading = '';
				sec.dataset.next = String(Math.floor(Date.now() / 1000) + 300);
				if (onDone) onDone(false);
			});
	}

	// Po spustení obnovy na serveri čakáme na nové ceny (max. ~3 min).
	function poll(sec, start) {
		start = start || Date.now();
		setStatus(sec, 'Hľadám najnovšie ceny…', true);
		setTimeout(function () {
			if (!sec.isConnected) return;
			load(sec, function (changed) {
				if (changed) return;
				if (Date.now() - start < 180000) {
					poll(sec, start);
				} else {
					setStatus(sec, '', false);
					showAgo(sec);
				}
			});
		}, 6000);
	}

	function requestRefresh(sec, manual) {
		if (!sec.dataset.refresh || !window.fetch || sec.classList.contains('is-refreshing')) return;
		sec.dataset.tried = String(Date.now() / 1000);
		setStatus(sec, manual ? 'Kontrolujem ceny…' : 'Hľadám najnovšie ceny…', true);
		fetch(url(sec.dataset.refresh), { method: 'POST', headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'omit' })
			.then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
			.then(function (json) {
				if (json.updated > (parseInt(sec.dataset.updated, 10) || 0)) {
					load(sec); // medzitým sa už obnovilo
				} else if (json.refreshing) {
					poll(sec);
				} else {
					// Ceny nie sú staršie ako 15 min – netreba nič sťahovať.
					setStatus(sec, '', false);
					showAgo(sec);
					var el = sec.querySelector('[data-svl-status]');
					if (el && manual) el.appendChild(document.createTextNode(' · ceny sú aktuálne'));
				}
			})
			.catch(function () { setStatus(sec, '', false); showAgo(sec); });
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-svl-refresh]');
		if (btn) requestRefresh(btn.closest('[data-svl]'), true);
	});

	function boot() {
		document.querySelectorAll('[data-svl]').forEach(function (sec) {
			if (sec.dataset.refreshing) {
				sec.dataset.tried = String(Date.now() / 1000);
				poll(sec);
			} else if (age(sec) > maxAge(sec)) {
				requestRefresh(sec); // napr. stránka z cache – ceny staršie ako 15 min
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { init(); boot(); });
	} else {
		init();
		boot();
	}
	setInterval(tick, 30000);
	document.addEventListener('visibilitychange', tick);
})();
