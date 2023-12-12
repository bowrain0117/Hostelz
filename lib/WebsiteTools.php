<?php

namespace Lib;

/* Tools for fetching/scraping data, etc. */

use Bakame\Laravel\Pdp\Facades\TopLevelDomains;

class WebsiteTools
{
    public static function fetchPage($url, $postVars = null, $curlOptions = false, $convertToUTF8 = false, $reportError = false)
    {
        static $curl;

        if (! $curl) {
            $curl = self::initCurl();
        }
        curl_setopt($curl, CURLOPT_URL, self::removeUrlFragments($url));

        if ($postVars !== null) {
            $paramString = '';
            if (is_array($paramString)) {
                // (Could probably use http_build_query() instead)
                foreach ($postVars as $name => $value) {
                    $paramString .= '&' . $name . '=' . rawurlencode($value);
                }
            } else {
                $paramString = $postVars;
            }
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $paramString);
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }

        if ($curlOptions) {
            curl_setopt_array($curl, $curlOptions);
        }

        $data = curl_exec($curl);

        if ($reportError && $data == '') {
            $errorText = curl_error($curl);
            if ($errorText != '') {
                logWarning($errorText);
            }
        }

        if ($data != '' && $convertToUTF8) {
            // (Should convert HTML entities first?)
            $data = self::convertHTMLToUTF8($data, curl_getinfo($curl, CURLINFO_CONTENT_TYPE));
        }

