"""Zabalí tému a plugin do ZIP súborov na nahratie do WordPressu (dist/).

ZIP témy obsahuje aj kópiu pluginu Letenky (inc/letenky/), takže na webe stačí nahrať
len tému. Samostatný ZIP pluginu ostáva pre tých, ktorí ho chcú spravovať zvlášť.
"""
import os
import zipfile

ROOT = os.path.dirname(os.path.abspath(__file__))
THEME = os.path.join(ROOT, 'wp-content/themes/spoznajsvet')
PLUGIN = os.path.join(ROOT, 'wp-content/plugins/spoznajsvet-letenky')


def add_dir(z, src, prefix):
    for root, dirs, files in os.walk(src):
        dirs[:] = sorted(d for d in dirs if not d.startswith('.'))
        for f in sorted(files):
            if f.startswith('.'):
                continue
            full = os.path.join(root, f)
            z.write(full, os.path.join(prefix, os.path.relpath(full, src)))


def pack(out, parts):
    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
        for src, prefix in parts:
            add_dir(z, src, prefix)
    print(out)


os.makedirs(os.path.join(ROOT, 'dist'), exist_ok=True)
pack(os.path.join(ROOT, 'dist/spoznajsvet.zip'), [(THEME, 'spoznajsvet'), (PLUGIN, 'spoznajsvet/inc/letenky')])
pack(os.path.join(ROOT, 'dist/spoznajsvet-letenky.zip'), [(PLUGIN, 'spoznajsvet-letenky')])
