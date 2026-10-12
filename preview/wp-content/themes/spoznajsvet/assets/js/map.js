/* Spoznaj Svet – mapa „Kde všade som bol“: štáty a ostrovy podľa počtu miest, body miest, priblíženie, posun,
   podrobná mapa pri priblížení, karta štátu. Jadro používa aj administrácia (Články → Mapa ciest). */
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

	// Equal Earth – inverzná projekcia (rovnaká ako d3.geoEqualEarth.invert).
	function invert(data, x, y) {
		var A1 = 1.340264, A2 = -0.081106, A3 = 0.000893, A4 = 0.003796, M = Math.sqrt(3) / 2;
		x = (x - data.tx) / data.scale;
		y = -(y - data.ty) / data.scale;
		var l = y, l2 = l * l, l6 = l2 * l2 * l2;
		for (var i = 0; i < 12; i++) {
			var fy = l * (A1 + A2 * l2 + l6 * (A3 + A4 * l2)) - y;
			var fpy = A1 + 3 * A2 * l2 + l6 * (7 * A3 + 9 * A4 * l2);
			var d = fy / fpy;
			l -= d; l2 = l * l; l6 = l2 * l2 * l2;
			if (Math.abs(d) < 1e-12) break;
		}
		var lng = M * x * (A1 + 3 * A2 * l2 + l6 * (7 * A3 + 9 * A4 * l2)) / Math.cos(l);
		var lat = Math.asin(Math.sin(l) / M);
		return [lat * 180 / Math.PI, lng * 180 / Math.PI];
	}

	// Equal Earth – priama projekcia (rovnaká ako sst_map_project v PHP).
	function project(data, lat, lng) {
		var A1 = 1.340264, A2 = -0.081106, A3 = 0.000893, A4 = 0.003796, M = Math.sqrt(3) / 2;
		var lam = lng * Math.PI / 180, phi = lat * Math.PI / 180;
		var l = Math.asin(M * Math.sin(phi)), l2 = l * l, l6 = l2 * l2 * l2;
		var x = lam * Math.cos(l) / (M * (A1 + 3 * A2 * l2 + l6 * (7 * A3 + 9 * A4 * l2)));
		var y = l * (A1 + A2 * l2 + l6 * (A3 + A4 * l2));
		return [x * data.scale + data.tx, -y * data.scale + data.ty];
	}

	function mount(root, opts) {
		opts = opts || {};
		var canvas = root.querySelector('[data-map-canvas]');
		var tip = root.querySelector('[data-map-tip]');
		var card = root.querySelector('[data-map-card]');
		var data = opts.data;
		var W = data.w, H = data.h;
		var view = [0, 0, W, H];
		var want = data.fit || data.regions.svet;
		var svg, dotsG, halosG, dots = [], halos = [], selected = null, anim = null, detail = 'none';
		var drag = false, moved = false;
		var api = { root: root, canvas: canvas };

		function loadSvg(url) {
			return fetch(url).then(function (r) { return r.ok ? r.text() : Promise.reject(r.status); }).then(function (text) {
				var doc = new DOMParser().parseFromString(text, 'image/svg+xml');
				return document.importNode(doc.documentElement, true);
			});
		}

		loadSvg(root.getAttribute('data-world-map'))
			.then(function (s) {
				svg = s;
				svg.setAttribute('class', 'world-map__svg');
				svg.setAttribute('role', 'img');
				svg.setAttribute('aria-label', 'Mapa sveta so štátmi a ostrovmi, ktoré som navštívil');
				svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
				canvas.insertBefore(svg, canvas.firstChild);
				canvas.classList.add('is-ready');
				paint();
				// „Moje cesty“: všetky vyznačené štáty, ostrovy aj mestá.
				var fit = computeFit();
				if (fit) { if (!want || want === data.fit) want = fit; data.fit = fit; }
				// Mobil: výška mapy podľa výrezu „Moje cesty“, aby nevznikalo prázdne miesto.
				if (data.fit && canvas.clientWidth < 720 && !opts.admin) {
					canvas.style.aspectRatio = String(Math.min(1.8, Math.max(1.1, data.fit[2] / data.fit[3])));
				}
				setView(want, false);
				bind();
				if (opts.onReady) opts.onReady(api);
			})
			.catch(function () { canvas.classList.add('is-error'); });

		// Podrobná mapa (Azory, pobrežie…) – dotiahneme ju až pri priblížení.
		function maybeDetail() {
			if (detail !== 'none' || !data.detail || view[2] > W * 0.45) return;
			detail = 'loading';
			canvas.classList.add('is-loading');
			loadSvg(data.detail).then(function (s) {
				var frag = document.createDocumentFragment();
				Array.prototype.slice.call(s.querySelectorAll('path')).forEach(function (p) { frag.appendChild(p); });
				Array.prototype.slice.call(svg.querySelectorAll('path')).forEach(function (p) { p.remove(); });
				svg.insertBefore(frag, svg.firstChild);
				detail = 'done';
				canvas.classList.remove('is-loading');
				paint();
			}).catch(function () { detail = 'none'; canvas.classList.remove('is-loading'); });
		}

		function countryOf(su) {
			var u = data.units[su];
			return u && u.i ? data.countries[u.i] : null;
		}

		function unitLabel(su, iso) {
			var c = data.countries[iso];
			var all = data.all && data.all[su];
			var name = c ? c.n : (all && all.m ? all.n : iso);
			if (!c && all && !all.m && data.all) {
				Object.keys(data.all).some(function (k) { if (data.all[k].i === iso && data.all[k].m) { name = data.all[k].n; return true; } return false; });
			}
			var part = data.names && data.names[su] ? data.names[su] : (all && !all.m ? all.n : '');
			return (c && c.f ? c.f + ' ' : '') + name + (part && part !== name ? ' – ' + part : '');
		}

		function paint() {
			var visitedPaths = [];
			svg.querySelectorAll('path[data-unit]').forEach(function (p) {
				var su = p.getAttribute('data-unit');
				var u = data.units[su];
				p.removeAttribute('class');
				p.removeAttribute('tabindex');
				p.removeAttribute('role');
				p.removeAttribute('aria-label');
				if (!u) {
					if (opts.admin && p.getAttribute('data-iso')) p.setAttribute('class', 'is-pickable');
					return;
				}
				var c = countryOf(su);
				p.setAttribute('class', 'is-visited lvl-' + (c ? c.l : 1) + (u.a ? '' : ' is-manual'));
				p.setAttribute('tabindex', '0');
				p.setAttribute('role', 'button');
				p.setAttribute('aria-label', unitLabel(su, u.i).replace(/^\S+\s/, '') + (c && summary(c) ? ', ' + summary(c) : ''));
				visitedPaths.push(p);
			});
			// Navštívené navrch, aby ich hranice neprekrývali susedia.
			var firstMark = halosG && halosG.parentNode ? halosG : null;
			visitedPaths.forEach(function (p) { svg.insertBefore(p, firstMark); });

			if (halosG) halosG.remove();
			if (dotsG) dotsG.remove();
			halosG = document.createElementNS(NS, 'g');
			halosG.setAttribute('class', 'world-map__halos');
			halos = [];
			// Malé navštívené ostrovy (Azory, Maldivy…) dostanú krúžok, aby boli vidieť aj pri celom svete.
			visitedPaths.forEach(function (p) {
				var b;
				try { b = p.getBBox(); } catch (e) { return; }
				if (b.width > 5 || b.height > 5) return;
				var h = document.createElementNS(NS, 'circle');
				h.setAttribute('cx', b.x + b.width / 2);
				h.setAttribute('cy', b.y + b.height / 2);
				h.setAttribute('class', 'world-map__halo ' + (p.getAttribute('class').match(/lvl-\d/) || ['lvl-1'])[0]);
				h.setAttribute('data-unit', p.getAttribute('data-unit'));
				h.setAttribute('data-iso', p.getAttribute('data-iso') || '');
				h._base = Math.max(b.width, b.height) / 2;
				halosG.appendChild(h);
				halos.push(h);
			});
			svg.appendChild(halosG);

			dotsG = document.createElementNS(NS, 'g');
			dotsG.setAttribute('class', 'world-map__dots');
			dots = [];
			Object.keys(data.countries).forEach(function (iso) {
				data.countries[iso].c.forEach(function (city) {
					if (city.x == null) return;
					var d = document.createElementNS(NS, 'circle');
					d.setAttribute('cx', city.x);
					d.setAttribute('cy', city.y);
					d.setAttribute('class', 'world-map__dot');
					d.setAttribute('data-iso', iso);
					d.setAttribute('data-city', city.n);
					d.setAttribute('data-id', city.id || '');
					dotsG.appendChild(d);
					dots.push(d);
				});
			});
			svg.appendChild(dotsG);
			if (selected) select(selected, true);
			sizeMarks();
		}

		function computeFit() {
			var x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
			function add(b) { x0 = Math.min(x0, b.x); y0 = Math.min(y0, b.y); x1 = Math.max(x1, b.x + b.width); y1 = Math.max(y1, b.y + b.height); }
			svg.querySelectorAll('path.is-visited').forEach(function (p) { try { add(p.getBBox()); } catch (e) { /* skrytý prvok */ } });
			dots.forEach(function (d) { add({ x: +d.getAttribute('cx'), y: +d.getAttribute('cy'), width: 0, height: 0 }); });
			if (!isFinite(x0)) return null;
			var pad = 25;
			x0 = Math.max(0, x0 - pad); y0 = Math.max(0, y0 - pad); x1 = Math.min(W, x1 + pad); y1 = Math.min(H, y1 + pad);
			return [x0, y0, Math.max(60, x1 - x0), Math.max(40, y1 - y0)];
		}

		function sizeMarks() {
			var k = view[2] / (canvas.clientWidth || W); // jednotky SVG na 1 px
			var r = ((canvas.clientWidth < 600 ? 2.7 : 3.4) * k).toFixed(3);
			dots.forEach(function (d) { d.setAttribute('r', r); });
			halos.forEach(function (h) { h.setAttribute('r', (h._base + 5 * k).toFixed(3)); });
		}

		// Výrez prispôsobený pomeru strán plátna; okraj vpravo pre tlačidlá +/−.
		function fitBox(b) {
			var pl = 12, pt = 12, pb = 12, pr = canvas.clientWidth < 600 ? 52 : 64;
			var cw = Math.max(1, (canvas.clientWidth || 1) - pl - pr), ch = Math.max(1, (canvas.clientHeight || 1) - pt - pb);
			var ar = cw / ch, x = b[0], y = b[1], w = b[2], h = b[3];
			if (w / h > ar) { var nh = w / ar; y -= (nh - h) / 2; h = nh; } else { var nw = h * ar; x -= (nw - w) / 2; w = nw; }
			var k = w / cw;
			return clamp([x - pl * k, y - pt * k, w + (pl + pr) * k, h + (pt + pb) * k]);
		}

		function clamp(v) {
			var w = Math.min(Math.max(v[2], 4), W * 1.6), h = w * (v[3] / v[2]);
			var x = Math.min(Math.max(v[0], -w * 0.3), W - w * 0.7);
			var y = Math.min(Math.max(v[1], -h * 0.3), H - h * 0.7);
			return [x, y, w, h];
		}

		function apply(v) {
			view = v;
			svg.setAttribute('viewBox', v.map(function (n) { return n.toFixed(3); }).join(' '));
			sizeMarks();
			canvas.classList.toggle('is-zoomed', v[2] < W * 0.98);
			maybeDetail();
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
			if (!tip) return;
			if (!text) { tip.hidden = true; return; }
			tip.textContent = text;
			tip.hidden = false;
			var rect = canvas.getBoundingClientRect();
			var x = ev.clientX - rect.left, y = ev.clientY - rect.top;
			tip.style.left = Math.min(Math.max(x, 90), rect.width - 90) + 'px';
			tip.style.top = Math.max(y - 14, 30) + 'px';
		}

		// Svetové súradnice a bod v SVG pre klik.
		function locate(ev) {
			var pt = svg.createSVGPoint();
			pt.x = ev.clientX; pt.y = ev.clientY;
			var p = pt.matrixTransform(svg.getScreenCTM().inverse());
			var ll = invert(data, p.x, p.y);
			return { x: p.x, y: p.y, lat: ll[0], lng: ll[1] };
		}

		function select(iso, keep) {
			svg.querySelectorAll('.is-selected').forEach(function (p) { p.classList.remove('is-selected'); });
			selected = iso;
			var c = iso && data.countries[iso];
			if (!c) { if (card) { card.hidden = true; card.textContent = ''; } return; }
			svg.querySelectorAll('path[data-iso="' + iso + '"].is-visited').forEach(function (p) { p.classList.add('is-selected'); });
			dots.forEach(function (d) { d.classList.toggle('is-selected', d.getAttribute('data-iso') === iso); });
			if (!card || keep) return;
			card.textContent = '';

			var head = el('div', 'world-map__card-head');
			head.appendChild(el('span', 'world-map__card-flag', c.f));
			var t = el('div', 'world-map__card-title');
			t.appendChild(el('strong', null, c.n));
			var partNames = (c.parts || []).map(function (su) { return data.names[su]; }).filter(Boolean);
			t.appendChild(el('span', null, [summary(c) || 'navštívený štát'].concat(partNames.length ? ['aj ' + partNames.join(', ')] : []).join(' · ')));
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
			if (c.u) {
				var go = el('a', 'btn btn--primary world-map__card-go', 'Zobraziť ' + c.n + ' →');
				go.href = c.u;
				card.appendChild(go);
			}
			card.hidden = false;
		}

		function bind() {
			svg.addEventListener('pointermove', function (e) {
				if (e.pointerType !== 'mouse' || drag) return;
				var t = e.target, su = t.getAttribute && t.getAttribute('data-unit');
				if (t.classList.contains('world-map__dot')) {
					var c = data.countries[t.getAttribute('data-iso')];
					showTip(t.getAttribute('data-city') + (c ? ' · ' + c.n : ''), e);
				} else if (su && data.units[su]) {
					var cc = countryOf(su);
					showTip(unitLabel(su, data.units[su].i) + (cc && summary(cc) ? ' · ' + summary(cc) : ''), e);
				} else if (su && opts.admin && data.all && data.all[su]) {
					showTip(unitLabel(su, data.all[su].i), e);
				} else {
					showTip('', e);
				}
			});
			svg.addEventListener('pointerleave', function () { if (tip) tip.hidden = true; });

			svg.addEventListener('click', function (e) {
				if (moved) return;
				var t = e.target;
				var info = {
					unit: t.getAttribute && t.getAttribute('data-unit'),
					iso: t.getAttribute && t.getAttribute('data-iso'),
					city: t.classList && t.classList.contains('world-map__dot') ? { id: t.getAttribute('data-id'), n: t.getAttribute('data-city') } : null,
					at: locate(e),
					event: e,
				};
				if (opts.onClick && opts.onClick(info) === true) return;
				var iso = info.unit && data.units[info.unit] ? data.units[info.unit].i : (info.city ? info.iso : null);
				select(iso && data.countries[iso] ? (iso === selected ? null : iso) : null);
			});
			svg.addEventListener('keydown', function (e) {
				if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('is-visited')) {
					e.preventDefault();
					var r = e.target.getBoundingClientRect();
					e.target.dispatchEvent(new MouseEvent('click', { bubbles: true, clientX: r.left + r.width / 2, clientY: r.top + r.height / 2 }));
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

			// Posun myšou / prstom, priblíženie dvoma prstami, v administrácii aj kolieskom.
			var pts = {}, last = null, pinch = null;
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
					if (tip) tip.hidden = true;
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
			if (opts.admin) {
				canvas.addEventListener('wheel', function (e) {
					e.preventDefault();
					setChip('');
					zoomAt(e.deltaY > 0 ? 1.18 : 1 / 1.18, e.clientX, e.clientY);
				}, { passive: false });
			}

			var rt;
			window.addEventListener('resize', function () {
				clearTimeout(rt);
				rt = setTimeout(function () { if (want) setView(want, false); else apply(clamp(view)); }, 150);
			});
		}

		api.setData = function (d) { data = d; if (svg) paint(); };
		api.getData = function () { return data; };
		api.select = select;
		api.label = unitLabel;
		api.focusUnit = function (su) {
			var p = svg && svg.querySelector('path[data-unit="' + su + '"]');
			if (!p) return;
			var b = p.getBBox(), pad = Math.max(b.width, b.height) * 0.6 + 6;
			setChip('');
			setView([b.x - pad, b.y - pad, b.width + 2 * pad, b.height + 2 * pad], true);
		};
		api.zoomTo = function (lat, lng, span) {
			var c = project(data, lat, lng), s = span || 30;
			setChip('');
			setView([c[0] - s / 2, c[1] - s / 2, s, s], true);
		};
		return api;
	}

	window.SSTMap = { mount: mount, invert: invert, project: project, summary: summary, plural: plural };

	function init() {
		document.querySelectorAll('[data-world-map]:not([data-map-admin])').forEach(function (root) {
			var dataEl = root.querySelector('[data-map-data]');
			if (!dataEl || !window.fetch) return;
			mount(root, { data: JSON.parse(dataEl.textContent) });
		});
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
