// Mapa sveta pre tému: npm install && npm run build. Parametre projekcie (scale, tx, ty) vypíše do world.json – musia sedieť so sst_map_meta() v inc/map.php.
import fs from 'fs';
import { feature } from 'topojson-client';
import { presimplify, simplify, quantile } from 'topojson-simplify';
import { geoEqualEarth, geoPath } from 'd3-geo';
import countries from 'i18n-iso-countries';

const res = process.argv[2] || '50m';
const q = parseFloat(process.argv[3] || '0.5');
let topo = JSON.parse(fs.readFileSync(`node_modules/world-atlas/countries-${res}.json`));
topo = presimplify(topo);
topo = simplify(topo, quantile(topo, q));
const fc = feature(topo, topo.objects.countries);
fc.features = fc.features.filter(f => f.id !== '010' && f.properties.name !== 'Antarctica');
const W = 1000;
const proj = geoEqualEarth().fitWidth(W, fc);
const path = geoPath(proj).digits(1);
const [[, y0], [, y1]] = path.bounds(fc);
const H = Math.ceil(y1);
const special = { Kosovo: 'XK', 'N. Cyprus': 'CY', Somaliland: 'SO' };
const out = {};
let land = [];
for (const f of fc.features) {
  let iso = f.id ? countries.numericToAlpha2(f.id) : null;
  if (!iso) iso = special[f.properties.name] || null;
  const d = path(f);
  if (!d) continue;
  if (!iso) { land.push(d); continue; }
  if (out[iso]) out[iso].d += d; else out[iso] = { n: f.properties.name, d };
}
const data = { w: W, h: H, scale: proj.scale(), tx: proj.translate()[0], ty: proj.translate()[1], countries: out, land: land.join('') };
fs.writeFileSync(process.argv[4] || 'world.json', JSON.stringify(data));
if (process.argv[5]) { let s = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + W + ' ' + H + '">'; if (data.land) s += '<path class="land" d="' + data.land + '"/>'; for (const [k, v] of Object.entries(out)) s += '<path data-iso="' + k + '" d="' + v.d + '"/>'; fs.writeFileSync(process.argv[5], s + '</svg>'); }
console.log(res, q, 'h', H, 'countries', Object.keys(out).length, 'unassigned', land.length, 'bytes', JSON.stringify(data).length, 'y0', y0);
