<?php

namespace App\Services;

use Lib\WebsiteTools;

class WebsiteStatusChecker
{
    public static $websiteStatusOptions = [
        'unknown' => 0, 'ok' => 1, 'invalidURL' => -5, 'bannedText' => -4, 'bannedURL' => -3, 'malwareBlacklist' => -2, 'pageError' => -1,
    ];

    private static $curl = null;

    public static function getWebsiteStatusLanguageKeys()
    {
        $return = [];
        foreach (self::$websiteStatusOptions as $name => $value) {
            $return['WebsiteTools.websiteStatusOptions.' . $name] = $value;
        }

        return $return;
    }

    public static function statusDisplayString($statusCode, $language = 'en')
    {
        return langGet(array_search($statusCode, self::getWebsiteStatusLanguageKeys()), false, [], $language);
    }

    /*
    no longer using Google SafeBrowsing (see below)

    used to have a table of google safe browsing hashes:
        CreateTable('checkWebsite',"
        	source VARCHAR(30) NOT NULL,
        	md5hash CHAR(32) NOT NULL,
        	PRIMARY KEY (source,md5hash), INDEX(md5hash)
        ");

    function checkHash($s) {
    	// echo "($s)";
    	return (dbGetOne("SELECT md5hash FROM checkWebsite WHERE md5hash='".md5($s)."'") != null);
    }
    */

