/* Spoznaj Svet – administrácia mapy ciest (Články → Mapa ciest). */
(function () {
	'use strict';

	var root = document.querySelector('.sst-map-admin');
	if (!root || !window.SSTMap) return;
	var mapEl = root.querySelector('[data-world-map]');
	var data = JSON.parse(root.querySelector('[data-map-data]').textContent);
	var pop = root.querySelector('[data-pop]');
	var toastEl = root.querySelector('[data-toast]');
	var list = root.querySelector('[data-list]');
	var search = root.querySelector('[data-search]');
	var results = root.querySelector('[data-results]');
	var mode = 'units';
	var map, toastT, busy = false;

	function el(tag, cls, text) {
		var e = document.createElement(tag);
		if (cls) e.className = cls;
		if (text != null) e.textContent = text;
		return e;
	}

	function norm(s) {
		return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	}

	function flag(iso) {
		return iso && /^[A-Z]{2}$/.test(iso) ? String.fromCodePoint(0x1F1E6 + iso.charCodeAt(0) - 65, 0x1F1E6 + iso.charCodeAt(1) - 65) : '';
	}

	function countryName(iso) {
		if (data.countries[iso]) return data.countries[iso].n;
		for (var su in data.all) if (data.all[su].i === iso && data.all[su].m) return data.all[su].n;
		return iso;
	}

	function unitsOf(iso) {
		return Object.keys(data.all).filter(function (su) { return data.all[su].i === iso; }).sort(function (a, b) {
			return data.all[b].m - data.all[a].m || data.all[a].n.localeCompare(data.all[b].n, 'sk');
		});
	}

	function toast(msg, error) {
		toastEl.textContent = msg;
		toastEl.classList.toggle('is-error', !!error);
		toastEl.hidden = false;
		clearTimeout(toastT);
		toastT = setTimeout(function () { toastEl.hidden = true; }, 3200);
	}

	function post(action, fields) {
		if (busy) return Promise.reject('busy');
		busy = true;
		root.classList.add('is-busy');
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('nonce', window.sstMapAdmin.nonce);
		Object.keys(fields).forEach(function (k) { body.append(k, fields[k]); });
		return fetch(window.sstMapAdmin.ajax, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (json) {
				busy = false;
				root.classList.remove('is-busy');
				if (!json || !json.success) {
					toast((json && json.data && json.data.msg) || 'Nepodarilo sa uložiť.', true);
					return Promise.reject('fail');
				}
				data = json.data.data;
				map.setData(data);
				root.querySelector('[data-stats]').innerHTML = json.data.stats;
				renderList();
				toast('✓ ' + json.data.msg);
				return json;
			}, function (e) {
				busy = false;
				root.classList.remove('is-busy');
				toast('Spojenie zlyhalo, skús to znova.', true);
				return Promise.reject(e);
			});
	}

	function toggle(su, on) {
		return post('sst_map_toggle', { unit: su, on: on ? 1 : 0 });
	}

	/* ---------- Bočný zoznam ---------- */

	function renderList() {
		list.textContent = '';
		var isos = Object.keys(data.countries).sort(function (a, b) { return data.countries[a].n.localeCompare(data.countries[b].n, 'sk'); });
		root.querySelector('[data-count]').textContent = '(' + isos.length + ')';
		if (!isos.length) {
			list.appendChild(el('li', 'sst-map-admin__empty', 'Zatiaľ nič. Klikni na štát v mape alebo ho vyhľadaj.'));
			return;
		}
		isos.forEach(function (iso) {
			var c = data.countries[iso];
			var li = el('li', 'sst-map-admin__row');
			var head = el('div', 'sst-map-admin__row-head');
			var name = el('button', 'sst-map-admin__name');
			name.type = 'button';
			name.appendChild(el('span', 'sst-map-admin__flag', c.f || flag(iso)));
			var t = el('span');
			t.appendChild(el('strong', null, c.n));
			t.appendChild(el('small', null, window.SSTMap.summary(c) || (c.id ? 'v Destináciách' : 'vyznačené ručne')));
			name.appendChild(t);
			name.addEventListener('click', function () {
				var main = unitsOf(iso).filter(function (su) { return data.units[su]; })[0] || unitsOf(iso)[0];
				if (main) map.focusUnit(main);
			});
			head.appendChild(name);
			if (c.e) {
				var edit = el('a', 'sst-map-admin__edit', 'Upraviť');
				edit.href = c.e;
				head.appendChild(edit);
			}
			li.appendChild(head);

			// Časti štátu: pevnina, ostrovy… – prepínače.
			var parts = unitsOf(iso);
			if (parts.length) {
				var chips = el('div', 'sst-map-admin__parts');
				parts.forEach(function (su) {
					var u = data.units[su];
					var b = el('button', 'sst-map-admin__part' + (u ? ' is-on' : ''), parts.length === 1 ? 'Celý štát' : (data.all[su].m ? 'Pevnina' : data.all[su].n));
					b.type = 'button';
					b.setAttribute('aria-pressed', u ? 'true' : 'false');
					b.title = u ? (u.a ? 'Automaticky z Destinácií – kliknutím skryješ' : 'Vyznačené ručne – kliknutím zrušíš') : 'Kliknutím vyznačíš';
					b.addEventListener('click', function () { toggle(su, !u); });
					b.addEventListener('mouseenter', function () { map.select(iso, true); });
					chips.appendChild(b);
				});
				li.appendChild(chips);
			}

			if (c.c.length) {
				var cities = el('ul', 'sst-map-admin__cities');
				c.c.forEach(function (city) {
					var ci = el('li');
					var go = el('button', 'sst-map-admin__city', city.n);
					go.type = 'button';
					go.title = city.x == null ? 'Poloha zatiaľ chýba – doplní sa automaticky' : 'Ukázať na mape';
					if (city.x == null) go.classList.add('is-missing');
					if (city.isl) { go.classList.add('is-island'); go.title += ' · ostrov'; }
					go.addEventListener('click', function () {
						if (city.x == null) return;
						var ll = window.SSTMap.invert(data, city.x, city.y);
						map.zoomTo(ll[0], ll[1], 14);
					});
					ci.appendChild(go);
					if (city.e) {
						var e = el('a', 'sst-map-admin__city-edit', '✎');
						e.href = city.e;
						e.title = 'Upraviť ' + city.n;
						ci.appendChild(e);
					}
					cities.appendChild(ci);
				});
				li.appendChild(cities);
			}
			list.appendChild(li);
		});
	}

	/* ---------- Vyhľadávanie štátov a ostrovov ---------- */

	var index = null;
	function buildIndex() {
		index = Object.keys(data.all).map(function (su) {
			var u = data.all[su], cn = countryName(u.i);
			var label = u.m ? cn : cn + ' – ' + u.n;
			return { su: su, iso: u.i, label: label, key: norm(label + ' ' + u.n) };
		});
		// Obľúbené ostrovy (Santorini, Koh Samui…) – pridajú sa ako miesto s polohou.
		(data.gaz || []).forEach(function (g) {
			var label = g[0] + ' – ostrov, ' + countryName(g[1]);
			index.push({ isle: g, iso: g[1], label: label, key: norm(label + ' ostrov') });
		});
		index.sort(function (a, b) { return a.label.localeCompare(b.label, 'sk'); });
	}

	function visitedPlace(iso, name) {
		var c = data.countries[iso];
		return !!(c && c.c.some(function (x) { return norm(x.n) === norm(name); }));
	}

	function renderResults() {
		var q = norm(search.value.trim());
		results.textContent = '';
		if (!q) { results.hidden = true; return; }
		if (!index) buildIndex();
		var hits = index.filter(function (x) { return x.key.indexOf(q) !== -1; }).slice(0, 8);
		if (!hits.length) {
			results.appendChild(el('li', 'sst-map-admin__noresult', 'Nič sa nenašlo'));
		}
		hits.forEach(function (x) {
			if (x.isle) {
				var g = x.isle, have = visitedPlace(g[1], g[0]);
				var li2 = el('li');
				var b2 = el('button', have ? 'is-on' : '');
				b2.type = 'button';
				b2.appendChild(el('span', 'sst-map-admin__flag', flag(g[1])));
				b2.appendChild(el('span', 'sst-map-admin__label', x.label));
				b2.appendChild(el('span', 'sst-map-admin__state', have ? '✓ navštívené' : '+ pridať ostrov'));
				b2.addEventListener('click', function () {
					map.zoomTo(g[2], g[3], 12);
					if (have) { renderResults(); return; }
					post('sst_map_add_place', { name: g[0], lat: g[2], lng: g[3], iso: g[1], island: 1 }).then(function () { renderResults(); });
				});
				li2.appendChild(b2);
				results.appendChild(li2);
				return;
			}
			var on = !!data.units[x.su];
			var li = el('li');
			var b = el('button', on ? 'is-on' : '');
			b.type = 'button';
			b.appendChild(el('span', 'sst-map-admin__flag', flag(x.iso)));
			b.appendChild(el('span', 'sst-map-admin__label', x.label));
			b.appendChild(el('span', 'sst-map-admin__state', on ? '✓ navštívené' : '+ vyznačiť'));
			b.addEventListener('click', function () {
				map.focusUnit(x.su);
				if (on) { renderResults(); return; }
				toggle(x.su, true).then(function () { renderResults(); });
			});
			li.appendChild(b);
			results.appendChild(li);
		});
		results.hidden = false;
	}
	search.addEventListener('input', renderResults);
	search.addEventListener('keydown', function (e) {
		if (e.key === 'Enter') {
			e.preventDefault();
			var first = results.querySelector('button');
			if (first) first.click();
		}
		if (e.key === 'Escape') { search.value = ''; renderResults(); }
	});

	/* ---------- Bublina pri kliknutí do mapy ---------- */

	function closePop() {
		pop.hidden = true;
		pop.textContent = '';
	}

	function openPop(x, y) {
		var rect = map.canvas.getBoundingClientRect();
		pop.style.left = Math.min(Math.max(x - rect.left, 150), rect.width - 150) + 'px';
		pop.style.top = Math.min(y - rect.top + 12, rect.height - 20) + 'px';
		pop.hidden = false;
		var tip = map.canvas.querySelector('[data-map-tip]');
		if (tip) tip.hidden = true;
	}

	function placeForm(info) {
		var su = info.unit;
		var u = su && data.all[su];
		closePop();
		if (!u) { toast('Klikni na pevninu alebo ostrov.', true); return; }
		var form = el('form', 'sst-map-admin__form');
		form.appendChild(el('p', 'sst-map-admin__where', flag(u.i) + ' ' + (u.m ? countryName(u.i) : countryName(u.i) + ' – ' + u.n)));
		var label = el('label', null, 'Názov miesta');
		var input = el('input');
		input.type = 'text';
		input.required = true;
		input.placeholder = 'Hľadám názov…';
		label.appendChild(input);
		form.appendChild(label);
		var islLabel = el('label', 'sst-map-admin__check');
		var isl = el('input');
		isl.type = 'checkbox';
		islLabel.appendChild(isl);
		islLabel.appendChild(document.createTextNode(' Je to ostrov (napr. Santorini, Koh Samui)'));
		form.appendChild(islLabel);
		form.appendChild(el('p', 'sst-map-admin__coords', info.at.lat.toFixed(4) + ', ' + info.at.lng.toFixed(4)));
		var actions = el('div', 'sst-map-admin__actions');
		var ok = el('button', 'button button-primary', 'Pridať miesto');
		ok.type = 'submit';
		var cancel = el('button', 'button', 'Zrušiť');
		cancel.type = 'button';
		cancel.addEventListener('click', closePop);
		actions.appendChild(ok);
		actions.appendChild(cancel);
		form.appendChild(actions);
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			ok.disabled = true;
			post('sst_map_add_place', { name: input.value, lat: info.at.lat, lng: info.at.lng, unit: su, island: isl.checked ? 1 : '' })
				.then(closePop, function () { ok.disabled = false; });
		});
		pop.appendChild(form);
		openPop(info.event.clientX, info.event.clientY);
		input.focus();

		// Návrh názvu podľa polohy (OpenStreetMap). Ak nie je dostupný, názov napíšeš sám.
		fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=10&accept-language=sk&lat=' + info.at.lat + '&lon=' + info.at.lng)
			.then(function (r) { return r.json(); })
			.then(function (j) {
				var a = j && j.address || {};
				// Na ostrove navrhneme názov ostrova (Santorini, nie Fira) a zaškrtneme „Je to ostrov“.
				if (a.island || a.archipelago) isl.checked = true;
				var n = a.island || a.city || a.town || a.village || a.municipality || a.county || a.state || '';
				if (n && !input.value) input.value = n;
				input.placeholder = 'napr. Ponta Delgada';
				input.select();
			})
			.catch(function () { input.placeholder = 'napr. Ponta Delgada'; });
	}

	function cityPop(info) {
		closePop();
		var iso = info.iso, c = data.countries[iso], city = null;
		if (c) c.c.forEach(function (x) { if (String(x.id) === String(info.city.id)) city = x; });
		if (!city) return;
		var box = el('div', 'sst-map-admin__form');
		box.appendChild(el('p', 'sst-map-admin__where', (c.f || '') + ' ' + city.n));
		box.appendChild(el('p', 'sst-map-admin__coords', window.SSTMap.summary({ c: [], p: city.p }) || 'bez článkov'));
		var actions = el('div', 'sst-map-admin__actions');
		if (city.e) {
			var edit = el('a', 'button', 'Upraviť');
			edit.href = city.e;
			actions.appendChild(edit);
		}
		if (!city.p) {
			var del = el('button', 'button button-link-delete', 'Odstrániť');
			del.type = 'button';
			del.addEventListener('click', function () {
				if (!window.confirm('Odstrániť miesto „' + city.n + '“ z Destinácií?')) return;
				post('sst_map_delete_place', { id: city.id }).then(closePop);
			});
			actions.appendChild(del);
		}
		var close = el('button', 'button', 'Zavrieť');
		close.type = 'button';
		close.addEventListener('click', closePop);
		actions.appendChild(close);
		box.appendChild(actions);
		pop.appendChild(box);
		openPop(info.event.clientX, info.event.clientY);
	}

	/* ---------- Režimy ---------- */

	root.querySelectorAll('[data-mode]').forEach(function (b) {
		b.addEventListener('click', function () {
			mode = b.getAttribute('data-mode');
			root.querySelectorAll('[data-mode]').forEach(function (x) {
				var on = x === b;
				x.classList.toggle('is-active', on);
				x.setAttribute('aria-checked', on ? 'true' : 'false');
			});
			mapEl.classList.toggle('is-places', mode === 'places');
			closePop();
			root.querySelector('[data-hint]').textContent = mode === 'places'
				? 'Klikni do mapy na miesto, kde si bol – pridá sa do Destinácií (aj s krajinou, ak ešte chýba). Priblíž si mapu pre presnejšiu polohu.'
				: 'Kliknutím na štát alebo ostrov ho vyznačíš alebo zrušíš. Kolieskom myši alebo +/− priblížiš, ťahaním posunieš.';
		});
	});

	map = window.SSTMap.mount(mapEl, {
		data: data,
		admin: true,
		onClick: function (info) {
			if (busy) return true;
			if (info.city) { cityPop(info); return true; }
			if (mode === 'places') { placeForm(info); return true; }
			closePop();
			var su = info.unit;
			if (!su || !data.all[su]) return true;
			toggle(su, !data.units[su]);
			return true;
		},
	});
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closePop(); });
	renderList();
})();
