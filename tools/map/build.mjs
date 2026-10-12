// Mapa sveta pre tému – spustenie: npm install && npm run build
// Zdroj: Natural Earth 1:10m „admin 0 map subunits“ (štáty aj samostatné súostrovia – Azory, Madeira, Kanárske ostrovy…).
// prep.js zlúči politické regióny (units.js), mapshaper zjednoduší na dve úrovne: world.svg (prehľad, načíta sa hneď) a world-detail.svg (pri priblížení).
// world.json: parametre projekcie Equal Earth + zoznam častí (kód štátu, kontinent, názov, hlavná časť).
import fs from 'fs';
import { geoEqualEarth, geoPath, geoArea } from 'd3-geo';

const [lowSrc, highSrc, outDir] = process.argv.slice(2);
const read = (f) => {
  const g = JSON.parse(fs.readFileSync(f));
  g.features = g.features.filter((x) => x.geometry);
  // d3 čaká vonkajšie okraje v smere hodinových ručičiek; mapshaper ich má opačne → otočíme.
  for (const f of g.features) {
    const geo = f.geometry;
    const polys = geo.type === 'Polygon' ? [geo.coordinates] : geo.type === 'MultiPolygon' ? geo.coordinates : [];
    if (geoArea(f) > 2 * Math.PI) polys.forEach((p) => p.forEach((r) => r.reverse()));
  }
  return g;
};
const low = read(lowSrc), high = read(highSrc);
const W = 1000;
const proj = geoEqualEarth().fitWidth(W, high);
const H = Math.ceil(geoPath(proj).bounds(high)[1][1]);
const units = {}, area = {};
const svg = (fc, digits) => {
  const path = geoPath(proj).digits(digits);
  let s = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}">`;
  for (const f of fc.features) {
    const p = f.properties, d = path(f), su = p.UNIT, iso = p.ISO || '';
    if (!d) continue;
    if (!units[su]) {
      units[su] = { i: iso, c: p.CONT || '', n: p.NAME };
      area[su] = 0;
    }
    area[su] = Math.max(area[su], geoArea(f));
    s += `<path data-unit="${su}"${iso ? ` data-iso="${iso}"` : ''} d="${d}"/>`;
  }
  return s + '</svg>';
};
const lowSvg = svg(low, 1), highSvg = svg(high, 2);
// Hlavná časť štátu = najväčšia (napr. pevninské Portugalsko, nie Azory).
const main = {};
for (const [su, u] of Object.entries(units)) if (u.i && (!main[u.i] || area[su] > area[main[u.i]])) main[u.i] = su;
for (const su of Object.values(main)) units[su].m = 1;
fs.writeFileSync(outDir + '/world.svg', lowSvg);
fs.writeFileSync(outDir + '/world-detail.svg', highSvg);
fs.writeFileSync(outDir + '/world.json', JSON.stringify({ w: W, h: H, scale: proj.scale(), tx: proj.translate()[0], ty: proj.translate()[1], units }));
console.log('units', Object.keys(units).length, 'low', lowSvg.length, 'high', highSvg.length, 'h', H);
