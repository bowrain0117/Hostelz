<?php

/*

    "the API provides 100 search queries per day for free. If you need more, you may sign up for billing in the Developers Console. Additional requests cost $5 per 1000 queries, up to 10k queries per day."

    alternative data sources that could be used:
       - http://siteexplorer.search.yahoo.com/search?p=http%3A%2F%2Fhostelz.com&bwm=i&bwmo=&bwmf=s -> tsv export.
*/

namespace Lib;

use Exception;
use Illuminate\Support\Str;

class WebSearch
{
    public static function search($search, $numberOfResults = 10)
    {
        return self::doGoogleSearch($search, $numberOfResults);
    }

    public static function inboundLinks($toURL, $numberOfResults = 10)
    {
        // remove http:// part
        $toURL = 'link:' . str_replace(['http://', 'https://'], ['', ''], $toURL);

        return self::doGoogleSearch($toURL, $numberOfResults);
    }

    private static function doGoogleSearch($search, $numberOfResults)
    {
        $results = [];

        while (count($results) < $numberOfResults) {
            $url = 'https://www.googleapis.com/customsearch/v1?' .
                'q=' . urlencode($search) .
                '&num=' . min($numberOfResults - count($results), 10) . // only works if num is <= 10
                '&start=' . (count($results) + 1) .
                // '&fields='.urlencode('items(title,snippet,link)') .
                '&cx=' . urlencode('006373179204573247532:atvit5io8xg') . // our custom search engine, see https://cse.google.com/cse/all
                '&key=' . urlencode(config('custom.googleApiKey.serverSide'));
            $data = GoogleAPI::makeApiCall($url);
            $data = json_decode($data);

            foreach ($data->items as $item) {
                $results[] = [
                    'title' => $item->title,
                    'desc' => $item->snippet,
                    'url' => $item->link,
                ];
            }
            if (count($results) >= $data->searchInformation->totalResults) {
                break;
            }
        }

        return $results;
    }

    /* (old method by scraping Google with a script on hostelinfo.com. No longer works since Google made some changes.
    private static function doGoogleSearch($search, $minimumNumberOfResults)
    {
        $ctx = stream_context_create([ 'http' => array('timeout' => 5*60) ]);
    	$data = file_get_contents("http://hostelinfo.com/googleFetch.php?s=".rawurlencode('link:'.$search)."&results=$minimumNumberOfResults", 0, $ctx);
    	echo $data;
    	if ($data == '') return false;
    	if (Str::startsWith($data, 'unexpected error')) {
    	    logError("doGoogleSearch('$search') returned '$data'.");
    	    return null;
    	}

    	return unserialize($data);
    }
    */

    /* (other search methods from the old code.  dont know if any of these still work

    function yahooWebSearch($s, $minResults, &$results, $resultsURL='') {
    	if($resultsURL != '') {
    		$data = file_get_contents('http://boss.yahooapis.com'.$resultsURL);
    	}
    	else {
    		$data = file_get_contents("http://boss.yahooapis.com/ysearch/web/v1/".urlencode($s)."?appid=44sT1N7V34HVyi1QCTJXROTxQOuJSYE7iCWLRfM6.O6aZKBtXxsb8bXAyJM9cUE-&format=json&type=html&style=raw&lang=en&count=".($minResults-count($results)));
    	}
    	$data = json_decode($data);
    	if($data == false) return false;
    	if(!isset($data->ysearchresponse->resultset_web)) return false;
    	foreach ($data->ysearchresponse->resultset_web as $r) {
    		$results[] = array(
    				'title' => $r->title,
    				'desc' => $r->abstract,
    				'url' => $r->url
    			);
    	}
    	if(isset($data->ysearchresponse->nextpage) && count($results)<$minResults && $data->ysearchresponse->nextpage!='' && $data->ysearchresponse->nextpage!=$resultsURL)
    		yahooWebSearch($s, $minResults, $results, $data->ysearchresponse->nextpage);
    	return true;
    }

    function yahooInboundLinkSearch($s, $minResults, &$results, $resultsURL='') {
    	if($resultsURL != '') {
    		$data = file_get_contents('http://boss.yahooapis.com'.$resultsURL);
    	}
    	else {
    		$data = file_get_contents("http://boss.yahooapis.com/ysearch/se_inlink/v1/".urlencode($s)."?appid=44sT1N7V34HVyi1QCTJXROTxQOuJSYE7iCWLRfM6.O6aZKBtXxsb8bXAyJM9cUE-&format=json&style=raw&entire_site=1&omit_inlinks=domain&lang=en&count=".($minResults-count($results)));
    	}
    	$data = json_decode($data);
    	if($data == false) return false;
    	if(!isset($data->ysearchresponse->resultset_se_inlink)) return false;
    	foreach ($data->ysearchresponse->resultset_se_inlink as $r) {
    		$results[] = array(
    				'title' => $r->title,
    				'desc' => $r->abstract,
    				'url' => $r->url
    			);
    	}
    	if(isset($data->ysearchresponse->nextpage) && count($results)<$minResults && $data->ysearchresponse->nextpage!='' && $data->ysearchresponse->nextpage!=$resultsURL)
    		yahooInboundLinkSearch($s, $minResults, $results, $data->ysearchresponse->nextpage);
    	return true;
    }

    function alexaInboundLinkSearch($url, $minResults, &$results) {
    	$ACCESS_KEY_ID = "0KNCH0MYTWW5RCD5VJG2";
    	$SECRET_ACCESS_KEY = "caYIYcEMf5YKlVyV/9psvls++Adzv+VSXtuMGufv";
    	$SERVICE_ENDPOINT = "http://awis.amazonaws.com?";
    	$action = 'SitesLinkingIn';
    	$responseGroup = "SitesLinkingIn";

    	$timestamp = gmdate("Y-m-d\TH:i:s.\\0\\0\\0\\Z", time());

    	// calculate_RFC2104HMAC
    	$signature = base64_encode(pack("H*", sha1((str_pad($SECRET_ACCESS_KEY, 64, chr(0x00)) ^(str_repeat(chr(0x5c), 64))) .
    		pack("H*", sha1((str_pad($SECRET_ACCESS_KEY, 64, chr(0x00)) ^(str_repeat(chr(0x36), 64))) .
    		$action.$timestamp)))));

       	$data = file_get_contents($SERVICE_ENDPOINT.
    		"AWSAccessKeyId=$ACCESS_KEY_ID&Timestamp=".urlencode($timestamp)."&Signature=".urlencode($signature).
    		"&Action=$action&ResponseGroup=$responseGroup&Url=".urlencode($url)."&Count=$minResults&Version=2005-07-11");
    	if(!$data) return false;
    	preg_match_all('`\<aws\:Title\>(.*)\<\/aws\:Title\>.*\<aws\:Url\>(.*)\<\/aws\:Url\>.*`sU', $data, $matches, PREG_SET_ORDER);
    	if(!$matches) return false;
    	foreach ($matches as $match) {
    		if('http://'.$match[1] == $match[2]) $match[1] = ''; // don't let it give us the URL as the title
    		$results[] = array(
    			'title' => $match[1],
    			'url' => str_replace(':80', '', $match[2]) // for some reach they result URLs with ":80" on them
    		);
    	}
    	return true;
    }
    */
}