    public static function getWebsiteStatus($url, $checkForBannedURL, $checkContents, &$contentsData = false)
    {
        // These websites aren't appropriate for using as the website in a listing, but may be ok for other purposes.
        // domains and paths must end with a '/'. can be domain, complete URL, or partial URL path.
        $BANNED_URL = ['hostelz.com/', 'hostelworld.com/', 'hostels.com/', 'airbnb.com/', 'hostel.com/', 'hostelbookers.com/', 'hostelsclub.com/', 'nychostels.com/', 'hostelsprice.com/', 'book-a-hostel.eu/', 'studentholidays.com/', 'trav.com/', 'bookhostels.com/', 'kasbah.com/', 'hostels.se/', 'youth-hostels-in.com/', 'hostelscentral.com/', 'redflag.info/', 'travellerspoint.com/', 'cheapaccommodation.com/', 'find-hostels.com/', 'allensguide.com/', 'expedia.co.uk/', 'bookpromo.com/', 'travelpunk.com/', 'rooms-spain.com/', 'cheapnights.com/', 'cheap-airfare.southwestwa.com/', '1-london-hostels-paris-hostels.com/', 'shopping.net/', 'barcelonaconnect.com/', 'redhotchilli.co.uk/', 'yourtravel-hq.com/', 'stop-barcelona.com/', 'citybreakguide.com/', 'hostelplanet.com/', 'hostels.ws/', 'cheap-holidays-online.co.uk/', 'coolschoolmedia.com/', 'globalhosting4u.co.uk/', 'paris-hostels-central.com/', 'onlineukshops.com/', 'travelhops.com/', 'hostels-cheap.com/', 'lonelyplanetexchange.com/', 'istanbulreservation.com/', 'ttleisuretours.com/', 'hostelsweb.com/', 'airline-flight.ida-danismanlik.com/', 'cybercityguides.com/', 'ehotelzone.com/', 'bookmeahostel.com/', 'helsinki-hotels-reservation.net/', 'hotelandhostel.com/',  'paris-on-line.com/', 'hiptravelguide.com/', 'bookings.org/', 'romantic-getaway.hepchotel.com/', 'alpha-beds.com/', 'channels.nl/', 'tnfsh.com/', 'world-stay.com/', 'australianexplorer.com/',  'rooms-australia.com/', 'global-rooms.com/', 'tellmeabout.co.uk/', 'bcl.com.au/', 'ozeworld.com/', 'cheaphotellinks.com/', 'ehotelfinder.net/', 'travelstay.com/', 'travel-uk.org/', '2camels.com/', 'hotelsprague.cz/', 'motylek.com/', 'guideforeurope.com/', 'find-discount-hotels-rate.com/', 'huge-hotel-discounts.com/', 'hotelsplustours.com/', 'eurocityhotels.com/', 'city-break-hotels.com/', 'euro-hotel-discounts.com/', 'twinroom.com/', 'businesshotelreservation.ch/', 'o-hotels.com/', 'o-discounts.com/', 'eztrip.com/', '1st-hoteldiscounts.com/', 'city-discounts.com/', 'accommodation-source.com/', 'euro-hotels.com/', 'travelpenguin.com/', 'volaretour.com/', 'quickaid.com/', 'hotelreservationsnow.com/', 'city-accommodation.com/', 'hotel-rate-deals.com/', 'lodge4less.com/', 'twinroom.de/', 'lodgingbooker.com/', 'hotels4tourists.com/', 'hotel-rates.com/', 'bestlodging.com/', 'bestlodging.com/', 'discount-hotels-now.com/', 'lodging-specials.com/', 'superior-hotels.com/', 'accommodation.info/', '1-800-868-9218.com/', 'dovehotels.com/', 'hoteldiscounts.tv/', 'hotelratesworldwide.com/', 'uk.laterooms.com/', 'anyhotelanywhere.com/', 'europe-hotels-travel.net/', 'lodgingsavings.com/', 'hotelcity.com/', 'best-hotel-reservations.com/', 'accommodationdesk.com/', 'best-hotel-reservations.com/', '4urhotelreservation.com/', 'hoteldealeo.com/', '1st-hotelrates.com/', 'hotelreservations-online.com/', '1sthotels.com/', 'world-hotel-search.com/', 'hotels-discount-now.com/', 'all-hotels.com/', 'discount-hotels-travels.com/', 'just-hotels.com/', '1st-hotelreservations.com/', 'myhoteldiscount.com/', 'cheap-hotels.co.uk/', 'bookingsavings.com/', 'reservations.ru/', 'maceratur.com/', 'searchbeat.net/', 'fastnet.com.au/', 'web-hotel.com/', 'hotel-discounter.org/', 'whereisthebeach.com/', 'cheephostels.co.uk/', 'w3hotels.com/', 'absolutehostels.com/', 'holidayconnexions.com.au/', 'properties.worldtourism.com.au/', 'holidaycity.com/', 'internethoteldiscounts.com/', 'hotels-discounts.wiztravelmart.com/', 'reservations.hotel-guides.us/', 'reservations.hotel-guides.us/', 'book-europe-hotels.com/', 'hotel-bargain-offers.co.uk/', 'hotelindex.org/', 'cheap-hotel-finder.co.uk/', 'europeanexplorer.com/', 'hotels-of-europe.net/', 'discounthotels-world.com/', 'europehotels.ws/', 'c-europe.com/', 'crystalcities.co.uk/', 'hotels-and-inns.com/', 'i-hotels.iagora.com/', 'webwide-travel.com/', 'america-hotels-travel.net/', 'hotel-price.co.uk/', 'shortbreaks.com/', 'ase.net/', 'sculpt.phidji.com/', 'bookings.org/', 'cityindex.com/', 'artemotore.com/', 'faresrus.com/', 'hotelsbyphone.co.uk/', '1st-class-hotels.com/', 'allthehotels.com/', 'the-hotels.com/', 'planetholiday.com/', 'voyagenow.com/', 'asiahotels.com/', 'booking.allstays.com/', 'online.booking-net.com/', 'hotelresdesk.com/', 'precisionreservations.com/', 'hostel-finder.com/', 'maxinfocanada.com/', 'clickcitytravel.com/', 'booking-net.com/', 'hotelmotelnow.com/', 'accommodation-and-hotels.com/', 'viprez.com/', 'backpackers.com.au/', 'backpackertours.com.au/', 'vipbackpackers.com/', 'hotelsbycity.com/', 'hotelreservationandinformation.com/', 'oztravel.com.au/', 'australia-hotels.biz/', 'malt-shovel.com.au/', 'hotelreservationssite.com/', 'international.motels-hotels-inns.com/', 'accommodation.com/', 'accommodation.co.uk/', 'where-to-stay.com/', 'travel-4-fun.com/', 'resort-deals.com/', 'glassglobal.com/', 'trip-search.com/', 'tripdeals.net/', 'maxinfous.com/', 'we-sell-it.co.uk/', 'nbportal.com/', 'fine-hotels-europe.co.uk/', 'londraweb.com/', 'hotelonclick.com/', 'exploreeurope.com/', 'gullivershotels.com/', 'euroadventures.net/', 'edreams.ru/', 'room4uonline.com/', 'travel-hotels-europe.co.uk/', 'bedandbreakfast-directory.co.uk/', 'eurocheapo.com/', 'iagora.com/', 'interhotel.com/', 'traveltocity.com/', 'traveljournals.net/', '1800stay.com/', 'online-hotels-worldwide.com/', 'anaussieinlondon.co.uk/', 'allukhotels.com/', 'hoteloholic.co.uk/', 'allbackpackers.com.au/', 'hotelnetservice.com/', 'concierge.com/', 'vacations.net/', 'ezhostel.com/', 'bhrhotels.com/', 'securedtravel.com/', 'w3hotels.com/', 'airport-accommodation.co.uk/', 'hotels-4jp.co.uk/', 'hoteltravelzone.com/', 'onetravel.com/', '1travelmart.com/', 'hotels-accommodation-europe.co.uk/', 'hotel-reservations-central.com/', 'cheaphostellinks.com/', 'melbournehotelfinder.com/', 'cheap-hostels-in.com/', 'travel.yahoo.com/', 'hosteltraveler.com/', 'instantworldbooking.com/', 'flashbooking.com/', 'urbanlowdown.com/', 'bedbreakfastreservations.com/', 'totalhostels.com/', 'hostelearth.com/', 'pilgrimreservations.com/', 'bedbreakfasttraveler.com/', 'eurohosteltraveler.com/', 'italyinstantbooking.com/', 'londoninstantbooking.com/', 'peruinstantbooking.com/', 'portugalinstantbooking.com/', 'romeinstantbooking.com/', 'spaininstantbooking.com/', 'ukinstantbooking.com/', 'gomio.com/', 'hostelcn.com/', 'hotelpronto.com/', 'hotelbookingmilan.it/', 'worldweb.com/', 'cybercityguides.com/', 'search-for-hotels.com/', 'hotelguide.net/', 'travelape.com/', 'hotelsandrates.com/', 'hotelreservationandinformation.com/', 'guide-to-hotel.com/', 'the-hotels.com/', 'hotelsandcarrentals.com/', 'mytravelguide.com', 'europe-hotels-comparison.com/', 'kelkoo.co.uk/', 'hotelchannel.com/', 'accommodations.com/', 'express-hotels.com/', 'number1hotels.com/', 'hotels-reservation-services.com/', 'find-hotels.net/', 'realhotels.com/', 'abnhotels.com/', 'accomodations-net.com/', 'tobook.com/', 'start4all.com/', 'laterooms.com/', 'travelmall.com/', 'hotelbase.com/', 'allwebhotels.com/', 'freehotelsearch.com/', 'hotels-with-discounts.com/', 'exploreeurope.com/', 'ase.net/', 'hotels.msk.ru/', 'nbportal.com/', 'hotels.spb.ru/', 'nethotels.com/', 'webtourist.net/', 'laterooms.com/', 'ratestogo.com/', 'express-hotel-guide.com/', 'hotelroomsplus.com/', '12bookhotels.com/', 'worldexecutive.com/', 'guide-to-hotels.com/', 'ifyouski.com/', 'hotels-list.com/', 'reservations-web.com/', 'hotels-search-engine.org/', 'freehotelguide.com/', 'learn4good.com/', 'hostels.net/', 'hostelseurope.com/', 'easytobook.com/', 'realtravel.com/', 'booking.com/', 'hotel.com.au/', 'travel.ebookers.com/', 'holiday-beds-direct.com/', 'hostelseverywhere.com/', 'unhostels.org/', 'newyorkbesthostels.com/', 'hostelinnewyork.com/', 'hotelplanner.com/', 'travelpod.com/', 'hosteltimes.com/', 'backpackers.com.tw/', 'best4hostels.com/', 'hostels247.com/', 'lonelyplanet.com/', 'travbuddy.com/', 'virtualtourist.com/', 'venere.com/', 'flightcentre.com.au/', 'boomerangservices.ro/', 'faxts.com/', 'daodao.com/', 'in-hotel.org/', 'cheap-hostels.org/', 'worldofhotels.com/', 'boomerangservices.ro/', 'pleasetakemeto.com/', 'epictrip.com/', 'beginthier.nl/', 'discount-hotel-selection.com/', 'waarbenjij.nu/', 'ozhostels.com.au/', 'error404.000webhost.com/', 'hotelscombined.com.au/', 'statravel.com.au/', 'www.homestead.com/' /* is ok for subdomains other than www. */, 'en.wordpress.com/' /* typo redirect subdomain */, 'www.geocities.com/' /* no longer up */,
            'tripadvisor.com/' /* but may have contact info */, 'tripadvisor.com.tr/', 'tripadvisor.com.ca/', 'tripadvisor.in/', 'tripadvisor.co.uk/', 'tripadvisor.de/', 'tripadvisor.it/', 'tripadvisor.es/', 'tripadvisor.jp/',
            'accommodationlondon.net/', 'thebrokebackpacker.com/', 'theculturetrip.com/', 'travelinglifestyle.net/', 'thesavvybackpacker.com/', 'indietraveller.co/', 'roadaffair.com/', 'spotahome.com/', 'kayak.com/', 'trip.com/', 'nomadicmatt.com/', 'oneweirdglobe.com/', 'budgetyourtrip.com/', 'hostel-traveler.by/', 'uniticket.by/', 'bedspro.com/', 'aviasales.ru/', 'hrg.com/', 'vk.com/', 'reg.ru/', 'msn.com/'
            /*specific hostel websites used for spam*/,
            'hairyberrynz.com/', 'hakusanri.com/', 'awahostels.com/', 'backpackersondundas.com/', 'bedandbicycle.com/', 'lotushostel.cn/', 'butterfly-villa.com/', 'caledonianinn.com.au/', 'diamondhostel.com/', 'parimatch.com.cy/', 'daughtershome.com/', 'domharcerza.prv.pl/', 'faro-hostel.com/', 'farrcottage.com/', 'flowerpowerhostel.pl/', 'yhalijiang.com/', 'fiveflagshostel.com/',
        ];
        // blocked keywords; need to double-check. e.g. cannot block "hoes", as the word "shoes" would be also blocked. Cannot block "sex" as it could be the gender for dorms.
        $BANNED_TEXT = [
            'This domain may be for sale.', 'THIS DOMAIN MAY BE FOR', 'Buy this Domain', 'parking_form',
            'http://searchportal.information.com', 'http://ndparking.com', 'parked_layouts', 'parked_images', '>Parked.com', 'Este site está bloqueado', 'Nuestros servidores de dns ya reconocen su sitio.', 'sedoparking.com', 'Pontonet - Webhosting', '"parking.css"', '.casalemedia.com', '/domainpark/',
            'sexualhealth', 'AVAILABLE - FreeServers', 'Site Disabled', 'AVAILABLE - 50megs', 'Site is not available.', 'Sorry, this page was not found', '> doesn&#8217;t&nbsp;exist<', 'domainnamesales.com', 'by Register.com', '/images.komplads.net', 'var parkingData', '>Lifestream Downloads<', 'The domain is for sale', 'name=\"landingparent\"', 'turing_cluster_prod',
            /* used on expired domains */
            'Domain Registered by WebNow', 'This domain name expired', 'http://mcc.godaddy.com/park/', 'Inquire about this domain', 'http://www.google.com/adsense/domains/caf.js', 'nutrisystem', 'dietboss', 'exitsplashpage', '薬剤師', '新大陆娱乐', '立即下载', 'bv伟德体育', 'Descargar ahora', 'download now', 'casino',
            'May be for sale', 'data-adblockkey=',
            /* New World Entertainment */
            'www.google.com/adsense/domains/', 'parked-content.godaddy.com',
            'This site is temporarily unavailable', '>MySQL Fatal Error<', '>It is currently being parked by the owner<', 'Enter a domain and click check to see if it is available',
            'is not configured on this server', 'hugedomains.com', 'This Account has been suspended', 'Site Suspended', '<img alt="Bluehost"', 'This site requires JavaScript and Cookies to be enabled. Please change your browser settings or upgrade your browser.',
            /* used by spammy sites, can't find any better identifier strings */
            'http://fwdssp.com', '<title>UK Cloud Web Hosting', '<frameset rows="100%,*" frameborder="no" border="0" framespacing="0">',
            /* used to hide the actual content of spam pages by using frames */
            '">Click here to proceed</a>. </body>',
            /* used by a bad malware site, couldn't find anything else to distinguish it, but this should do it */
            '<meta http-equiv="Refresh" content="0;url=defaultsite" />', 'id="b8a27b6bae"',
            /* used by sedo parked domains */
            'If this is your domain name you must renew it immediately', 'https://dan.com/domain-seller', 'This Page Is Under Construction', 'Domain for Sale', 'Future home of something', 'The Hostel is now closed permanently', 'has expired', 'renew this domain name',
            /* new adds 2022 */
            'Premium Domains', '.WS Domain', 'available to register', 'comic.jp',
            'dating platform', 'buy domain', '404 Not Found', 'Index of /',
            'domain may be for sale', 'Register Your Own .WS Domain', 'Apache2 Ubuntu Default Page', '朝阳泳兜金融服务有限公司', 'Affiliate Marketing',

            'nolvadex', 'phenergan', 'plaquenil', 'progesterone', 'propranolol', 'prozac', 'rehab', 'sildenafil',
            'silldalis', 'sterapred', 'sulfate', 'tadacip', 'tadalafil', 'tadalis', 'teollisuudelle', 'toreador',
            'tretinoin', 'valtrex', 'vermox', 'viagra', 'wellbutrin', 'xalanta', 'xenical',
            'zithromax', 'zoloft', 'You are a winner', 'You have been selected', 'Your income',
            'gigabyte', 'vps', 'stream', 'streaming', '403 Forbidden',
            'Let the domain work', 'Пусть домен работает', 'На домене', 'no website on',
            'Domain redirect', 'buy bitcoin', 'buy cryptocurrency', 'h0rny', 'p0rn', 'porno',
            'hydroxychloroquine', 'impotence', 'indocin', 'investointi', 'kamagra', 'kannabiksen',
            'kypsyy', 'lasix', 'levitra', 'lexapro', 'lipitor', 'methotrexate', 'nasopharynx', 'neurontin', '247drugshop', 'abilify', 'accutane', 'acyclovir', 'albendazole', 'alcoholic', 'amoxicillin', 'amoxil', 'antabuse', 'aralen', 'atarax', 'augmentin', 'azithromycin', 'baclofen', 'Apache is functioning normally', 'partner.googleadservices.',
            'Privacy Error', 'Security Risk', 'Sorry, we are closed', 'permanently closing', 'closed  permanently', 'Sponsored Listings', 'Nuestro sitio esta cerrado', 'Our hostel closed',

            /* code */
            'body> </body', '<body></body>', '<html></html>', '<body>
</body></html>', '<body></body></html>', '</head><body></body></html>', '<html>

</html>
',
        ];

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return self::$websiteStatusOptions['invalidURL'];
        }