        return $data;
    }

    // This removes any "#" portion of the URL (which causes curl to fail to fetch it)

    public static function removeUrlFragments($url)
    {
        return explode('#', $url)[0];
    }

    public static function initCurl()
    {
        $cookieFile = tempnam('/tmp', 'fetchPage-curl-cookies');
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36', // "Chrome/51.0.2704.103 Safari/537.36" was causing the SEO API to fail
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 7,
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 35,
            CURLOPT_TIMEOUT => 40,
            // Turn off the server and peer verification (TrustManager Concept).
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            // CURLOPT_SSL_VERIFYSTATUS => false, (requires PHP 7)
            // Save cookies between fetchPage() calls (needed for some sites)
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_COOKIEJAR => $cookieFile,
        ]);

        return $curl;
    }

    // Based on http://stackoverflow.com/questions/2510868/php-convert-curl-exec-output-to-utf8
    public static function convertHTMLToUTF8($data, $contentType = '')
    {
        if (! is_string($data) || $data == '') {
            return $data;
        }

        /* 1: HTTP Content-Type: header */
        preg_match('@([\w/+]+)(;\s*charset=(\S+))?@i', $contentType, $matches);
        if (isset($matches[3])) {
            $charset = $matches[3];
        }

        /* 2: <meta> element in the page */
        if (! isset($charset) || $charset == 'none') {
            preg_match('@<meta\s+http-equiv="Content-Type"\s+content="([\w/]+)(;\s*charset=([^\s"]+))?@i', $data, $matches);
            if (isset($matches[3])) {
                $charset = $matches[3];
            }
        }

        /* 3: <xml> element in the page */
        if (! isset($charset) || $charset == 'none') {
            preg_match('@<\?xml.+encoding="([^\s"]+)@si', $data, $matches);
            if (isset($matches[1])) {
                $charset = $matches[1];
            }
        }

        /* 4: PHP's heuristic detection */
        if (! isset($charset) || $charset == 'none') {
            $encoding = mb_detect_encoding($data);
            if ($encoding) {
                $charset = $encoding;
            }
        }

        /* 5: Default for HTML */
        if (! isset($charset) || $charset == 'none') {
            if (strstr($contentType, 'text/html') === 0) {
                $charset = 'ISO 8859-1';
            }
        }

        /* Convert it if it is anything but UTF-8 */
        /* You can change "UTF-8"  to "UTF-8//IGNORE" to
           ignore conversion errors and still output something reasonable */
        if (isset($charset) && $charset != 'none' && strtoupper($charset) != 'UTF-8') {
            $convertedData = @iconv($charset, 'UTF-8', $data);
            if ($convertedData !== false) {
                return $convertedData;
            }
        }

        return $data;
    }

    // http://www.w3schools.com/XPath/xpath_syntax.asp
    // http://www.stylusstudio.com/docs/v62/d_xpath15.html
    public static function fetchXPath($urlOrData, $postVars, $paths, $curlOptions = '', $dontReportErrors = false)
    {
        if (strpos($urlOrData, 'http') === 0) {
            $data = self::fetchPage($urlOrData, $postVars, $curlOptions);
            if ($data == false) {
                if (! $dontReportErrors) {
                    logWarning("Couldn't fetch url '$urlOrData'.");
                }

                return false;
            }
        } else {
            $data = $urlOrData;
        }

        /* Didn't end up needing it for this one, but maybe another one
    	// Remove CDATA tags (this is hack, not sure if there's a better way)
    	$data = str_replace('<![CDATA[', '', $data);
    	$data = str_replace(']]>', '', $data);
    	*/

        $dom = new \DOMDocument();
        @$dom->loadHTML($data);
        if ($dom == false) {
            if (! $dontReportErrors) {
                logWarning("Couldn't load dom for page 'urlOrData'.");
            }

            return false;
        }

        $result = [];
        $xpath = new \DOMXPath($dom);
        foreach ($paths as $name=>$path) {
            if ($path == '') {
                $result[$name] = $data;
                continue;
            }
            $hrefs = $xpath->evaluate($path);
            if ($hrefs == false) {
                logWarning("Couldn't get path $name for '$urlOrData'.");
                continue;
            }
            // echo $hrefs->saveXML(); (can't do this, have to create a new DOMDocument to output xml)
            if ($hrefs->length == 1) {
                $result[$name] = self::trimAll($hrefs->item(0)->nodeValue);
            } else {
                for ($i = 0; $i < $hrefs->length; $i++) {
                    $result[$name][] = self::trimAll($hrefs->item($i)->nodeValue);
                }
            }
        }

        return $result;
    }

    // also trims non-breaking spaces which regular trim doesn't
    private static function trimAll($s)
    {
        return trim($s, chr(0xC2) . chr(0xA0) . " \0\r\n\t\x0B");
    }

    // Used for extracting embedded videos

    public static function extractEmbedCode($url)
    {
        // We originally did our own oembed extracting, but embed.ly is better since it works with sites that aren't even oohembed compliant.
        // Used to be oohembed.com -> now embed.ly. API key ee4768c4dfe04a76a7000249d60a0467
        // Docs: http://embed.ly/docs/embed/api/arguments
        // uses oembed standard.  see http://www.oembed.com/
        // 5,000 URLs per Month (or pay for more)
        //
        // 2017-03 Update: They are now charging for over 100 extractions.  Probably want to re-enable our own code.

        $oohembed = @file_get_contents('http://api.embed.ly/1/oembed?key=ee4768c4dfe04a76a7000249d60a0467&maxwidth=620&url=' . urlencode($url));
        if ($oohembed == '') {
            logError("No result for extractEmbedCode($url).");

            return null;
        }

        $res = json_decode($oohembed);
        if (! is_object($res)) {
            logError("Can't decode json for extractEmbedCode($url).");

            return null;
        }
        if (! isset($res->html)) {
            logError("No embed HTML could be found for extractEmbedCode($url).");

            return null;
        }

        $videoHTML = $res->html;
        if ($videoHTML != '') {
            return $videoHTML;
        } else {
            return null;
        }

        /*
        our old code:
        // some code inspired by http://pear.php.net/package/Services_oEmbed/docs/latest/__filesource/fsource_Services_oEmbed__Services_oEmbed-0.1.0ServicesoEmbed.php.html

    	if (preg_match_all('`<link([^>]*)type="(application/json|text/xml)\+oembed"([^>]*)>`si', $data, $matches)) {
    		$foundEmbedType = $foundEmbedURL = '';
    		foreach ($matches[0] as $i => $link) {
    			$h = [ ];
    			if(preg_match('/href="([^"]+)"/i', $link, $h)) {
    				$foundEmbedType = $matches[2][$i];
    				$foundEmbedURL = $h[1];
    				if($foundEmbedType == 'application/json') break; // our preferred type, stop now
    			}
    		}

    		if ($foundEmbedURL!='') {
                //
    			//if(strpos($foundEmbedURL,'http://') !== 0 && strpos($foundEmbedURL,'https://') !== 0)
    			//	$url = "http://$foundEmbedURL"; // add "http://" if needed
                //

    			// Add our own extra parameters
    			if (strpos($foundEmbedURL,'?') === false)
    				$foundEmbedURL .= '?';
    			else
    				$foundEmbedURL .= '&';
    			$foundEmbedURL .= 'maxheight=400&maxwidth=600';

    			$data = file_get_contents($foundEmbedURL);

    			unset($videoHTML);
    			if ($foundEmbedType == 'application/json') {
    				$res = json_decode($data);
    				if(!is_object($res)) { triggerError("Can't decode json for $url."); return false; }
    				$videoHTML = $res->html;
    			}
    			else if ($foundEmbed['text/xml']) {
    				libxml_use_internal_errors(true);
    				$res = simplexml_load_string($data);
    				if(!$res instanceof SimpleXMLElement)
    					{ triggerError("Can't load XML for embed $url."); return false; }
    				$videoHTML = $res->html;
    			}
    			else { triggerError("Unknown type for embed $url."); return false; }

    			if ($videoHTML == '') { triggerError("HTML missing for embed $url."); return false; }
    			return $videoHTML;
    		}
    	}
        */

        /*
        	$data = file_get_contents($url);
        	if($data == '') return false;

        	if($GLOBALS['isAdmin']) { // probably too risky to let just anyone do this
        		// Couldn't find oEmbed, try extracting generic video URL instead (such as http://www.hostels.tv videos)
        		// (note: 5min.com is missing the </embed> tag, so we don't look for that
        		if(!preg_match('`\<object ([^>]*)\>(.*)\<\/object\>`siU', $data, $matches)) return false; // give up
        		if($matches[1] != '' && $matches[2] != '') {
        			// we supply our own height/width
        			return "<object width=550 height=320>$matches[2]</object>";
        		}
        	}
        	return false;
        }
        */
    }

    public static function getVideoSchema($videoID)
    {
        $key = config('custom.googleApiKey.clientSide');

        $response = file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=contentDetails%2Csnippet%2Cstatistics&id={$videoID}&key={$key}");

        if ($response == '') {
            logError("No result for getVideoSchema({$videoID}).");

            return null;
        }

        $res = json_decode($response);
        if (! is_object($res)) {
            logError("Can't decode json for getVideoSchema({$videoID}).");

            return null;
        }
        if (! isset($res->items[0])) {
            logError("No items could be found for getVideoSchema({$videoID}).");

            return null;
        }

        $items = $res->items[0];
        $schema = [];
        $schema['embedURL'] = "https://youtube.googleapis.com/v/{$videoID}";
        $schema['duration'] = isset($items->contentDetails->duration)
            ? $items->contentDetails->duration
            : '';
        $schema['uploadDate'] = isset($items->snippet->publishedAt)
            ? $items->snippet->publishedAt
            : '';

        $schema['thumbnailURL'] = isset($items->snippet->thumbnails->maxres->url)
            ? $items->snippet->thumbnails->maxres->url
            : $items->snippet->thumbnails->default->url;

        $schema['interactionCount'] = isset($items->statistics->viewCount)
            ? $items->statistics->viewCount
            : '';

        $schema['interactionCount'] = isset($items->statistics->viewCount)
            ? $items->statistics->viewCount
            : '';

        return json_encode($schema);
    }

    // (includes 'www' or other subdomains.)

    public static function getHostName($url)
    {
        return mb_strtolower(parse_url($url, PHP_URL_HOST));
    }

    public static function getRootDomainName($url)
    {
        return TopLevelDomains::resolve($url)->registrableDomain()->toString() ?: null;
    }

    public static function searchForContactEmails($domain, $name = '', $limit = 10)
    {
        $url = 'https://api.emailhunter.co/v1/search?domain=' . urlencode($domain) . '&api_key=' . config('custom.emailhunterKey');
        if ($name != '') {
            $result = parseFirstAndLastName($name);
            if ($result) {
                $url .= '&first_name=' . urlencode($result['firstName']) . '&last_name=' . urlencode($result['lastName']);
            }
        }

        $data = @file_get_contents($url);
        if (! $data) {
            return null;
        }
        $data = json_decode($data, true);
        if (! $data || $data['status'] != 'success') {
            logError("searchForContactEmails error for $url.");

            return null;
        }

        $results = [];
        foreach ($data['emails'] as $emailData) {
            $results[] = $emailData['value'];
            if (count($results) == $limit) {
                break;
            }
        }

        return $results;
    }
}
