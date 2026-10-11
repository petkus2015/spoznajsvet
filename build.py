"""Zabalí tému a plugin do ZIP súborov na nahratie do WordPressu (dist/)."""
import os
import zipfile

ROOT = os.path.dirname(os.path.abspath(__file__))


def pack(src, name, out):
    with zipfile.ZipFile(out, 'w', zipfile.ZIP_DEFLATED) as z:
        for root, dirs, files in os.walk(src):
            dirs[:] = sorted(d for d in dirs if not d.startswith('.'))
            for f in sorted(files):
                if f.startswith('.'):
                    continue
                full = os.path.join(root, f)
                z.write(full, os.path.join(name, os.path.relpath(full, src)))
    print(out)


os.makedirs(os.path.join(ROOT, 'dist'), exist_ok=True)
pack(os.path.join(ROOT, 'wp-content/themes/spoznajsvet'), 'spoznajsvet', os.path.join(ROOT, 'dist/spoznajsvet.zip'))
pack(os.path.join(ROOT, 'wp-content/plugins/spoznajsvet-letenky'), 'spoznajsvet-letenky', os.path.join(ROOT, 'dist/spoznajsvet-letenky.zip'))
