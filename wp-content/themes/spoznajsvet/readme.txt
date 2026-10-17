=== Spoznaj Svet ===
Contributors: spoznajsvet
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.22.0
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

= 1.22.0 =
* Najnavštevovanejšie mestá Európy v novom prehľadnom dizajne (zoznam: vlajka, mesto, trasa a aerolinka, termín, počasie, cena, celý riadok klikateľný). Na webe sa už nepíše „nízkonákladovka / klasická linka“ – uprednostnenie nízkonákladoviek ostáva len v pozadí.

= 1.21.1 =
* Exotika (rebríček aj karty Bangkok, Dubaj, Abu Dhabi): len nízkonákladovky; klasická linka iba tam, kam nízkonákladovka nelieta (so štítkom „Klasická linka“).

= 1.21.0 =
* Bangkok, Dubaj, Abu Dhabi: ako pri obľúbených mestách – najprv najlacnejšia nízkonákladovka (Wizz Air, flydubai, Air Arabia…), pod ňou klasická linka. Dubaj zahŕňa aj letisko Al Maktoum (DWC), kam lieta Wizz Air, a Šardžá (SHJ); Bangkok aj Don Mueang (DMK).

= 1.20.0 =
* Nová sekcia „Najnavštevovanejšie mestá Európy“ (Londýn, Paríž, Istanbul, Rím, Barcelona, Amsterdam, Miláno… 24 miest, všetky letiská mesta spolu): najlacnejšia spiatočná letenka – najprv nízkonákladovky (Ryanair, Wizz Air, easyJet, Vueling, Pegasus…), klasické aerolinky len tam, kde nízkonákladovka nelieta. Shortcode [spoznajsvet_letenky skupina="top"]. Na stránke Letenky medzi Európou a exotikou.

= 1.19.1 =
* Mapa na mobile: záložné farby pre staršie iPhony (bez color-mix), záložná výška bez aspect-ratio (iOS 14), chyba pri zvýraznení ostrovov už nezastaví celú mapu, výpočet bez limitu argumentov v Safari.

= 1.19.0 =
* Malé ostrovy na mape: Santorini, Mykonos, Korfu, Phuket, Koh Samui, Hvar, Capri… – navštívený ostrov sa vyfarbí zlatou, veľmi malé (napr. Koh Tao) majú zlatý krúžok.
* Pridanie ostrova: v Články → Mapa ciest vyhľadávaním (vyše 130 obľúbených ostrovov s polohou) alebo kliknutím do mapy s voľbou „Je to ostrov“ (OpenStreetMap ju zaškrtne sám); pri mieste v Destináciách políčko „Ostrov“.
* Ostrov sa rozpozná aj podľa názvu miesta (Phuket, Santorini) a pri mestách na samostatných častiach mapy (Palermo → Sicília).
* Vrstva malých ostrovov (~195 kB) sa načíta až pri veľkom priblížení.

= 1.18.3 =
* Štatistiky nad mapou: kontinenty, štáty, ostrovy (navštívené súostrovia a ostrovy ako Sicília, Azory, Havaj) a % sveta – bez počtu miest.

= 1.18.2 =
* Mapa: kód krajiny sa doplní sám podľa názvu destinácie (Taliansko → IT, Anglicko → GB…), mapa sa už neskryje potichu – admin vidí, čo doplniť; na úvodnej stránke aj bez článkov; záloha dát cez REST.
* Prechod zo starej témy: kód tagDiv Composer / WPBakery ([tdc_zone …], [vc_row …]) sa na webe nezobrazuje, v Stránkach upozornenie a tlačidlo „Vyčistiť starý kód“ (pôvodný obsah ostane v revíziách).
* Stránky Destinácie a Letenky dostanú šablónu témy aj po nahratí novej verzie (aj keď mali šablónu zo starej témy).

= 1.18.1 =
* Zrušená animácia názvu krajiny a mesta z 1.18.0 – nadpis je opäť ako predtým.

= 1.17.0 =
* Počasie v destinácii pri každej letenke (ikona + najvyššia denná teplota počas pobytu): pri odlete do 16 dní predpoveď, pri neskoršom odlete odhad podľa rovnakého obdobia v minulých rokoch so znakom ~ (Open-Meteo, bez kľúča). Dá sa vypnúť v Nastavenia → Letenky.

= 1.16.0 =
* Podrobná mapa (Natural Earth 1:10m): štáty aj samostatné ostrovy a súostrovia – Azory, Madeira, Kanárske a Baleárske ostrovy, Sicília, Sardínia, Korzika, Havaj…
  Pri priblížení sa načíta podrobná verzia, malé navštívené ostrovy majú krúžok.
* Automaticky sa vyfarbí časť, v ktorej leží mesto (Palermo → Sicília, Ponta Delgada → Azory).
* Nová stránka Články → Mapa ciest: vyznačenie štátov a ostrovov kliknutím, vyhľadávanie, pridanie miesta kliknutím do mapy (návrh názvu z OpenStreetMap), zoznam navštívených.
* Na webe štatistiky: počet štátov, miest, kontinentov a % sveta.
* Letenky: najprv Európa, potom exotika.

= 1.15.0 =
* Mapa sveta „Kde všade som bol“ na úvodnej stránke a na stránke Destinácie (aj shortcode [spoznajsvet_mapa]):
  navštívené krajiny sú vyfarbené – čím viac miest, tým silnejšia farba; mestá sú body na mape.
* Priblíženie oblastí (Moje cesty, Svet, Európa, Ázia…), posun myšou/prstom, priblíženie dvoma prstami a +/−.
* Kliknutím na krajinu sa zobrazí karta s mestami a odkazom na krajinu.
* Pri meste nové pole „Poloha mesta na mape“; ak je prázdne, poloha sa doplní automaticky (vstavaný zoznam miest, inak OpenStreetMap).

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
