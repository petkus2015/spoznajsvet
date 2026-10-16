import fs from 'fs'; import { geoEqualEarth, geoPath, geoArea } from 'd3-geo';
// Vrstva malých ostrovov (Santorini, Koh Tao…), ktoré sa do podrobnej mapy nezmestia – rovnaká projekcia ako world.json.
const meta = JSON.parse(fs.readFileSync(process.argv[4]));
const proj = geoEqualEarth().scale(meta.scale).translate([meta.tx, meta.ty]);
const path = geoPath(proj).digits(2);
const g = JSON.parse(fs.readFileSync(process.argv[2]));
let s = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${meta.w} ${meta.h}">`, n = 0;
for (const f of g.features) { const geo=f.geometry; if(!geo) continue; if (geoArea(f) > 2*Math.PI) { (geo.type==='Polygon'?[geo.coordinates]:geo.coordinates).forEach(p=>p.forEach(r=>r.reverse())); }
  const d = path(f); if (!d) continue; n++; s += `<path data-unit="${f.properties.UNIT}"${f.properties.ISO ? ` data-iso="${f.properties.ISO}"` : ''} d="${d}"/>`; }
fs.writeFileSync(process.argv[3], s + '</svg>'); console.log('islands', n, 'bytes', s.length);