        $INVALID_CHARS = [' ', ',', "\n", "\r", '@'];
        $url = trim($url);
        foreach ($INVALID_CHARS as $char) {
            if (strpos($url, $char) !== false) {
                return self::$websiteStatusOptions['invalidURL'];
            }
        }

        /* This no longer works.  Would need to upgrade to their new API.  Could use a library like https://github.com/Beaver6813/phpGSB, but it's complicated and doesn't use MySQLi.

            $VERSION_FILE = USER_ROOT.'data/checkWebsiteData.txt';
        	$UPDATE_DELAY = 24*60*60; // Google recommends every 30 minutes, but we don't need to be as current

        	if ((time()-filemtime($VERSION_FILE)) > $UPDATE_DELAY) {
        		touch($VERSION_FILE); // set the modified time quickly
        		$versions = unserialize(file_get_contents($VERSION_FILE));
        		if (!$versions) return self::$websiteStatusOptions['unknown']; // error // $versions = array('goog-black-hash'=>'1:-1', 'goog-malware-hash'=>'1:-1'); // this should only happen once
        		foreach ($versions as $listName=>$version) {
        			$data = file("http://sb.google.com/safebrowsing/update?client=api&apikey=".config('custom.googleApiKey.serverSide')."&version=$listName:".str_replace('.',':',$version));
        			// if ($isAdmin) { echo "http://sb.google.com/safebrowsing/update?client=api&apikey=".config('custom.googleApiKey.serverSide')."&version=$listName:".str_replace('.',':',$version); exit(); }
        			// Extract new version # from first line
        			preg_match('`^\['.$listName.' (.*)(\]| )`U',$data[0],$matches);
        			if (!$matches[1]) continue; // we couldn't make sense of what google returned
        			$versions[$listName] = $matches[1];
        			unset($data[0]);

        			foreach ($data as $line) {
        				$line = trim($line);
        				$md5hash = trim(substr($line,1));
        				switch(substr($line,0,1)) {
        					case '+':
        						dbQuery("INSERT INTO checkWebsite (source,md5hash) VALUES ('$listName','$md5hash')");
        					break;
        					case '-':
        						dbQuery("DELETE FROM checkWebsite WHERE source='$listName' AND md5hash='$md5hash'");
        					break;
        					case '':
        						continue 2; // blank line, ignore
        					break;
        					default:
        						logWarning("Unknown safebrowsing line type for $line.");
        					break;
        				}
        			}
        		}
        		file_put_contents($VERSION_FILE,serialize($versions));
        	}
            */

