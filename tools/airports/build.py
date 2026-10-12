"""Súradnice letísk pre počasie v letenkách: includes/airport-coords.php.

Zdroj: OurAirports (verejná doména), letiská s pravidelnými linkami (veľké a stredné)
a všetky kódy zo slovníka includes/airports.php. Spustenie:
  curl -L -o airports.csv https://raw.githubusercontent.com/davidmegginson/ourairports-data/main/airports.csv
  python3 build.py airports.csv
"""
import csv
import re
import sys
from pathlib import Path

PLUGIN = Path(__file__).resolve().parents[2] / 'wp-content/plugins/spoznajsvet-letenky/includes'
# Mestské kódy (viac letísk) – stred mesta.
CITY = {'BJS': (39.90, 116.41), 'KIV': (47.01, 28.86), 'OSA': (34.69, 135.50), 'REP': (13.36, 103.86),
        'SEL': (37.57, 126.98), 'TYO': (35.68, 139.69), 'LON': (51.51, -0.13), 'PAR': (48.86, 2.35),
        'ROM': (41.90, 12.50), 'MIL': (45.46, 9.19), 'NYC': (40.71, -74.01), 'MOW': (55.76, 37.62),
        'STO': (59.33, 18.07), 'OSL': (59.91, 10.75), 'BUH': (44.43, 26.10), 'IST': (41.01, 28.98)}


def main(src):
    known = set(re.findall(r"'([A-Z]{3})'\s*=>", (PLUGIN / 'airports.php').read_text()))
    rows = {}
    for r in csv.DictReader(open(src, encoding='utf-8')):
        code = r['iata_code']
        if not re.match(r'^[A-Z]{3}$', code or ''):
            continue
        if code not in known and (r['scheduled_service'] != 'yes' or r['type'] not in ('large_airport', 'medium_airport')):
            continue
        rows[code] = (round(float(r['latitude_deg']), 2), round(float(r['longitude_deg']), 2))
    for code, ll in CITY.items():
        rows.setdefault(code, ll)
    body = ''.join("'%s'=>[%s,%s]," % (k, v[0], v[1]) for k, v in sorted(rows.items()))
    out = PLUGIN / 'airport-coords.php'
    out.write_text(
        "<?php\n// Súradnice letísk (OurAirports, verejná doména) – vygenerované tools/airports/build.py.\n"
        "defined( 'ABSPATH' ) || exit;\n// phpcs:ignoreFile\nreturn [" + body + "];\n"
    )
    print(out, len(rows))


if __name__ == '__main__':
    main(sys.argv[1])
