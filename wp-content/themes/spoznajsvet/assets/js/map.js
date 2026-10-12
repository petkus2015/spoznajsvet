/* Spoznaj Svet – mapa „Kde som bol“: farby krajín podľa počtu miest, body miest, priblíženie, posun, karta krajiny. */
(function () {
	'use strict';

	var NS = 'http://www.w3.org/2000/svg';
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	function plural(n, one, few, many) {
		return n === 1 ? one : n >= 2 && n <= 4 ? few : many;
	}

	function el(tag, cls, text) {
		var e = document.createElement(tag);
		if (cls) e.className = cls;
		if (text != null) e.textContent = text;
		return e;
	}

	function summary(c) {
		var parts = [];
		if (c.c.length) parts.push(c.c.length + ' ' + plural(c.c.length, 'miesto', 'miesta', 'miest'));
		if (c.p) parts.push(c.p + ' ' + plural(c.p, 'článok', 'články', 'článkov'));
		return parts.join(' · ');
	}

	function setup(root) {
		var dataEl = root.querySelector('[data-map-data]');
		var canvas = root.querySelector('[data-map-canvas]');
		if (!dataEl || !canvas || !window.fetch) return;
		var data = JSON.parse(dataEl.textContent);
		var tip = root.querySelector('[data-map-tip]');
		var card = root.querySelector('[data-map-card]');
		var W = data.w, H = data.h;
		var view = [0, 0, W, H];
		var want = data.fit || data.regions.svet;
		var svg, dots = [], selected = null, anim = null;

		fetch(root.getAttribute('data-world-map'))
			.then(function (r) { return r.ok ? r.text() : Promise.reject(r.status); })
			.then(function (text) {
				var doc = new DOMParser().parseFromString(text, 'image/svg+xml');
				svg = document.importNode(doc.documentElement, true);
				svg.setAttribute('class', 'world-map__svg');
				svg.setAttribute('role', 'img');
				svg.setAttribute('aria-label', 'Mapa sveta s krajinami, ktoré som navštívil');
				svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
				paint();
				canvas.insertBefore(svg, canvas.firstChild);
				canvas.classList.add('is-ready');
				// Mobil: výška mapy podľa výrezu „Moje cesty“, aby nevznikalo prázdne miesto.
				if (data.fit && canvas.clientWidth < 720) {
					canvas.style.aspectRatio = String(Math.min(1.8, Math.max(1.1, data.fit[2] / data.fit[3])));
				}
				setView(want, false);
				bind();
			})
			.catch(function () { canvas.classList.add('is-error'); });

		function paint() {
			svg.querySelectorAll('path[data-iso]').forEach(function (p) {
				var c = data.countries[p.getAttribute('data-iso')];
				if (!c) return;
				p.classList.add('is-visited', 'lvl-' + c.l);
				p.setAttribute('tabindex', '0');
				p.setAttribute('role', 'button');
				p.setAttribute('aria-label', c.n + ', ' + summary(c));
				// Navštívené krajiny navrch, aby ich hranice neprekrývali susedia.
				p.parentNode.appendChild(p);
			});
			var g = document.createElementNS(NS, 'g');
			g.setAttribute('class', 'world-map__dots');
			Object.keys(data.countries).forEach(function (iso) {
				data.countries[iso].c.forEach(function (city) {
					if (city.x == null) return;
					var d = document.createElementNS(NS, 'circle');
					d.setAttribute('cx', city.x);
					d.setAttribute('cy', city.y);
					d.setAttribute('r', 3);
					d.setAttribute('class', 'world-map__dot');
					d.setAttribute('data-iso', iso);
					d.setAttribute('data-city', city.n);
					g.appendChild(d);
					dots.push(d);
				});
			});
			svg.appendChild(g);
		}

		// Výrez prispôsobený pomeru strán plátna (aby sa oblasť zmestila celá).
		// Okraj v px: vpravo miesto pre tlačidlá +/−, aby neprekryli navštívené miesta.
		function fitBox(b) {
			var pl = 12, pt = 12, pb = 12, pr = canvas.clientWidth < 600 ? 52 : 64;
			var cw = Math.max(1, (canvas.clientWidth || 1) - pl - pr), ch = Math.max(1, (canvas.clientHeight || 1) - pt - pb);
			var ar = cw / ch, x = b[0], y = b[1], w = b[2], h = b[3];
			if (w / h > ar) { var nh = w / ar; y -= (nh - h) / 2; h = nh; } else { var nw = h * ar; x -= (nw - w) / 2; w = nw; }
			var k = w / cw;
			return clamp([x - pl * k, y - pt * k, w + (pl + pr) * k, h + (pt + pb) * k]);
		}

		function clamp(v) {
			var w = Math.min(Math.max(v[2], 40), W * 1.6), h = w * (v[3] / v[2]);
			var x = Math.min(Math.max(v[0], -w * 0.3), W - w * 0.7);
			var y = Math.min(Math.max(v[1], -h * 0.3), H - h * 0.7);
			return [x, y, w, h];
		}

		function apply(v) {
			view = v;
			svg.setAttribute('viewBox', v.map(function (n) { return n.toFixed(2); }).join(' '));
			var k = v[2] / (canvas.clientWidth || W); // jednotky SVG na 1 px
			var r = ((canvas.clientWidth < 600 ? 2.7 : 3.4) * k).toFixed(3);
			dots.forEach(function (d) { d.setAttribute('r', r); });
			canvas.classList.toggle('is-zoomed', v[2] < W * 0.98);
		}

		function setView(box, animate) {
			want = box;
			var to = fitBox(box);
			if (anim) cancelAnimationFrame(anim);
			if (!animate || reduce) { apply(to); return; }
			var from = view.slice(), t0 = performance.now(), dur = 520;
			(function step(now) {
				var t = Math.min(1, (now - t0) / dur), e = 1 - Math.pow(1 - t, 3);
				apply(from.map(function (f, i) { return f + (to[i] - f) * e; }));
				if (t < 1) anim = requestAnimationFrame(step);
			})(t0);
		}

		function zoomAt(factor, px, py) {
			var rect = canvas.getBoundingClientRect();
			var fx = px == null ? 0.5 : (px - rect.left) / rect.width;
			var fy = py == null ? 0.5 : (py - rect.top) / rect.height;
			var w = view[2] * factor, h = view[3] * factor;
			var x = view[0] + (view[2] - w) * fx, y = view[1] + (view[3] - h) * fy;
			want = null;
			apply(clamp([x, y, w, h]));
		}

		function setChip(key) {
			root.querySelectorAll('[data-region]').forEach(function (b) {
				var on = b.getAttribute('data-region') === key;
				b.classList.toggle('is-active', on);
				b.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
		}

		function showTip(text, ev) {
			if (!text) { tip.hidden = true; return; }
			tip.textContent = text;
			tip.hidden = false;
			var rect = canvas.getBoundingClientRect();
			var x = ev.clientX - rect.left, y = ev.clientY - rect.top;
			tip.style.left = Math.min(Math.max(x, 70), rect.width - 70) + 'px';
			tip.style.top = Math.max(y - 14, 30) + 'px';
		}

		function select(iso) {
			if (selected) svg.querySelectorAll('.is-selected').forEach(function (p) { p.classList.remove('is-selected'); });
			selected = iso;
			var c = iso && data.countries[iso];
			card.textContent = '';
			if (!c) { card.hidden = true; return; }
			svg.querySelectorAll('path[data-iso="' + iso + '"]').forEach(function (p) { p.classList.add('is-selected'); });
			dots.forEach(function (d) { d.classList.toggle('is-selected', d.getAttribute('data-iso') === iso); });

			var head = el('div', 'world-map__card-head');
			head.appendChild(el('span', 'world-map__card-flag', c.f));
			var t = el('div', 'world-map__card-title');
			t.appendChild(el('strong', null, c.n));
			t.appendChild(el('span', null, summary(c) || 'navštívená krajina'));
			head.appendChild(t);
			var close = el('button', 'world-map__card-close', '×');
			close.type = 'button';
			close.setAttribute('aria-label', 'Zavrieť');
			close.addEventListener('click', function () { select(null); });
			head.appendChild(close);
			card.appendChild(head);

			if (c.c.length) {
				var list = el('ul', 'world-map__cities');
				c.c.forEach(function (city) {
					var li = el('li');
					var a = el('a', 'chip', city.n);
					a.href = city.u;
					if (city.p) a.appendChild(el('span', 'chip__count', String(city.p)));
					li.appendChild(a);
					list.appendChild(li);
				});
				card.appendChild(list);
			}
			var go = el('a', 'btn btn--primary world-map__card-go', 'Zobraziť ' + c.n + ' →');
			go.href = c.u;
			card.appendChild(go);
			card.hidden = false;
		}

		function bind() {
			// Prechod myšou: názov krajiny / mesta.
			svg.addEventListener('pointermove', function (e) {
				if (e.pointerType !== 'mouse' || drag) return;
				var t = e.target;
				if (t.classList.contains('world-map__dot')) {
					var c = data.countries[t.getAttribute('data-iso')];
					showTip(t.getAttribute('data-city') + (c ? ' · ' + c.n : ''), e);
				} else if (t.classList.contains('is-visited')) {
					var cc = data.countries[t.getAttribute('data-iso')];
					showTip(cc.f + ' ' + cc.n + (summary(cc) ? ' · ' + summary(cc) : ''), e);
				} else {
					showTip('', e);
				}
			});
			svg.addEventListener('pointerleave', function () { tip.hidden = true; });

			svg.addEventListener('click', function (e) {
				if (moved) return;
				var iso = e.target.getAttribute && e.target.getAttribute('data-iso');
				select(iso && data.countries[iso] ? (iso === selected ? null : iso) : null);
			});
			svg.addEventListener('keydown', function (e) {
				if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('is-visited')) {
					e.preventDefault();
					select(e.target.getAttribute('data-iso'));
				}
			});

			root.querySelectorAll('[data-region]').forEach(function (b) {
				b.addEventListener('click', function () {
					var key = b.getAttribute('data-region');
					setChip(key);
					setView(key === 'fit' ? data.fit : data.regions[key], true);
				});
			});
			root.querySelectorAll('[data-zoom]').forEach(function (b) {
				b.addEventListener('click', function () {
					setChip('');
					zoomAt(b.getAttribute('data-zoom') === 'in' ? 0.6 : 1 / 0.6);
				});
			});

			// Posun myšou / prstom, priblíženie dvoma prstami.
			var pts = {}, drag = false, moved = false, last = null, pinch = null;
			svg.addEventListener('pointerdown', function (e) {
				pts[e.pointerId] = { x: e.clientX, y: e.clientY };
				moved = false;
				var ids = Object.keys(pts);
				if (ids.length === 1) { drag = true; last = { x: e.clientX, y: e.clientY }; }
				if (ids.length === 2) {
					var a = pts[ids[0]], b = pts[ids[1]];
					pinch = { d: Math.hypot(a.x - b.x, a.y - b.y), mx: (a.x + b.x) / 2, my: (a.y + b.y) / 2 };
				}
			});
			window.addEventListener('pointermove', function (e) {
				if (!pts[e.pointerId]) return;
				pts[e.pointerId] = { x: e.clientX, y: e.clientY };
				var ids = Object.keys(pts);
				var k = view[2] / canvas.clientWidth;
				if (ids.length === 2 && pinch) {
					var a = pts[ids[0]], b = pts[ids[1]];
					var d = Math.hypot(a.x - b.x, a.y - b.y), mx = (a.x + b.x) / 2, my = (a.y + b.y) / 2;
					apply(clamp([view[0] - (mx - pinch.mx) * k, view[1] - (my - pinch.my) * k, view[2], view[3]]));
					if (d > 0 && pinch.d > 0) zoomAt(pinch.d / d, mx, my);
					pinch = { d: d, mx: mx, my: my };
					moved = true;
					setChip('');
				} else if (drag && last) {
					var dx = e.clientX - last.x, dy = e.clientY - last.y;
					if (!moved && Math.abs(dx) + Math.abs(dy) < 4) return;
					moved = true;
					canvas.classList.add('is-dragging');
					tip.hidden = true;
					apply(clamp([view[0] - dx * k, view[1] - dy * k, view[2], view[3]]));
					last = { x: e.clientX, y: e.clientY };
					setChip('');
					want = null;
				}
			});
			function up(e) {
				delete pts[e.pointerId];
				if (Object.keys(pts).length < 2) pinch = null;
				if (!Object.keys(pts).length) { drag = false; last = null; canvas.classList.remove('is-dragging'); }
			}
			window.addEventListener('pointerup', up);
			window.addEventListener('pointercancel', up);

			var rt;
			window.addEventListener('resize', function () {
				clearTimeout(rt);
				rt = setTimeout(function () { if (want) setView(want, false); else apply(clamp(view)); }, 150);
			});
		}
	}

	function init() {
		document.querySelectorAll('[data-world-map]').forEach(setup);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