        $parsed = parse_url($url);
        $host = $parsed['host'];

        // not yet done: "If the hostname can be parsed as an IP address, it should be normalized to 4 dot-separated decimal values. The client should handle any legal IP address encoding, including octal, hex, and fewer than 4 components."
        $host = trim(strtolower($host), '.'); //  Lowercase the whole string, Remove all leading and trailing dots
        while (strstr($host, '..')) {
            $host = str_replace($host, '..', '.');
        } // Replace consecutive dots with a single dot.

        $fullPath = isset($parsed['path']) ? (string) $parsed['path'] : '';
        // The sequences "/../" and "/./" in the path should be resolved, by replacing "/./" with "/"
        while (strstr($fullPath, '/./')) {
            $fullPath = str_replace('/./', '/', $fullPath);
        }
        // not yet done:  Removing "/../" along with the preceding path component.
        //  Runs of consecutive slashes should be replaced with a single slash character.
        while (strstr($fullPath, '//')) {
            $fullPath = str_replace('//', '/', $fullPath);
        }

        // not yet done: "After performing these steps, percent-escape all characters in the URL which are <= ASCII 32, >= 127, or "%". The escapes should use uppercase hex characters."

        if ($fullPath == '') {
            $fullPath = '/';
        }

        do {
            $path = $fullPath;
            /* (no longer using google safebrowsing) if ($parsed['query'] && checkHash("$host$path?$parsed[query]")) return self::$websiteStatusOptions['malwareBlacklisted']; */
            do {
                /* (no longer using google safebrowsing) if (checkHash("$host$path")) return self::$websiteStatusOptions['malwareBlacklisted']; */
                if (in_array("$host$path", $BANNED_URL)) {
                    return self::$websiteStatusOptions['bannedURL'];
                }
                $path = rtrim($path, '/');
                $path = substr($path, 0, strrpos($path, '/') + 1); // remove the last bit of text up to a '/'
            } while (substr_count($path, '/') >= 1);
            $host = substr($host, strpos($host, '.') + 1); // remove the first bit of text up to a '.'
        } while (substr_count($host, '.') >= 1);

