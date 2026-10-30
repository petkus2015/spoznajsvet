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

	/* Spodná navigácia „Liquid Glass“ (iOS 27): tekutá šošovka pod aktívnou položkou, dá sa ťahať prstom. */
	var nav = doc.querySelector('[data-bottom-nav]');
	var lens = nav && nav.querySelector('[data-nav-lens]');
	var lensX = null, moveT;

	function moveLens(item, instant) {
		if (!lens) return;
		if (!item) { lens.classList.remove('is-visible'); return; }
		var bar = lens.parentElement.getBoundingClientRect();
		var r = item.getBoundingClientRect();
		if (!r.width) return;
		var x = r.left - bar.left;
		// Pri presune sa šošovka natiahne ako kvapka a potom dopruží.
		if (!instant && lensX !== null && Math.abs(x - lensX) > 2 && !reduceMotion) {
			lens.classList.add('is-moving');
			clearTimeout(moveT);
			moveT = setTimeout(function () { lens.classList.remove('is-moving'); }, 260);
		}
		lensX = x;
		lens.style.setProperty('--lens-x', x + 'px');
		lens.style.setProperty('--lens-w', r.width + 'px');
		lens.classList.add('is-visible');
	}
	function activeItem() { return nav && nav.querySelector('.bottom-nav__item.is-active'); }
	window.sstNavReset = function () { moveLens(activeItem()); };

	if (nav && lens) {
		// Prvé umiestnenie bez animácie, potom už plynulo.
		lens.style.transition = 'none';
		moveLens(activeItem(), true);
		window.requestAnimationFrame(function () {
			lens.style.transition = '';
			nav.classList.add('is-ready');
		});
		var drag = null;
		function itemAt(x) {
			var hit = null;
			nav.querySelectorAll('.bottom-nav__item').forEach(function (it) {
				var r = it.getBoundingClientRect();
				if (x >= r.left && x <= r.right) hit = it;
			});
			return hit;
		}
		// Odkaz sa nesmie začať „presúvať“ – zrušilo by to ťahanie šošovky.
		nav.addEventListener('dragstart', function (e) { e.preventDefault(); });
		nav.addEventListener('pointerdown', function (e) {
			var item = e.target.closest('.bottom-nav__item');
			if (!item) return;
			drag = { start: item, over: item, x: e.clientX, moved: false };
			moveLens(item);
			lens.classList.add('is-pressed');
		});
		// Ťahanie prstom po lište: šošovka ide za prstom, pustením sa otvorí položka pod ním.
		nav.addEventListener('pointermove', function (e) {
			if (!drag) return;
			if (!drag.moved && Math.abs(e.clientX - drag.x) < 8) return;
			drag.moved = true;
			lens.classList.add('is-dragging');
			var it = itemAt(e.clientX);
			if (it && it !== drag.over) { drag.over = it; moveLens(it); }
		});
		var release = function (e) {
			lens.classList.remove('is-pressed', 'is-dragging');
			if (drag && drag.moved && drag.over && drag.over !== drag.start && e && e.type === 'pointerup') {
				drag.over.click();
			}
			drag = null;
		};
		nav.addEventListener('pointerup', release);
		nav.addEventListener('pointercancel', function () { release(); moveLens(activeItem()); });
		nav.addEventListener('pointerleave', function () { if (drag && !drag.moved) release(); });
		window.addEventListener('resize', function () { moveLens(activeItem(), true); });
		// Návrat späť (bfcache) – šošovka na správne miesto.
		window.addEventListener('pageshow', function () { moveLens(activeItem(), true); });
	}

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
		// Šošovka spodnej lišty sa vráti z „Hľadať“ na aktuálnu stránku.
		if (window.sstNavReset) window.sstNavReset();
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

	/* Mobilné menu: otvára sa zospodu z položky „Menu“ v spodnej lište (vyhľadávanie je v ňom dole). */
	var menu = doc.querySelector('[data-mobile-menu]');
	var menuBtns = doc.querySelectorAll('[data-menu-toggle]');
	var menuFrom = null;

	function setMenu(open) {
		if (!menu || !menuBtns.length) return;
		if (open === !menu.hidden) return;
		root.classList.toggle('menu-open', open);
		root.classList.toggle('no-scroll', open);
		menuBtns.forEach(function (b) {
			b.setAttribute('aria-expanded', open ? 'true' : 'false');
			if (b.classList.contains('menu-toggle')) b.setAttribute('aria-label', open ? 'Zavrieť menu' : 'Otvoriť menu');
		});
		if (open) {
			menu.hidden = false;
			window.requestAnimationFrame(function () { menu.classList.add('is-open'); });
		} else {
			menu.classList.remove('is-open');
			setTimeout(function () { if (!root.classList.contains('menu-open')) menu.hidden = true; }, reduceMotion ? 0 : 200);
			// Šošovka spodnej lišty sa vráti z „Menu“ na aktuálnu stránku.
			if (window.sstNavReset) window.sstNavReset();
			if (menuFrom && menuFrom.focus) menuFrom.focus();
		}
	}
	menuBtns.forEach(function (b) {
		b.addEventListener('click', function () { menuFrom = b; setMenu(menu.hidden); });
	});
	if (menu) {
		menu.addEventListener('click', function (e) {
			if (e.target === menu || e.target.closest('a')) setMenu(false);
		});
	}
	doc.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && menu && !menu.hidden) setMenu(false);
	});
	window.matchMedia('(min-width: 960px)').addEventListener('change', function (m) { if (m.matches) setMenu(false); });
	doc.addEventListener('click', function (e) { if (e.target.closest('[data-search-open]')) setMenu(false); }, true);

	/* Klávesnica na mobile: panely zospodu (menu, vyhľadávanie) sa posunú nad ňu. */
	if (window.visualViewport) {
		var vv = window.visualViewport;
		var fitKeyboard = function () {
			var kb = Math.max(0, window.innerHeight - vv.height - vv.offsetTop);
			kb = kb > 80 ? Math.round(kb) : 0;
			root.style.setProperty('--kb', kb + 'px');
			root.classList.toggle('kb-open', kb > 0);
		};
		vv.addEventListener('resize', fitKeyboard);
		vv.addEventListener('scroll', fitKeyboard);
	}

	/* Karusely (scroll-snap + šípky + počítadlo). */
	doc.querySelectorAll('[data-carousel]').forEach(function (car) {
		var track = car.querySelector('[data-carousel-track]');
		if (!track) return;
		var prev = car.querySelector('[data-carousel-prev]');
		var next = car.querySelector('[data-carousel-next]');
		var current = car.querySelector('[data-carousel-current]');
		var bar = car.querySelector('[data-carousel-progress]');

		function step() {
			var first = track.firstElementChild;
			if (!first) return track.clientWidth;
			var gap = parseFloat(getComputedStyle(track).columnGap) || 16;
			return first.getBoundingClientRect().width + gap;
		}
		function update() {
			var i = Math.round(track.scrollLeft / step());
			if (current) current.textContent = String(Math.min(i + 1, track.children.length)).padStart(2, '0');
			if (bar) {
				var max = track.scrollWidth - track.clientWidth;
				var seen = (track.clientWidth + Math.max(0, track.scrollLeft)) / track.scrollWidth;
				bar.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, seen) : 1) + ')';
			}
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

	/* Krajina / mesto: rozbalenie dlhého popisu. */
	doc.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-clamp-toggle]');
		if (!btn) return;
		var box = btn.previousElementSibling;
		if (!box || !box.hasAttribute('data-clamp')) return;
		var open = box.classList.toggle('is-clamped') === false;
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		btn.firstChild.textContent = open ? 'Menej ' : 'Celý popis ';
	});

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
