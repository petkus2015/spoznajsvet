<?php
/**
 * Slovenské názvy letísk/miest a krajín. Neznáme letiská sa zobrazia
 * s názvom, ktorý vráti poskytovateľ.
 *
 * Formát: IATA => [ mesto, ISO kód krajiny, kontinent (eu|as|af) ].
 */

defined( 'ABSPATH' ) || exit;

function svl_airports() {
	static $a = null;
	if ( null !== $a ) {
		return $a;
	}
	$a = array(
		// Východiskové.
		'BTS' => array( 'Bratislava', 'SK', 'eu' ),
		'KSC' => array( 'Košice', 'SK', 'eu' ),
		'VIE' => array( 'Viedeň', 'AT', 'eu' ),
		'BUD' => array( 'Budapešť', 'HU', 'eu' ),
		'PRG' => array( 'Praha', 'CZ', 'eu' ),
		// Exotika.
		'BKK' => array( 'Bangkok', 'TH', 'as' ),
		'DMK' => array( 'Bangkok', 'TH', 'as' ),
		'HKT' => array( 'Phuket', 'TH', 'as' ),
		'DXB' => array( 'Dubaj', 'AE', 'as' ),
		'DWC' => array( 'Dubaj', 'AE', 'as' ),
		'AUH' => array( 'Abu Dhabi', 'AE', 'as' ),
		'SHJ' => array( 'Šardžá', 'AE', 'as' ),
		'DOH' => array( 'Dauha', 'QA', 'as' ),
		'MLE' => array( 'Male', 'MV', 'as' ),
		'MCT' => array( 'Maskat', 'OM', 'as' ),
		'SLL' => array( 'Salála', 'OM', 'as' ),
		'RUH' => array( 'Rijád', 'SA', 'as' ),
		'JED' => array( 'Džidda', 'SA', 'as' ),
		'TLV' => array( 'Tel Aviv', 'IL', 'as' ),
		'AMM' => array( 'Ammán', 'JO', 'as' ),
		'AQJ' => array( 'Akaba', 'JO', 'as' ),
		'KUT' => array( 'Kutaisi', 'GE', 'as' ),
		'TBS' => array( 'Tbilisi', 'GE', 'as' ),
		'EVN' => array( 'Jerevan', 'AM', 'as' ),
		// Afrika.
		'RAK' => array( 'Marrákeš', 'MA', 'af' ),
		'AGA' => array( 'Agadir', 'MA', 'af' ),
		'FEZ' => array( 'Fès', 'MA', 'af' ),
		'TNG' => array( 'Tanger', 'MA', 'af' ),
		'RBA' => array( 'Rabat', 'MA', 'af' ),
		'HRG' => array( 'Hurghada', 'EG', 'af' ),
		'SSH' => array( 'Šarm aš-Šajch', 'EG', 'af' ),
		'RMF' => array( 'Marsa Alam', 'EG', 'af' ),
		// Veľká Británia a Írsko.
		'STN' => array( 'Londýn', 'GB', 'eu' ),
		'LTN' => array( 'Londýn', 'GB', 'eu' ),
		'LGW' => array( 'Londýn', 'GB', 'eu' ),
		'LHR' => array( 'Londýn', 'GB', 'eu' ),
		'MAN' => array( 'Manchester', 'GB', 'eu' ),
		'EDI' => array( 'Edinburgh', 'GB', 'eu' ),
		'BHX' => array( 'Birmingham', 'GB', 'eu' ),
		'BRS' => array( 'Bristol', 'GB', 'eu' ),
		'LPL' => array( 'Liverpool', 'GB', 'eu' ),
		'EMA' => array( 'East Midlands', 'GB', 'eu' ),
		'GLA' => array( 'Glasgow', 'GB', 'eu' ),
		'LBA' => array( 'Leeds', 'GB', 'eu' ),
		'BFS' => array( 'Belfast', 'GB', 'eu' ),
		'DUB' => array( 'Dublin', 'IE', 'eu' ),
		'ORK' => array( 'Cork', 'IE', 'eu' ),
		// Taliansko.
		'BGY' => array( 'Miláno (Bergamo)', 'IT', 'eu' ),
		'MXP' => array( 'Miláno', 'IT', 'eu' ),
		'LIN' => array( 'Miláno', 'IT', 'eu' ),
		'FCO' => array( 'Rím', 'IT', 'eu' ),
		'CIA' => array( 'Rím', 'IT', 'eu' ),
		'NAP' => array( 'Neapol', 'IT', 'eu' ),
		'BRI' => array( 'Bari', 'IT', 'eu' ),
		'BDS' => array( 'Brindisi', 'IT', 'eu' ),
		'CTA' => array( 'Catánia', 'IT', 'eu' ),
		'PMO' => array( 'Palermo', 'IT', 'eu' ),
		'TPS' => array( 'Trapani', 'IT', 'eu' ),
		'CAG' => array( 'Cagliari', 'IT', 'eu' ),
		'AHO' => array( 'Alghero', 'IT', 'eu' ),
		'OLB' => array( 'Olbia', 'IT', 'eu' ),
		'PSA' => array( 'Pisa', 'IT', 'eu' ),
		'FLR' => array( 'Florencia', 'IT', 'eu' ),
		'BLQ' => array( 'Bologna', 'IT', 'eu' ),
		'VCE' => array( 'Benátky', 'IT', 'eu' ),
		'TSF' => array( 'Benátky (Treviso)', 'IT', 'eu' ),
		'TRN' => array( 'Turín', 'IT', 'eu' ),
		'GOA' => array( 'Janov', 'IT', 'eu' ),
		'VRN' => array( 'Verona', 'IT', 'eu' ),
		'SUF' => array( 'Lamezia Terme', 'IT', 'eu' ),
		'PSR' => array( 'Pescara', 'IT', 'eu' ),
		'AOI' => array( 'Ancona', 'IT', 'eu' ),
		'TRS' => array( 'Terst', 'IT', 'eu' ),
		'REG' => array( 'Reggio Calabria', 'IT', 'eu' ),
		'CUF' => array( 'Cuneo', 'IT', 'eu' ),
		'PEG' => array( 'Perugia', 'IT', 'eu' ),
		// Španielsko.
		'BCN' => array( 'Barcelona', 'ES', 'eu' ),
		'GRO' => array( 'Barcelona (Girona)', 'ES', 'eu' ),
		'REU' => array( 'Reus', 'ES', 'eu' ),
		'MAD' => array( 'Madrid', 'ES', 'eu' ),
		'AGP' => array( 'Málaga', 'ES', 'eu' ),
		'ALC' => array( 'Alicante', 'ES', 'eu' ),
		'VLC' => array( 'Valencia', 'ES', 'eu' ),
		'PMI' => array( 'Palma de Mallorca', 'ES', 'eu' ),
		'IBZ' => array( 'Ibiza', 'ES', 'eu' ),
		'MAH' => array( 'Menorca', 'ES', 'eu' ),
		'SVQ' => array( 'Sevilla', 'ES', 'eu' ),
		'TFS' => array( 'Tenerife', 'ES', 'eu' ),
		'TFN' => array( 'Tenerife', 'ES', 'eu' ),
		'LPA' => array( 'Gran Canaria', 'ES', 'eu' ),
		'ACE' => array( 'Lanzarote', 'ES', 'eu' ),
		'FUE' => array( 'Fuerteventura', 'ES', 'eu' ),
		'BIO' => array( 'Bilbao', 'ES', 'eu' ),
		'SDR' => array( 'Santander', 'ES', 'eu' ),
		'SCQ' => array( 'Santiago de Compostela', 'ES', 'eu' ),
		// Portugalsko.
		'LIS' => array( 'Lisabon', 'PT', 'eu' ),
		'OPO' => array( 'Porto', 'PT', 'eu' ),
		'FAO' => array( 'Faro', 'PT', 'eu' ),
		'FNC' => array( 'Madeira', 'PT', 'eu' ),
		'PDL' => array( 'Azory', 'PT', 'eu' ),
		// Francúzsko, Benelux.
		'CDG' => array( 'Paríž', 'FR', 'eu' ),
		'ORY' => array( 'Paríž', 'FR', 'eu' ),
		'BVA' => array( 'Paríž (Beauvais)', 'FR', 'eu' ),
		'NCE' => array( 'Nice', 'FR', 'eu' ),
		'MRS' => array( 'Marseille', 'FR', 'eu' ),
		'LYS' => array( 'Lyon', 'FR', 'eu' ),
		'BOD' => array( 'Bordeaux', 'FR', 'eu' ),
		'TLS' => array( 'Toulouse', 'FR', 'eu' ),
		'NTE' => array( 'Nantes', 'FR', 'eu' ),
		'BRU' => array( 'Brusel', 'BE', 'eu' ),
		'CRL' => array( 'Brusel (Charleroi)', 'BE', 'eu' ),
		'EIN' => array( 'Eindhoven', 'NL', 'eu' ),
		'AMS' => array( 'Amsterdam', 'NL', 'eu' ),
		'RTM' => array( 'Rotterdam', 'NL', 'eu' ),
		'LUX' => array( 'Luxemburg', 'LU', 'eu' ),
		// Nemecko, Švajčiarsko, Rakúsko.
		'BER' => array( 'Berlín', 'DE', 'eu' ),
		'CGN' => array( 'Kolín', 'DE', 'eu' ),
		'DTM' => array( 'Dortmund', 'DE', 'eu' ),
		'MUC' => array( 'Mníchov', 'DE', 'eu' ),
		'FRA' => array( 'Frankfurt', 'DE', 'eu' ),
		'HHN' => array( 'Frankfurt (Hahn)', 'DE', 'eu' ),
		'HAM' => array( 'Hamburg', 'DE', 'eu' ),
		'NUE' => array( 'Norimberg', 'DE', 'eu' ),
		'FMM' => array( 'Memmingen', 'DE', 'eu' ),
		'DUS' => array( 'Düsseldorf', 'DE', 'eu' ),
		'STR' => array( 'Stuttgart', 'DE', 'eu' ),
		'BSL' => array( 'Bazilej', 'CH', 'eu' ),
		'GVA' => array( 'Ženeva', 'CH', 'eu' ),
		'ZRH' => array( 'Zürich', 'CH', 'eu' ),
		'SZG' => array( 'Salzburg', 'AT', 'eu' ),
		'INN' => array( 'Innsbruck', 'AT', 'eu' ),
		'GRZ' => array( 'Graz', 'AT', 'eu' ),
		// Škandinávia a Pobaltie.
		'CPH' => array( 'Kodaň', 'DK', 'eu' ),
		'BLL' => array( 'Billund', 'DK', 'eu' ),
		'AAR' => array( 'Aarhus', 'DK', 'eu' ),
		'ARN' => array( 'Štokholm', 'SE', 'eu' ),
		'NYO' => array( 'Štokholm (Skavsta)', 'SE', 'eu' ),
		'GOT' => array( 'Göteborg', 'SE', 'eu' ),
		'MMX' => array( 'Malmö', 'SE', 'eu' ),
		'OSL' => array( 'Oslo', 'NO', 'eu' ),
		'TRF' => array( 'Oslo (Torp)', 'NO', 'eu' ),
		'BGO' => array( 'Bergen', 'NO', 'eu' ),
		'HEL' => array( 'Helsinki', 'FI', 'eu' ),
		'RVN' => array( 'Rovaniemi', 'FI', 'eu' ),
		'TLL' => array( 'Tallinn', 'EE', 'eu' ),
		'RIX' => array( 'Riga', 'LV', 'eu' ),
		'VNO' => array( 'Vilnius', 'LT', 'eu' ),
		'KUN' => array( 'Kaunas', 'LT', 'eu' ),
		'KEF' => array( 'Reykjavík', 'IS', 'eu' ),
		// Poľsko, Česko, Maďarsko.
		'KRK' => array( 'Krakov', 'PL', 'eu' ),
		'WAW' => array( 'Varšava', 'PL', 'eu' ),
		'WMI' => array( 'Varšava (Modlin)', 'PL', 'eu' ),
		'GDN' => array( 'Gdansk', 'PL', 'eu' ),
		'KTW' => array( 'Katovice', 'PL', 'eu' ),
		'WRO' => array( 'Vroclav', 'PL', 'eu' ),
		'POZ' => array( 'Poznaň', 'PL', 'eu' ),
		'DEB' => array( 'Debrecín', 'HU', 'eu' ),
		// Balkán a juhovýchod.
		'ZAD' => array( 'Zadar', 'HR', 'eu' ),
		'SPU' => array( 'Split', 'HR', 'eu' ),
		'DBV' => array( 'Dubrovník', 'HR', 'eu' ),
		'PUY' => array( 'Pula', 'HR', 'eu' ),
		'ZAG' => array( 'Záhreb', 'HR', 'eu' ),
		'RJK' => array( 'Rijeka', 'HR', 'eu' ),
		'OTP' => array( 'Bukurešť', 'RO', 'eu' ),
		'CLJ' => array( 'Kluž', 'RO', 'eu' ),
		'TSR' => array( 'Temešvár', 'RO', 'eu' ),
		'IAS' => array( 'Jasy', 'RO', 'eu' ),
		'SOF' => array( 'Sofia', 'BG', 'eu' ),
		'VAR' => array( 'Varna', 'BG', 'eu' ),
		'BOJ' => array( 'Burgas', 'BG', 'eu' ),
		'BEG' => array( 'Belehrad', 'RS', 'eu' ),
		'INI' => array( 'Niš', 'RS', 'eu' ),
		'SJJ' => array( 'Sarajevo', 'BA', 'eu' ),
		'TZL' => array( 'Tuzla', 'BA', 'eu' ),
		'TGD' => array( 'Podgorica', 'ME', 'eu' ),
		'TIV' => array( 'Tivat', 'ME', 'eu' ),
		'SKP' => array( 'Skopje', 'MK', 'eu' ),
		'OHD' => array( 'Ohrid', 'MK', 'eu' ),
		'TIA' => array( 'Tirana', 'AL', 'eu' ),
		'PRN' => array( 'Priština', 'XK', 'eu' ),
		'KIV' => array( 'Kišiňov', 'MD', 'eu' ),
		'LJU' => array( 'Ľubľana', 'SI', 'eu' ),
		// Grécko, Cyprus, Malta, Turecko.
		'ATH' => array( 'Atény', 'GR', 'eu' ),
		'SKG' => array( 'Solún', 'GR', 'eu' ),
		'HER' => array( 'Heraklion (Kréta)', 'GR', 'eu' ),
		'CHQ' => array( 'Chania (Kréta)', 'GR', 'eu' ),
		'RHO' => array( 'Rodos', 'GR', 'eu' ),
		'CFU' => array( 'Korfu', 'GR', 'eu' ),
		'KGS' => array( 'Kos', 'GR', 'eu' ),
		'JTR' => array( 'Santorini', 'GR', 'eu' ),
		'JMK' => array( 'Mykonos', 'GR', 'eu' ),
		'ZTH' => array( 'Zakynthos', 'GR', 'eu' ),
		'EFL' => array( 'Kefalónia', 'GR', 'eu' ),
		'PVK' => array( 'Preveza', 'GR', 'eu' ),
		'MLA' => array( 'Malta', 'MT', 'eu' ),
		'LCA' => array( 'Larnaka', 'CY', 'eu' ),
		'PFO' => array( 'Pafos', 'CY', 'eu' ),
		'IST' => array( 'Istanbul', 'TR', 'eu' ),
		'SAW' => array( 'Istanbul (Sabiha)', 'TR', 'eu' ),
		'AYT' => array( 'Antalya', 'TR', 'eu' ),
		'ADB' => array( 'Izmir', 'TR', 'eu' ),
		'DLM' => array( 'Dalaman', 'TR', 'eu' ),
		'BJV' => array( 'Bodrum', 'TR', 'eu' ),
	);
	return $a;
}

