=== Spoznaj Svet – Letenky ===
Contributors: spoznajsvet
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Najlacnejšie aktuálne letenky (Ryanair, Wizz Air, Aviasales/Travelpayouts + porovnanie Kayak a momondo), aktualizované každých 30 minút.

== Description ==

* Exotika: Bangkok, Dubaj, Abu Dhabi z Viedne, Bratislavy a Budapešti (spiatočné).
* Európske mestá: najlacnejšie jednosmerné letenky z Bratislavy a Viedne.
* Shortcode [spoznajsvet_letenky], blok „Letenky“, REST /wp-json/spoznajsvet/v1/letenky.
* Nastavenia → Letenky: zdroje, token Travelpayouts, letiská, ručné tipy.
* WP-CLI: wp letenky refresh, wp letenky status.

== Changelog ==

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
