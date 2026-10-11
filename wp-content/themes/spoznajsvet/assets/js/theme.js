/* Spoznaj Svet – interakcie témy (bez závislostí). */
(function () {
	'use strict';

	var doc = document;
	var root = doc.documentElement;
	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	/* Hlavička: pozadie po odscrollovaní. Spodná navigácia je na mobile viditeľná stále. */
	var header = doc.querySelector('[data-header]');
	var progress = doc.querySelector('[data-read-progress]');
	var ticking = false;

	function onScroll() {
		var y = window.scrollY;
		if (header) header.classList.toggle('is-scrolled', y > 12);
		if (progress) {
			var h = doc.documentElement.scrollHeight - window.innerHeight;
			progress.style.transform = 'scaleX(' + (h > 0 ? Math.min(1, y / h) : 0) + ')';
		}
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
	}, { passive: true });
	onScroll();

	/* Vyhľadávanie cez celú obrazovku. */
	var overlay = doc.querySelector('[data-search-overlay]');
	var lastFocus = null;

	function openSearch(trigger) {
		if (!overlay) return;
		lastFocus = trigger || doc.activeElement;
		overlay.hidden = false;
		root.classList.add('no-scroll');
		doc.querySelectorAll('[data-search-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
		window.requestAnimationFrame(function () {
			overlay.classList.add('is-open');
			var input = overlay.querySelector('input[type="search"]');
			if (input) input.focus();
		});
	}
	function closeSearch() {
		if (!overlay || overlay.hidden) return;
		overlay.classList.remove('is-open');
		root.classList.remove('no-scroll');
		doc.querySelectorAll('[data-search-open]').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
		setTimeout(function () { overlay.hidden = true; }, reduceMotion ? 0 : 200);
		if (lastFocus) lastFocus.focus();
	}

	doc.addEventListener('click', function (e) {
		var open = e.target.closest('[data-search-open]');
		if (open) { e.preventDefault(); openSearch(open); return; }
		if (e.target.closest('[data-search-close]') || e.target === overlay) closeSearch();
	});
	doc.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') closeSearch();
		if (e.key === '/' && !/input|textarea|select/i.test(doc.activeElement.tagName)) { e.preventDefault(); openSearch(); }
		if (e.key === 'Tab' && overlay && !overlay.hidden) {
			var f = overlay.querySelectorAll('a, button, input');
			if (!f.length) return;
			var first = f[0], last = f[f.length - 1];
			if (e.shiftKey && doc.activeElement === first) { e.preventDefault(); last.focus(); }
			else if (!e.shiftKey && doc.activeElement === last) { e.preventDefault(); first.focus(); }
		}
	});

	/* Mobilné menu (hamburger). */
	var menu = doc.querySelector('[data-mobile-menu]');
	var menuBtn = doc.querySelector('[data-menu-toggle]');

	function setMenu(open) {
		if (!menu || !menuBtn) return;
		if (open === !menu.hidden) return;
		root.classList.toggle('menu-open', open);
		root.classList.toggle('no-scroll', open);
		menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
		menuBtn.setAttribute('aria-label', open ? 'Zavrieť menu' : 'Otvoriť menu');
		if (open) {
			menu.hidden = false;
			window.requestAnimationFrame(function () { menu.classList.add('is-open'); });
		} else {
			menu.classList.remove('is-open');
			setTimeout(function () { if (!root.classList.contains('menu-open')) menu.hidden = true; }, reduceMotion ? 0 : 200);
		}
	}
	if (menuBtn) menuBtn.addEventListener('click', function () { setMenu(menu.hidden); });
	if (menu) {
		menu.addEventListener('click', function (e) {
			if (e.target === menu || e.target.closest('a')) setMenu(false);
		});
	}
	doc.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && menu && !menu.hidden) { setMenu(false); menuBtn.focus(); }
	});
	window.matchMedia('(min-width: 960px)').addEventListener('change', function (m) { if (m.matches) setMenu(false); });
	doc.addEventListener('click', function (e) { if (e.target.closest('[data-search-open]')) setMenu(false); }, true);

	/* Karusely (scroll-snap + šípky + počítadlo). */
	doc.querySelectorAll('[data-carousel]').forEach(function (car) {
		var track = car.querySelector('[data-carousel-track]');
		if (!track) return;
		var prev = car.querySelector('[data-carousel-prev]');
		var next = car.querySelector('[data-carousel-next]');
		var current = car.querySelector('[data-carousel-current]');

		function step() {
			var first = track.firstElementChild;
			if (!first) return track.clientWidth;
			var gap = parseFloat(getComputedStyle(track).columnGap) || 16;
			return first.getBoundingClientRect().width + gap;
		}
		function update() {
			var i = Math.round(track.scrollLeft / step());
			if (current) current.textContent = String(Math.min(i + 1, track.children.length)).padStart(2, '0');
			if (prev) prev.disabled = track.scrollLeft < 4;
			if (next) next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
		}
		if (prev) prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: reduceMotion ? 'auto' : 'smooth' }); });
		if (next) next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: reduceMotion ? 'auto' : 'smooth' }); });
		track.addEventListener('scroll', function () { window.requestAnimationFrame(update); }, { passive: true });
		window.addEventListener('resize', update);
		update();
	});

	/* Stránka Destinácie: filter a kontinenty. */
	var filter = doc.querySelector('[data-dest-filter]');
	var tabs = doc.querySelector('[data-continent-tabs]');
	var activeContinent = '';

	function norm(s) {
		return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	}
	function applyDest() {
		var q = filter ? norm(filter.value.trim()) : '';
		var any = false;
		doc.querySelectorAll('[data-continent-group]').forEach(function (g) {
			var groupOk = !activeContinent || g.dataset.continentGroup === activeContinent;
			var visible = 0;
			g.querySelectorAll('.country-row').forEach(function (row) {
				var ok = groupOk && (!q || norm(row.dataset.name || '').indexOf(q) !== -1);
				row.hidden = !ok;
				if (ok) visible++;
			});
			g.hidden = visible === 0;
			if (visible) any = true;
		});
		var empty = doc.querySelector('[data-dest-empty]');
		if (empty) empty.hidden = any;
	}
	if (filter) filter.addEventListener('input', applyDest);
	if (tabs) {
		tabs.addEventListener('click', function (e) {
			var b = e.target.closest('[data-continent]');
			if (!b) return;
			activeContinent = b.dataset.continent;
			tabs.querySelectorAll('.chip').forEach(function (c) { c.classList.toggle('is-active', c === b); });
			applyDest();
		});
	}

	/* Zdieľanie článku. */
	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-share]');
		if (!btn) return;
		var data = { title: btn.dataset.title, url: btn.dataset.url };
		if (navigator.share) {
			navigator.share(data).catch(function () {});
		} else if (navigator.clipboard) {
			navigator.clipboard.writeText(data.url).then(function () {
				var label = btn.querySelector('[data-share-label]');
				if (label) { label.textContent = 'Odkaz skopírovaný'; setTimeout(function () { label.textContent = 'Zdieľať'; }, 2000); }
			});
		}
	});
})();