function svl_countries() {
	return array(
		'SK' => 'Slovensko', 'AT' => 'Rakúsko', 'HU' => 'Maďarsko', 'CZ' => 'Česko', 'PL' => 'Poľsko',
		'TH' => 'Thajsko', 'AE' => 'SAE', 'QA' => 'Katar', 'MV' => 'Maldivy', 'OM' => 'Omán', 'SA' => 'Saudská Arábia',
		'IL' => 'Izrael', 'JO' => 'Jordánsko', 'GE' => 'Gruzínsko', 'AM' => 'Arménsko', 'MA' => 'Maroko', 'EG' => 'Egypt',
		'GB' => 'Veľká Británia', 'IE' => 'Írsko', 'IT' => 'Taliansko', 'ES' => 'Španielsko', 'PT' => 'Portugalsko',
		'FR' => 'Francúzsko', 'BE' => 'Belgicko', 'NL' => 'Holandsko', 'LU' => 'Luxembursko', 'DE' => 'Nemecko',
		'CH' => 'Švajčiarsko', 'DK' => 'Dánsko', 'SE' => 'Švédsko', 'NO' => 'Nórsko', 'FI' => 'Fínsko', 'EE' => 'Estónsko',
		'LV' => 'Lotyšsko', 'LT' => 'Litva', 'IS' => 'Island', 'HR' => 'Chorvátsko', 'RO' => 'Rumunsko', 'BG' => 'Bulharsko',
		'RS' => 'Srbsko', 'BA' => 'Bosna a Hercegovina', 'ME' => 'Čierna Hora', 'MK' => 'Severné Macedónsko', 'AL' => 'Albánsko',
		'XK' => 'Kosovo', 'MD' => 'Moldavsko', 'SI' => 'Slovinsko', 'GR' => 'Grécko', 'MT' => 'Malta', 'CY' => 'Cyprus', 'TR' => 'Turecko',
	);
}

