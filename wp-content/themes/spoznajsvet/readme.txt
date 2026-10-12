=== Spoznaj Svet ===
Contributors: spoznajsvet
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.14.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Moderná téma pre osobný cestovateľský blog spoznajsvet.sk.

== Description ==

* Články zoradené podľa krajín a miest (Články → Destinácie: krajina → mesto).
* Mobilná spodná navigácia ako v aplikácii (stále viditeľná) a hamburger menu.
* Úvodná stránka s kartami krajín, najnovšími príbehmi a najlacnejšími letenkami.
* Stránky Destinácie a Letenky sa vytvoria automaticky pri aktivácii.
* Letenky (Ryanair, Wizz Air, Aviasales, Kayak, momondo) sú súčasťou témy – Nastavenia → Letenky.
* Tmavý režim podľa systému, farby a písma témy v blokovom editore (theme.json).

== Installation ==

1. Vzhľad → Témy → Pridať tému → Nahrať tému → vyberte spoznajsvet.zip → Inštalovať → Aktivovať.
2. Nastavenia → Trvalé odkazy → Uložiť zmeny.
3. Vzhľad → Prispôsobiť → Spoznaj Svet (úvodný obrázok, texty, O mne, sociálne siete).

== Changelog ==

= 1.14.0 =
* Aktualizácia každých 30 minút a na vyžiadanie: pri návšteve alebo kliknutí na „Obnoviť“ sa ceny staršie ako 15 minút obnovia na pozadí a stránka si ich doplní sama.

= 1.13.0 =
* Nový dizajn výpisu leteniek ako palubný lístok: trasa (napr. VIE ✈ RAK), termín tam a späť s počtom nocí, aerolinka a výrazná celková cena; celá karta je klikateľná.

= 1.12.1 =
* Letenky: len celková cena tam aj späť, bez rozpisu na jednotlivé smery.

= 1.12.0 =
* Exotika (rebríček): okrem Aviasales aj Wizz Air (všetky jeho linky do Ázie a exotiky, napr. z Budapešti
  do Saudskej Arábie, Kazachstanu, Uzbekistanu, Emirátov) a Ryanair – z Viedne, Bratislavy aj Budapešti.
* Nastaviteľné oblasti rebríčka (Ázia, tropy, severná Afrika, Blízky východ) a dĺžka pobytu (predvolene 5 – 21 nocí).

= 1.11.0 =
* Všetky letenky sú spiatočné a pri každej je celková cena „celkom tam aj späť“.
* Rozpis ceny „tam X € + späť Y €“ (Ryanair a Wizz Air), štítok na stránke krajiny „letenka tam aj späť od …“.

= 1.10.0 =
* Responzívne fotky v článkoch: nikdy nepretečú obrazovku ani sa nedeformujú (aj fotky
  zo starého editora s pevnou šírkou/výškou a popisom [caption]).
* Fotky zarovnané vľavo/vpravo sú na mobile cez celú šírku, od tabletu ich text obteká.
* Fotky „široké“ a „na celú šírku“ sú presne vycentrované; článok je v jednom stĺpci,
  údaje „O ceste“ sú nad textom.
* Kliknutím sa fotka zväčší na celú obrazovku (WordPress lightbox).

= 1.9.0 =
* Európske destinácie: len spiatočné letenky (Ryanair roundTripFares, Wizz Air tam a späť, Aviasales spiatočné).
* Nové nastavenie dĺžky pobytu pre Európu (predvolene 2 – 7 nocí).
* Pri každej európskej ponuke termín tam – späť a počet nocí.

= 1.8.0 =
* Nový dizajn stránky Články: hlavička s počtom príbehov, vyhľadávaním a filtrom krajín,
  zvýraznený najnovší príbeh, karty s textom pod fotkou (úryvok, kedy, dĺžka, čas čítania).
* Na mobile kompaktný zoznam ako v aplikácii; tablet 2 stĺpce, počítač 3 stĺpce.

= 1.7.0 =
* Ázia a exotika: rebríček najlacnejších spiatočných leteniek do celej Ázie a exotiky (jedna na mesto).
* Karty Bangkok/Dubaj/Abu Dhabi ukazujú len jednu najlacnejšiu letenku. Všade len spiatočné letenky.
* Wizz Air pre exotiku hľadá spiatočné lety (pobyt 5–21 nocí).
* Ponuky sa otvárajú cez tlačidlá – pri prejdení myšou sa v rohu prehliadača neukazuje adresa.

= 1.6.0 =
* Exotika: každý let s cenou, leteckou spoločnosťou (logo + názov), dátumami a počtom prestupov.
* Záložné vyhľadávanie Travelpayouts, keď hlavné nič nenájde.
* Návod na získanie tokenu priamo na stránke Letenky (vidí ho len admin).

= 1.5.0 =
* Letenky sú pribalené priamo v téme (inc/letenky) – netreba inštalovať samostatný plugin.
  Ak je samostatný plugin aktívny, má prednosť.

= 1.4.0 =
* Spodná mobilná navigácia v štýle Apple Liquid Glass: sklenená kapsula, posuvná šošovka
  pod aktívnou položkou, samostatné okrúhle tlačidlo vyhľadávania. Rešpektuje „Znížiť priehľadnosť“.

= 1.3.0 =
* Predvolené ilustrácie destinácií a úvodného banneru pribalené v téme (assets/img).
  Použijú sa, kým krajina/mesto nemá vlastnú titulnú fotku ani článok s fotkou.

= 1.2.0 =
* Nové moderné písmo Manrope + Instrument Serif, pribalené v téme (bez Google Fonts).
* Výber z 3 kombinácií písma v Prispôsobení.
* Nové texty (sólo cestovanie aj v partii), štítky v sekcii O mne.

= 1.1.0 =
* Kompatibilita s WordPress 7.1, pridaný theme.json (farby a písma v editore).
* Spodná navigácia na mobile je stále viditeľná.
* Nové mobilné menu, rozbaľovacie menu Destinácie.

= 1.0.0 =
* Prvá verzia.

== Copyright ==

Spoznaj Svet, (C) 2026 Spoznaj Svet. Licencia GPLv2 or later.
Ikony podľa Lucide (ISC). Písma Manrope, Instrument Serif, Figtree, Fraunces, Plus Jakarta Sans a Playfair Display (SIL Open Font License), pribalené v assets/fonts.
Ilustrácie destinácií v assets/img vytvorené pre túto tému, licencia GPLv2 or later.