        if ($checkContents) {
            // Check website for errors or banned text.

            if (! self::$curl) {
                self::$curl = curl_init();
            }
            $cookieFile = tempnam('/tmp', 'fetchPage-curl-cookies');
            curl_setopt_array(self::$curl, [CURLOPT_URL => WebsiteTools::removeUrlFragments($url), CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 7, CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_TIMEOUT => 40,
                /* CURLOPT_BUFFERSIZE => 16000, (supposed to limit the amount of data we download, but was causing timeouts) */
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
                CURLOPT_COOKIEFILE => $cookieFile, CURLOPT_COOKIEJAR => $cookieFile, // accept cookies (avoids a redirect loop with some sites)
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // was having an issue with www.bananabarracks.com failing with IPv6, so making it always use IPv4 for now
                CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, ]);
            $contentsData = curl_exec(self::$curl);

            $errno = curl_errno(self::$curl);
            $code = curl_getinfo(self::$curl, CURLINFO_HTTP_CODE);
            $effectiveURL = curl_getinfo(self::$curl, CURLINFO_EFFECTIVE_URL);
            if (! $errno) {
                $contentType = curl_getinfo(self::$curl, CURLINFO_CONTENT_TYPE);
            }

            if ($errno || $contentsData == '') {
                return self::$websiteStatusOptions['pageError'];
            } // some kind of error fetching the page

            if (substr($code, 0, 1) != '2') {
                return self::$websiteStatusOptions['pageError'];
            }

            $contentsData = WebsiteTools::convertHTMLToUTF8($contentsData, $contentType);

            foreach ($BANNED_TEXT as $bt) {
                if (stripos($contentsData, $bt) !== false) {
                    return self::$websiteStatusOptions['bannedText'];
                }
            }

            if (strcasecmp($effectiveURL, $url)) { // if the effective URL is different (redirected)...
                // Check the new effictive URL not for contents, but just to see if the URL is banned...
                return self::getWebsiteStatus($effectiveURL, $checkForBannedURL, false);
            } // check $effectiveURL (won't load content again, so no risk of infinite looping)
        }