function svl_airlines() {
	return array(
		'FR' => 'Ryanair', 'RK' => 'Ryanair UK', 'AL' => 'Malta Air', 'OE' => 'Lauda', 'W6' => 'Wizz Air', 'W4' => 'Wizz Air Malta',
		'W9' => 'Wizz Air UK', '5W' => 'Wizz Air Abu Dhabi', 'QR' => 'Qatar Airways', 'EK' => 'Emirates', 'EY' => 'Etihad',
		'FZ' => 'flydubai', 'TK' => 'Turkish Airlines', 'PC' => 'Pegasus', 'OS' => 'Austrian', 'LH' => 'Lufthansa', 'LX' => 'Swiss',
		'LO' => 'LOT', 'AZ' => 'ITA Airways', 'KL' => 'KLM', 'AF' => 'Air France', 'BA' => 'British Airways', 'IB' => 'Iberia',
		'TP' => 'TAP', 'A3' => 'Aegean', 'OU' => 'Croatia Airlines', 'JU' => 'Air Serbia', 'RO' => 'Tarom', 'FB' => 'Bulgaria Air',
		'TG' => 'Thai Airways', 'G9' => 'Air Arabia', '3O' => 'Air Arabia Maroc', 'U2' => 'easyJet', 'EC' => 'easyJet', 'EW' => 'Eurowings',
		'VY' => 'Vueling', 'HV' => 'Transavia', 'QS' => 'Smartwings', 'SN' => 'Brussels Airlines', 'MS' => 'EgyptAir',
		'RJ' => 'Royal Jordanian', 'GF' => 'Gulf Air', 'WY' => 'Oman Air', 'SV' => 'Saudia', 'CA' => 'Air China', 'MU' => 'China Eastern',
		'HU' => 'Hainan Airlines', 'AI' => 'Air India', 'UL' => 'SriLankan', 'KC' => 'Air Astana', 'SQ' => 'Singapore Airlines',
		'CX' => 'Cathay Pacific', 'J2' => 'AZAL', 'DE' => 'Condor', 'XQ' => 'SunExpress', 'H4' => 'HiSky', 'BT' => 'airBaltic',
	);
}

/**
 * Doplní informácie o letisku.
 *
 * @param string $iata     Kód.
 * @param string $fallback Názov mesta od poskytovateľa.
 * @param string $iso      ISO krajiny od poskytovateľa.
 * @return array{city:string,iso:string,country:string,continent:string}
 */
function svl_airport( $iata, $fallback = '', $iso = '' ) {
	$all = svl_airports();
	$iso = strtoupper( (string) $iso );
	if ( isset( $all[ $iata ] ) ) {
		list( $city, $iso, $continent ) = $all[ $iata ];
	} else {
		$city      = $fallback ? $fallback : $iata;
		$continent = '';
	}
	$countries = svl_countries();
	return array(
		'city'      => $city,
		'iso'       => $iso,
		'country'   => isset( $countries[ $iso ] ) ? $countries[ $iso ] : '',
		'continent' => $continent,
	);
}
