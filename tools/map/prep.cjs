// Pred mapshaperom: doplní pole UNIT/ISO/NAME/CONT, aby sa dalo zlúčiť (-dissolve2 UNIT).
const fs = require('fs'); const U = require('./units.cjs');
const [src, out] = process.argv.slice(2);
const g = JSON.parse(fs.readFileSync(src));
const cont = { Europe: 'europa', Asia: 'azia', Africa: 'afrika', 'North America': 'amerika', 'South America': 'amerika', Oceania: 'oceania' };
g.features = g.features.filter(f => f.properties.CONTINENT !== 'Antarctica').map(f => {
  const p = f.properties, su = p.SU_A3, unit = U.merge[su] || su;
  let iso = p.ISO_A2_EH && p.ISO_A2_EH !== '-99' ? p.ISO_A2_EH : (p.ISO_A2 !== '-99' ? p.ISO_A2 : '');
  if (p.ADM0_A3 === 'KOS') iso = 'XK';
  let name = p.SUBUNIT;
  if (U.head[unit]) { iso = U.head[unit][0]; name = U.head[unit][1]; }
  if (!/^[A-Z]{2}$/.test(iso)) iso = '';
  let c = cont[p.CONTINENT] || cont[p.REGION_UN] || '';
  if (unit === 'RUA') c = 'europa';
  return { type: 'Feature', properties: { UNIT: unit, ISO: iso, NAME: name, CONT: c }, geometry: f.geometry };
});
fs.writeFileSync(out, JSON.stringify(g));
console.log('features', g.features.length);