        return self::$websiteStatusOptions['ok'];
    }

    //  todo: for testing
    public static function testGetWebsiteStatus($url, $checkForBannedURL, $checkContents, &$contentsData = false)
    {
        // These websites aren't appropriate for using as the website in a listing, but may be ok for other purposes.
        // domains and paths must end with a '/'. can be domain, complete URL, or partial URL path.
        $BANNED_URL = ['hostelz.com/', 'hostelworld.com/', 'hostels.com/', 'airbnb.com/', 'hostel.com/', 'hostelbookers.com/', 'hostelsclub.com/', 'nychostels.com/', 'hostelsprice.com/', 'book-a-hostel.eu/', 'studentholidays.com/', 'trav.com/', 'bookhostels.com/', 'kasbah.com/', 'hostels.se/', 'youth-hostels-in.com/', 'hostelscentral.com/', 'redflag.info/', 'travellerspoint.com/', 'cheapaccommodation.com/', 'find-hostels.com/', 'allensguide.com/', 'expedia.co.uk/', 'bookpromo.com/', 'travelpunk.com/', 'rooms-spain.com/', 'cheapnights.com/', 'cheap-airfare.southwestwa.com/', '1-london-hostels-paris-hostels.com/', 'shopping.net/', 'barcelonaconnect.com/', 'redhotchilli.co.uk/', 'yourtravel-hq.com/', 'stop-barcelona.com/', 'citybreakguide.com/', 'hostelplanet.com/', 'hostels.ws/', 'cheap-holidays-online.co.uk/', 'coolschoolmedia.com/', 'globalhosting4u.co.uk/', 'paris-hostels-central.com/', 'onlineukshops.com/', 'travelhops.com/', 'hostels-cheap.com/', 'lonelyplanetexchange.com/', 'istanbulreservation.com/', 'ttleisuretours.com/', 'hostelsweb.com/', 'airline-flight.ida-danismanlik.com/', 'cybercityguides.com/', 'ehotelzone.com/', 'bookmeahostel.com/', 'helsinki-hotels-reservation.net/', 'hotelandhostel.com/',  'paris-on-line.com/', 'hiptravelguide.com/', 'bookings.org/', 'romantic-getaway.hepchotel.com/', 'alpha-beds.com/', 'channels.nl/', 'tnfsh.com/', 'world-stay.com/', 'australianexplorer.com/',  'rooms-australia.com/', 'global-rooms.com/', 'tellmeabout.co.uk/', 'bcl.com.au/', 'ozeworld.com/', 'cheaphotellinks.com/', 'ehotelfinder.net/', 'travelstay.com/', 'travel-uk.org/', '2camels.com/', 'hotelsprague.cz/', 'motylek.com/', 'guideforeurope.com/', 'find-discount-hotels-rate.com/', 'huge-hotel-discounts.com/', 'hotelsplustours.com/', 'eurocityhotels.com/', 'city-break-hotels.com/', 'euro-hotel-discounts.com/', 'twinroom.com/', 'businesshotelreservation.ch/', 'o-hotels.com/', 'o-discounts.com/', 'eztrip.com/', '1st-hoteldiscounts.com/', 'city-discounts.com/', 'accommodation-source.com/', 'euro-hotels.com/', 'travelpenguin.com/', 'volaretour.com/', 'quickaid.com/', 'hotelreservationsnow.com/', 'city-accommodation.com/', 'hotel-rate-deals.com/', 'lodge4less.com/', 'twinroom.de/', 'lodgingbooker.com/', 'hotels4tourists.com/', 'hotel-rates.com/', 'bestlodging.com/', 'bestlodging.com/', 'discount-hotels-now.com/', 'lodging-specials.com/', 'superior-hotels.com/', 'accommodation.info/', '1-800-868-9218.com/', 'dovehotels.com/', 'hoteldiscounts.tv/', 'hotelratesworldwide.com/', 'uk.laterooms.com/', 'anyhotelanywhere.com/', 'europe-hotels-travel.net/', 'lodgingsavings.com/', 'hotelcity.com/', 'best-hotel-reservations.com/', 'accommodationdesk.com/', 'best-hotel-reservations.com/', '4urhotelreservation.com/', 'hoteldealeo.com/', '1st-hotelrates.com/', 'hotelreservations-online.com/', '1sthotels.com/', 'world-hotel-search.com/', 'hotels-discount-now.com/', 'all-hotels.com/', 'discount-hotels-travels.com/', 'just-hotels.com/', '1st-hotelreservations.com/', 'myhoteldiscount.com/', 'cheap-hotels.co.uk/', 'bookingsavings.com/', 'reservations.ru/', 'maceratur.com/', 'searchbeat.net/', 'fastnet.com.au/', 'web-hotel.com/', 'hotel-discounter.org/', 'whereisthebeach.com/', 'cheephostels.co.uk/', 'w3hotels.com/', 'absolutehostels.com/', 'holidayconnexions.com.au/', 'properties.worldtourism.com.au/', 'holidaycity.com/', 'internethoteldiscounts.com/', 'hotels-discounts.wiztravelmart.com/', 'reservations.hotel-guides.us/', 'reservations.hotel-guides.us/', 'book-europe-hotels.com/', 'hotel-bargain-offers.co.uk/', 'hotelindex.org/', 'cheap-hotel-finder.co.uk/', 'europeanexplorer.com/', 'hotels-of-europe.net/', 'discounthotels-world.com/', 'europehotels.ws/', 'c-europe.com/', 'crystalcities.co.uk/', 'hotels-and-inns.com/', 'i-hotels.iagora.com/', 'webwide-travel.com/', 'america-hotels-travel.net/', 'hotel-price.co.uk/', 'shortbreaks.com/', 'ase.net/', 'sculpt.phidji.com/', 'bookings.org/', 'cityindex.com/', 'artemotore.com/', 'faresrus.com/', 'hotelsbyphone.co.uk/', '1st-class-hotels.com/', 'allthehotels.com/', 'the-hotels.com/', 'planetholiday.com/', 'voyagenow.com/', 'asiahotels.com/', 'booking.allstays.com/', 'online.booking-net.com/', 'hotelresdesk.com/', 'precisionreservations.com/', 'hostel-finder.com/', 'maxinfocanada.com/', 'clickcitytravel.com/', 'booking-net.com/', 'hotelmotelnow.com/', 'accommodation-and-hotels.com/', 'viprez.com/', 'backpackers.com.au/', 'backpackertours.com.au/', 'vipbackpackers.com/', 'hotelsbycity.com/', 'hotelreservationandinformation.com/', 'oztravel.com.au/', 'australia-hotels.biz/', 'malt-shovel.com.au/', 'hotelreservationssite.com/', 'international.motels-hotels-inns.com/', 'accommodation.com/', 'accommodation.co.uk/', 'where-to-stay.com/', 'travel-4-fun.com/', 'resort-deals.com/', 'glassglobal.com/', 'trip-search.com/', 'tripdeals.net/', 'maxinfous.com/', 'we-sell-it.co.uk/', 'nbportal.com/', 'fine-hotels-europe.co.uk/', 'londraweb.com/', 'hotelonclick.com/', 'exploreeurope.com/', 'gullivershotels.com/', 'euroadventures.net/', 'edreams.ru/', 'room4uonline.com/', 'travel-hotels-europe.co.uk/', 'bedandbreakfast-directory.co.uk/', 'eurocheapo.com/', 'iagora.com/', 'interhotel.com/', 'traveltocity.com/', 'traveljournals.net/', '1800stay.com/', 'online-hotels-worldwide.com/', 'anaussieinlondon.co.uk/', 'allukhotels.com/', 'hoteloholic.co.uk/', 'allbackpackers.com.au/', 'hotelnetservice.com/', 'concierge.com/', 'vacations.net/', 'ezhostel.com/', 'bhrhotels.com/', 'securedtravel.com/', 'w3hotels.com/', 'airport-accommodation.co.uk/', 'hotels-4jp.co.uk/', 'hoteltravelzone.com/', 'onetravel.com/', '1travelmart.com/', 'hotels-accommodation-europe.co.uk/', 'hotel-reservations-central.com/', 'cheaphostellinks.com/', 'melbournehotelfinder.com/', 'cheap-hostels-in.com/', 'travel.yahoo.com/', 'hosteltraveler.com/', 'instantworldbooking.com/', 'flashbooking.com/', 'urbanlowdown.com/', 'bedbreakfastreservations.com/', 'totalhostels.com/', 'hostelearth.com/', 'pilgrimreservations.com/', 'bedbreakfasttraveler.com/', 'eurohosteltraveler.com/', 'italyinstantbooking.com/', 'londoninstantbooking.com/', 'peruinstantbooking.com/', 'portugalinstantbooking.com/', 'romeinstantbooking.com/', 'spaininstantbooking.com/', 'ukinstantbooking.com/', 'gomio.com/', 'hostelcn.com/', 'hotelpronto.com/', 'hotelbookingmilan.it/', 'worldweb.com/', 'cybercityguides.com/', 'search-for-hotels.com/', 'hotelguide.net/', 'travelape.com/', 'hotelsandrates.com/', 'hotelreservationandinformation.com/', 'guide-to-hotel.com/', 'the-hotels.com/', 'hotelsandcarrentals.com/', 'mytravelguide.com', 'europe-hotels-comparison.com/', 'kelkoo.co.uk/', 'hotelchannel.com/', 'accommodations.com/', 'express-hotels.com/', 'number1hotels.com/', 'hotels-reservation-services.com/', 'find-hotels.net/', 'realhotels.com/', 'abnhotels.com/', 'accomodations-net.com/', 'tobook.com/', 'start4all.com/', 'laterooms.com/', 'travelmall.com/', 'hotelbase.com/', 'allwebhotels.com/', 'freehotelsearch.com/', 'hotels-with-discounts.com/', 'exploreeurope.com/', 'ase.net/', 'hotels.msk.ru/', 'nbportal.com/', 'hotels.spb.ru/', 'nethotels.com/', 'webtourist.net/', 'laterooms.com/', 'ratestogo.com/', 'express-hotel-guide.com/', 'hotelroomsplus.com/', '12bookhotels.com/', 'worldexecutive.com/', 'guide-to-hotels.com/', 'ifyouski.com/', 'hotels-list.com/', 'reservations-web.com/', 'hotels-search-engine.org/', 'freehotelguide.com/', 'learn4good.com/', 'hostels.net/', 'hostelseurope.com/', 'easytobook.com/', 'realtravel.com/', 'booking.com/', 'hotel.com.au/', 'travel.ebookers.com/', 'holiday-beds-direct.com/', 'hostelseverywhere.com/', 'unhostels.org/', 'newyorkbesthostels.com/', 'hostelinnewyork.com/', 'hotelplanner.com/', 'travelpod.com/', 'hosteltimes.com/', 'backpackers.com.tw/', 'best4hostels.com/', 'hostels247.com/', 'lonelyplanet.com/', 'travbuddy.com/', 'virtualtourist.com/', 'venere.com/', 'flightcentre.com.au/', 'boomerangservices.ro/', 'faxts.com/', 'daodao.com/', 'in-hotel.org/', 'cheap-hostels.org/', 'worldofhotels.com/', 'boomerangservices.ro/', 'pleasetakemeto.com/', 'epictrip.com/', 'beginthier.nl/', 'discount-hotel-selection.com/', 'waarbenjij.nu/', 'ozhostels.com.au/', 'error404.000webhost.com/', 'hotelscombined.com.au/', 'statravel.com.au/', 'www.homestead.com/' /* is ok for subdomains other than www. */, 'en.wordpress.com/' /* typo redirect subdomain */, 'www.geocities.com/' /* no longer up */,
            'tripadvisor.com/' /* but may have contact info */, 'tripadvisor.com.tr/', 'tripadvisor.com.ca/', 'tripadvisor.in/', 'tripadvisor.co.uk/', 'tripadvisor.de/', 'tripadvisor.it/', 'tripadvisor.es/', 'tripadvisor.jp/',
        ];

        $BANNED_TEXT = [
            'This domain may be for sale.', 'THIS DOMAIN MAY BE FOR', 'Buy this Domain', 'parking_form', 'http://searchportal.information.com', 'http://ndparking.com', 'parked_layouts', 'parked_images', '>Parked.com', 'Este site está bloqueado', 'Nuestros servidores de dns ya reconocen su sitio.', 'sedoparking.com', 'Pontonet - Webhosting', '"parking.css"', '.casalemedia.com', '/domainpark/', 'sexualhealth', 'AVAILABLE - FreeServers', 'Site Disabled', 'AVAILABLE - 50megs', 'Site is not available.', 'Sorry, this page was not found', '> doesn&#8217;t&nbsp;exist<', 'domainnamesales.com', 'by Register.com', '/images.komplads.net', 'var parkingData', '>Lifestream Downloads<', 'The domain is for sale!', 'name=\"landingparent\"', 'turing_cluster_prod' /* used on expired domains */, 'Domain Registered by WebNow', 'This domain name expired', 'http://mcc.godaddy.com/park/', 'Inquire about this domain', 'http://www.google.com/adsense/domains/caf.js', 'nutrisystem', 'dietboss', 'exitsplashpage', '薬剤師' /* "pharmacist" */,
            '新大陆娱乐' /* New World Entertainment */, 'www.google.com/adsense/domains/', 'parked-content.godaddy.com',
            'This site is temporarily unavailable', '>MySQL Fatal Error<', '>It is currently being parked by the owner<', 'Enter a domain and click check to see if it is available',
            'is not configured on this server', 'hugedomains.com', 'This Account has been suspended', 'Site Suspended', '<img alt="Bluehost"',
            'This site requires JavaScript and Cookies to be enabled. Please change your browser settings or upgrade your browser.', /* used by spammy sites, can't find any better identifier strings */
            'http://fwdssp.com', '<title>UK Cloud Web Hosting',
            '<frameset rows="100%,*" frameborder="no" border="0" framespacing="0">', /* used to hide the actual content of spam pages by using frames */
            '">Click here to proceed</a>. </body>', /* used by a bad malware site, couldn't find anything else to distinguish it, but this should do it */
            '<meta http-equiv="Refresh" content="0;url=defaultsite" />', /* used by sedo parked domains */
            'If this is your domain name you must renew it immediately', 'https://dan.com/domain-seller', 'This Page Is Under Construction', 'Domain for Sale', 'Future home of something',
        ];

        $CORRECT_TEXT = [
            'accommodation', 'hostel', 'backpacker', 'room type', 'facility',
        ];

        //  listing name, address, city

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return self::$websiteStatusOptions['invalidURL'];
        }

        $INVALID_CHARS = [' ', ',', "\n", "\r", '@'];
        $url = trim($url);
        foreach ($INVALID_CHARS as $char) {
            if (strpos($url, $char) !== false) {
                return self::$websiteStatusOptions['invalidURL'];
            }
        }

        /* This no longer works.  Would need to upgrade to their new API.  Could use a library like https://github.com/Beaver6813/phpGSB, but it's complicated and doesn't use MySQLi.

            $VERSION_FILE = USER_ROOT.'data/checkWebsiteData.txt';
        	$UPDATE_DELAY = 24*60*60; // Google recommends every 30 minutes, but we don't need to be as current

        	if ((time()-filemtime($VERSION_FILE)) > $UPDATE_DELAY) {
        		touch($VERSION_FILE); // set the modified time quickly
        		$versions = unserialize(file_get_contents($VERSION_FILE));
        		if (!$versions) return self::$websiteStatusOptions['unknown']; // error // $versions = array('goog-black-hash'=>'1:-1', 'goog-malware-hash'=>'1:-1'); // this should only happen once
        		foreach ($versions as $listName=>$version) {
        			$data = file("http://sb.google.com/safebrowsing/update?client=api&apikey=".config('custom.googleApiKey.serverSide')."&version=$listName:".str_replace('.',':',$version));
        			// if ($isAdmin) { echo "http://sb.google.com/safebrowsing/update?client=api&apikey=".config('custom.googleApiKey.serverSide')."&version=$listName:".str_replace('.',':',$version); exit(); }
        			// Extract new version # from first line
        			preg_match('`^\['.$listName.' (.*)(\]| )`U',$data[0],$matches);
        			if (!$matches[1]) continue; // we couldn't make sense of what google returned
        			$versions[$listName] = $matches[1];
        			unset($data[0]);

        			foreach ($data as $line) {
        				$line = trim($line);
        				$md5hash = trim(substr($line,1));
        				switch(substr($line,0,1)) {
        					case '+':
        						dbQuery("INSERT INTO checkWebsite (source,md5hash) VALUES ('$listName','$md5hash')");
        					break;
        					case '-':
        						dbQuery("DELETE FROM checkWebsite WHERE source='$listName' AND md5hash='$md5hash'");
        					break;
        					case '':
        						continue 2; // blank line, ignore
        					break;
        					default:
        						logWarning("Unknown safebrowsing line type for $line.");
        					break;
        				}
        			}
        		}
        		file_put_contents($VERSION_FILE,serialize($versions));
        	}
            */

        $parsed = parse_url($url);
        $host = $parsed['host'];

        // not yet done: "If the hostname can be parsed as an IP address, it should be normalized to 4 dot-separated decimal values. The client should handle any legal IP address encoding, including octal, hex, and fewer than 4 components."
        $host = trim(strtolower($host), '.'); //  Lowercase the whole string, Remove all leading and trailing dots
        while (strstr($host, '..')) {
            $host = str_replace($host, '..', '.');
        } // Replace consecutive dots with a single dot.

        $fullPath = isset($parsed['path']) ? (string) $parsed['path'] : '';
        // The sequences "/../" and "/./" in the path should be resolved, by replacing "/./" with "/"
        while (strstr($fullPath, '/./')) {
            $fullPath = str_replace('/./', '/', $fullPath);
        }
        // not yet done:  Removing "/../" along with the preceding path component.
        //  Runs of consecutive slashes should be replaced with a single slash character.
        while (strstr($fullPath, '//')) {
            $fullPath = str_replace('//', '/', $fullPath);
        }

        // not yet done: "After performing these steps, percent-escape all characters in the URL which are <= ASCII 32, >= 127, or "%". The escapes should use uppercase hex characters."

        if ($fullPath == '') {
            $fullPath = '/';
        }

        do {
            $path = $fullPath;
            /* (no longer using google safebrowsing) if ($parsed['query'] && checkHash("$host$path?$parsed[query]")) return self::$websiteStatusOptions['malwareBlacklisted']; */
            do {
                /* (no longer using google safebrowsing) if (checkHash("$host$path")) return self::$websiteStatusOptions['malwareBlacklisted']; */
                if (in_array("$host$path", $BANNED_URL)) {
                    return self::$websiteStatusOptions['bannedURL'];
                }
                $path = rtrim($path, '/');
                $path = substr($path, 0, strrpos($path, '/') + 1); // remove the last bit of text up to a '/'
            } while (substr_count($path, '/') >= 1);
            $host = substr($host, strpos($host, '.') + 1); // remove the first bit of text up to a '.'
        } while (substr_count($host, '.') >= 1);

        if ($checkContents) {
            // Check website for errors or banned text.

            if (! self::$curl) {
                self::$curl = curl_init();
            }
            $cookieFile = tempnam('/tmp', 'fetchPage-curl-cookies');
            curl_setopt_array(self::$curl, [CURLOPT_URL => WebsiteTools::removeUrlFragments($url), CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 7, CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_TIMEOUT => 40,
                /* CURLOPT_BUFFERSIZE => 16000, (supposed to limit the amount of data we download, but was causing timeouts) */
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
                CURLOPT_COOKIEFILE => $cookieFile, CURLOPT_COOKIEJAR => $cookieFile, // accept cookies (avoids a redirect loop with some sites)
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // was having an issue with www.bananabarracks.com failing with IPv6, so making it always use IPv4 for now
                CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, ]);
            $contentsData = curl_exec(self::$curl);

            $errno = curl_errno(self::$curl);
            $code = curl_getinfo(self::$curl, CURLINFO_HTTP_CODE);
            $effectiveURL = curl_getinfo(self::$curl, CURLINFO_EFFECTIVE_URL);
            if (! $errno) {
                $contentType = curl_getinfo(self::$curl, CURLINFO_CONTENT_TYPE);
            }

            if ($errno || $contentsData == '') {
                return self::$websiteStatusOptions['pageError']; // some kind of error fetching the page
            }

            if (substr($code, 0, 1) != '2') {
                return self::$websiteStatusOptions['pageError'];
            }

            $contentsData = WebsiteTools::convertHTMLToUTF8($contentsData, $contentType);

//        	foreach ($CORRECT_TEXT as $ct) {
//                if ( stripos( $contentsData, $ct ) !== false ) {
//                    return self::$websiteStatusOptions['bannedText'];
//                }
//            }

            foreach ($BANNED_TEXT as $bt) {
                if (stripos($contentsData, $bt) !== false) {
                    return self::$websiteStatusOptions['bannedText'];
                }
            }

            if (strcasecmp($effectiveURL, $url)) {
                // if the effective URL is different (redirected)...
                // Check the new effictive URL not for contents, but just to see if the URL is banned...
                return self::getWebsiteStatus($effectiveURL, $checkForBannedURL, false); // check $effectiveURL (won't load content again, so no risk of infinite looping)
            }
        }

        return self::$websiteStatusOptions['ok'];
    }
}
