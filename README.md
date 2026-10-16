# Spoznaj Svet – WordPress téma + letenky

Osobný cestovateľský blog pre [spoznajsvet.sk](https://www.spoznajsvet.sk/).

Testované na **WordPress 7.1.3** (najnovšia verzia k 11. 10. 2026), PHP 7.4+.

```
dist/
├── spoznajsvet.zip                  téma vrátane leteniek – stačí nahrať len tento súbor
└── spoznajsvet-letenky.zip          voliteľne: letenky ako samostatný plugin
wp-content/
├── themes/spoznajsvet/              zdrojové súbory témy
└── plugins/spoznajsvet-letenky/     zdrojové súbory pluginu
preview/                             statický HTML náhľad (ukážkový obsah)
```

## Inštalácia

1. *Vzhľad → Témy → Pridať tému → Nahrať tému* → `dist/spoznajsvet.zip` → Inštalovať → **Aktivovať**.
   Téma pri aktivácii vytvorí stránky **Destinácie** (`/destinacie/`) a **Letenky** (`/letenky/`).
   Ak mal web ako úvod „najnovšie články“, vytvorí aj stránky *Domov* a *Články* a nastaví ich v *Nastavenia → Čítanie*.
2. *Nastavenia → Trvalé odkazy* → Uložiť (obnoví URL adresy).
3. *Vzhľad → Prispôsobiť → Spoznaj Svet*: úvodný obrázok, texty, „O mne“, sociálne siete.

Letenky sú **pribalené priamo v ZIP-e témy** (`inc/letenky/`), samostatný plugin netreba.
`dist/spoznajsvet-letenky.zip` je voliteľný – ak ho nainštalujete a aktivujete, má prednosť pred kópiou v téme.

## Krajiny a mestá

V administrácii *Články → Destinácie*:

- **Krajina** = položka bez nadradenej (napr. *Taliansko*). Vyplňte **kód krajiny** (IT → 🇮🇹), **kontinent** a voliteľne **titulnú fotku**.
- **Mesto** = položka s nadradenou krajinou (napr. *Florencia* → rodič *Taliansko*).
- Pri článku zaškrtnite mesto (alebo viac miest) – krajina sa doplní automaticky.
- Pri článku v bočnom paneli **O ceste** vyplňte „Kedy“ (napr. *Leto 2024*) a počet dní – zobrazí sa na kartách.

URL: `/destinacie/taliansko/`, `/destinacie/taliansko/florencia/`.

## Letenky

*Nastavenia → Letenky*

| Zdroj | Čo prináša | Kľúč |
|---|---|---|
| **Ryanair** | najlacnejšia spiatočná letenka do každej destinácie z BTS/VIE | netreba |
| **Wizz Air** | cenový kalendár na linkách z VIE/BUD (aj Abu Dhabi/Dubaj, ak ich Wizz lieta) | netreba |
| **Aviasales (Travelpayouts)** | klasické aerolínie – Qatar, Emirates, Etihad, Turkish… hlavne pre **Bangkok, Dubaj, Abu Dhabi** | bezplatný token z travelpayouts.com |
| **Open-Meteo** | počasie v destinácii ku každej letenke: do 16 dní predpoveď, neskôr odhad „~“ podľa minulých rokov | netreba |
| **Kayak, momondo** | ku každej ponuke odkaz „porovnať“ s rovnakou trasou a dátumami; akcie odtiaľ môžete vložiť ako **ručné tipy** | – |

Kayak ani momondo nemajú verejné API a ich automatické sťahovanie porušuje ich podmienky. Preto slúžia ako porovnávač (odkazy) a zdroj ručných tipov. Ryanair a Wizz Air používajú neoficiálne API svojich webov – keď ho zmenia alebo dočasne zablokujú, plugin ukazuje posledné platné ceny (max. 24 h) a chybu uvidíte v *Nastavenia → Letenky → Stav*.

**Predvolené nastavenie**

- Ázia a exotika (spiatočné): z `VIE, BTS, BUD`, pobyt 5 – 21 nocí, horizont 6 mesiacov; rebríček z Aviasales, Wizz Air a Ryanair (oblasti: Ázia, tropy, severná Afrika; voliteľne Blízky východ) + karty `BKK, DXB, AUH`
- Poradie: najprv Európa, potom exotika.
- Európa (spiatočné): z `BTS, VIE`, pobyt 2 – 7 nocí, horizont 90 dní, max. 150 €, len Európa

**Použitie:** stránka *Letenky* (šablóna), úvodná stránka, shortcode
`[spoznajsvet_letenky]`, `[spoznajsvet_letenky skupina="exotika"]`, `[spoznajsvet_letenky skupina="europa" limit="20"]`
alebo blok **Letenky** v editore. REST: `/wp-json/spoznajsvet/v1/letenky`.

### Aktualizácia každých 30 minút, na vyžiadanie max. 15 minút staré

Plugin sa naplánuje do WP-Cron. WP-Cron však beží iba pri návšteve webu, preto na produkcii odporúčame skutočný cron:

```
# wp-config.php
define( 'DISABLE_WP_CRON', true );

# crontab na hostingu (každých 30 min)
*/30 * * * * curl -s https://www.spoznajsvet.sk/wp-cron.php?doing_wp_cron > /dev/null
```

**Na vyžiadanie:** keď niekto otvorí stránku s letenkami alebo klikne na **Obnoviť** a ceny sú staršie ako 15 minút, plugin ich začne obnovovať na pozadí (`POST /wp-json/spoznajsvet/v1/letenky/obnovit`). Stránka sa načíta hneď a nové ceny si doplní sama, bez obnovenia. Mladšie ceny sa znova nesťahujú a súbežné požiadavky spustia len jednu obnovu.
Wizz Air obnovuje linky postupne (každá linka max. raz za hodinu), aby ho plugin nezahltil.

Ručne: tlačidlo **Aktualizovať teraz** v administrácii alebo `wp letenky refresh` (WP-CLI).

## Dizajn

- Mobile-first, na mobile **spodná navigácia** ako v aplikácii (Domov · Destinácie · **Letenky** · Články · Hľadať), stále viditeľná, v štýle Apple **Liquid Glass** (sklenená kapsula, šošovka pod aktívnou položkou, okrúhle tlačidlo hľadania). Hore je navyše tlačidlo ☰ s celým menu.
  Vlastné položky: menu „Spodná navigácia“, ikonu určíte CSS triedou položky (`ikona-home`, `ikona-globe`, `ikona-plane`, `ikona-book`, `ikona-search`, `ikona-user`, `ikona-map`).
- Tmavý „teal“ hero s kartami krajín (karusel), karty článkov s fotkou (štýl „My trips“), sklenené (glass) prvky, tmavý režim podľa systému.
- `theme.json`: farby, písma a veľkosti témy sú dostupné aj v blokovom editore.
- Písma sú pribalené v téme (žiadne Google Fonts → rýchlejšie a bez GDPR problémov). Predvolené **Manrope + Instrument Serif**, prepnúť sa dá v *Prispôsobiť → Spoznaj Svet → Písmo* (aj Figtree + Fraunces alebo pôvodné Plus Jakarta Sans + Playfair Display).

## Mapa sveta

Na úvodnej stránke a na stránke *Destinácie* je mapa „Kde všade som bol“ (inde shortcode `[spoznajsvet_mapa]`, bez nadpisu `[spoznajsvet_mapa nadpis="0"]`). Nad mapou je počet **kontinentov, štátov, ostrovov** (navštívené súostrovia a ostrovy ako Sicília, Azory, Havaj) a percento sveta.

**Vyznačenie v administrácii – *Články → Mapa ciest***

- Režim **Vyznačiť štáty a ostrovy**: klik na štát alebo ostrov ho vyznačí / zruší. Malé ostrovy nájdete aj cez vyhľadávanie („Azory“, „Havaj“…). V zozname vpravo má každý štát prepínače svojich častí (napr. Portugalsko: Pevnina · Azory · Madeira).
- Režim **Pridať miesto**: klik do mapy → názov miesta (navrhne sa z OpenStreetMap) → miesto sa pridá do *Destinácií*, aj s krajinou, ak chýba.
- Štáty a mestá z *Destinácií* (s vyplneným kódom krajiny) sú na mape automaticky – vyfarbí sa časť, v ktorej mesto leží (Palermo → Sicília). Ručne ich môžete skryť.
- Sýtosť farby podľa počtu miest v štáte: 1 · 2 – 3 · 4 – 6 · 7 a viac. Mestá sú body; polohu známych miest téma pozná, ostatné dohľadá cez OpenStreetMap, presná poloha sa dá zadať pri meste.

Mapa je v téme (`assets/map/`, Natural Earth 1:10m, štáty aj súostrovia): prehľadová verzia (~35 kB) sa načíta hneď, podrobná (~290 kB) až pri priblížení. Nový súbor vygeneruje `tools/map` (`npm install && npm run fetch && npm run build`).

## Predvolené obrázky

Téma obsahuje ilustrácie (`assets/img/destinacie/`) pre Taliansko, Rím, Florenciu, Benátky, Amalfi, Toskánsko, Sicíliu/Palermo, Thajsko, Bangkok, Phuket, SAE, Dubaj, Abu Dhabi, Chorvátsko, Island a Portugalsko a obrázok úvodného banneru (`assets/img/hero.jpg`).

Poradie, odkiaľ sa berie obrázok destinácie:
1. titulná fotka nastavená pri krajine/meste,
2. náhľadový obrázok najnovšieho článku z destinácie,
3. ilustrácia z témy podľa názvu (pri meste podľa krajiny), inak jedna z ponuky.

Vypnutie ilustrácií: `add_filter( 'sst_use_default_covers', '__return_false' );`

## Náhľad

`preview/index.html` – statická kópia vygenerovaná z lokálneho WordPressu. Obsah, obrázky (generované ilustrácie) a ceny leteniek sú **ukážkové**.

## Nové ZIP balíky

Po úprave zdrojových súborov: `python3 build.py` → vytvorí `dist/spoznajsvet.zip` a `dist/spoznajsvet-letenky.zip`.
