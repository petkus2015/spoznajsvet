=== Spoznaj Svet – Letenky ===
Contributors: spoznajsvet
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.10.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Najlacnejšie aktuálne letenky (Ryanair, Wizz Air, Aviasales/Travelpayouts + porovnanie Kayak a momondo), aktualizované každých 30 minút, pri návšteve alebo na vyžiadanie nie staršie ako 15 minút.

== Description ==

* Exotika: Bangkok, Dubaj, Abu Dhabi z Viedne, Bratislavy a Budapešti (spiatočné).
* Európske mestá: najlacnejšie jednosmerné letenky z Bratislavy a Viedne.
* Shortcode [spoznajsvet_letenky], blok „Letenky“, REST /wp-json/spoznajsvet/v1/letenky.
* Nastavenia → Letenky: zdroje, token Travelpayouts, letiská, ručné tipy.
* WP-CLI: wp letenky refresh, wp letenky status.

== Changelog ==

= 1.10.0 =
* Nová sekcia „Najnavštevovanejšie mestá Európy“ (Londýn, Paríž, Istanbul, Rím, Barcelona, Amsterdam, Miláno… 24 miest, všetky letiská mesta spolu): najlacnejšia spiatočná letenka – najprv nízkonákladovky (Ryanair, Wizz Air, easyJet, Vueling, Pegasus…), klasické aerolinky len tam, kde nízkonákladovka nelieta. Shortcode [spoznajsvet_letenky skupina="top"].

= 1.9.0 =
* Počasie v destinácii pri každej letenke (ikona + najvyššia denná teplota počas pobytu): pri odlete do 16 dní predpoveď, pri neskoršom odlete odhad podľa rovnakého obdobia v minulých rokoch so znakom ~ (Open-Meteo, bez kľúča). Dá sa vypnúť v Nastavenia → Letenky.

= 1.8.1 =
* Najprv najlacnejšie ponuky v Európe, potom exotika.

= 1.8.0 =
* Aktualizácia každých 30 minút a na vyžiadanie: pri návšteve alebo kliknutí na „Obnoviť“ sa ceny staršie ako 15 minút obnovia na pozadí a stránka si ich doplní sama.

= 1.7.0 =
* Nový dizajn výpisu leteniek ako palubný lístok: trasa (napr. VIE ✈ RAK), termín tam a späť s počtom nocí, aerolinka a výrazná celková cena; celá karta je klikateľná.

= 1.6.1 =
* Pri letenkách sa zobrazuje len celková cena tam aj späť (bez rozpisu na jednotlivé smery).

= 1.6.0 =
* Exotika (rebríček): okrem Aviasales aj Wizz Air (všetky jeho linky do Ázie a exotiky, napr. z Budapešti
  do Saudskej Arábie, Kazachstanu, Uzbekistanu, Emirátov) a Ryanair – z Viedne, Bratislavy aj Budapešti.
* Nastaviteľné oblasti rebríčka (Ázia, tropy, severná Afrika, Blízky východ) a dĺžka pobytu (predvolene 5 – 21 nocí).

= 1.5.0 =
* Všetky letenky sú spiatočné a pri každej je celková cena „celkom tam aj späť“.
* Rozpis ceny „tam X € + späť Y €“ (Ryanair a Wizz Air), štítok na stránke krajiny „letenka tam aj späť od …“.

= 1.4.0 =
* Európske destinácie: len spiatočné letenky (Ryanair roundTripFares, Wizz Air tam a späť, Aviasales spiatočné).
* Nové nastavenie dĺžky pobytu pre Európu (predvolene 2 – 7 nocí).
* Pri každej európskej ponuke termín tam – späť a počet nocí.

= 1.3.0 =
* Ázia a exotika: rebríček najlacnejších spiatočných leteniek do celej Ázie a exotiky (jedna na mesto).
* Karty Bangkok/Dubaj/Abu Dhabi ukazujú len jednu najlacnejšiu letenku. Všade len spiatočné letenky.
* Wizz Air pre exotiku hľadá spiatočné lety (pobyt 5–21 nocí).
* Ponuky sa otvárajú cez tlačidlá – pri prejdení myšou sa v rohu prehliadača neukazuje adresa.

= 1.2.0 =
* Letecká spoločnosť (logo + názov) a priamy let/prestupy pri každej ponuke.
* Exotika: záloha cez Travelpayouts /v1/prices/cheap, viac aerolinek v rovnaký deň.
* Návod na token pre admina, keď chýba.

= 1.1.0 =
* Dá sa pribaliť do témy Spoznaj Svet; pri súčasnom načítaní z témy aj pluginu sa načíta len raz.

= 1.0.1 =
* Kompatibilita s WordPress 7.1.
* Parameter stav="0" skryje riadok s časom aktualizácie.

= 1.0.0 =
* Prvá verzia.
