<?php

namespace Lib;

/* it usually works, but the code is a mess.  should probably use a different php spider library */

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class Spider
{
    public $maxPageDataRead = 200000; // bytes

    public $abortDownloadIfOverKB = 1000; // this prevents Curl out of memory errors if abnormally large page

    public $maxLinksToFollowPerPage = 100;

    public $maxTotalPages = 300;

    public $verbose = false;

    public $obeyNoFollow = true;

    public $ignoreExtensions = ['gif', 'jpg', 'jpeg', 'bmp', 'rtf', 'ps', 'arj', 'zip', 'gz', 'tar', 'exe', 'tgz', 'bz', 'bz2', 'wav', 'mp3', 'au', 'aiff', 'bin', 'mpg', 'mpeg', 'mov', 'qt', 'tif', 'tiff', 'tar', 'z', 'avi', 'ram', 'ra', 'arc', 'gzip', 'hqx', 'sit', 'sea', 'uu', 'png', 'css', 'ico', 'cl', 'jar', 'xml', 'pdf', 'doc', 'xls', 'swf'];

    private $curl;

    private $visitedLinks;

    private $totalPages;

    private $mainurl = '';

    private $mainUrlParts;

    private $mainUrlRootDomain;

    private $mainUrlDirectory;

    private $restrictTo;

    private $findLinkPatterns;

    private $foundLinkPatterns;

    // 'domain' means 'www.' or not is ok, 'host' limits it to exact domain/subdomain.
    private $RESTRICT_TO = ['all' => 1, 'domain' => 2, 'host' => 3, 'directory' => 4];

    public static function getRootDomain($url)
    {
        preg_match("`(?:www\.){0,1}(.+?)(\/|$)`i", str_replace('http://', '', $url), $matches);

        return $matches[1];
    }

    public function spiderSiteWithCaching($url, $depth, $findLinkPatterns, $restrictTo, $type, $equivalentIfSameDomain, $daysToUseOldSpidering, $forceRespidering = false)
    {
        $urlParts = $this->parseURL($url);
        if (! $urlParts) {
            return false;
        }

        $rootDomain = self::getRootDomain($urlParts['host']);
        if ($rootDomain == '') {
            logWarning("Can't get root domain for '$url'.");

            return false;
        }

        $query = DB::table('spiderResults')->where('type', $type)->orderBy('lastUpdateDate', 'desc'); // (order shouldn't usually matter, but get newest)
        if ($equivalentIfSameDomain) {
            $query->where('domain', $rootDomain);
        } else {
            $query->where('url', $url);
        }
        $previousSpiderResult = $query->first();

        if (! $forceRespidering && $previousSpiderResult && $previousSpiderResult->lastUpdateDate >= Carbon::now()->subDays($daysToUseOldSpidering)) {
            return $previousSpiderResult->spiderResults == '' ? '' : unserialize($previousSpiderResult->spiderResults);
        }

        $spiderResults = $this->spiderSite($url, $depth, $findLinkPatterns, $restrictTo);

        $data = ['type' => $type, 'domain' => $rootDomain, 'url' => $url, 'lastUpdateDate' => Carbon::now()->format('Y-m-d'),
            'spiderResults' => $spiderResults ? serialize($spiderResults) : '', ];

        if ($previousSpiderResult) {
            DB::table('spiderResults')->where('id', $previousSpiderResult->id)->update($data);
        } else {
            DB::table('spiderResults')->insert($data);
        }

        return $spiderResults;
    }

    public function spiderSite($url, $depth, $findLinkPatterns, $restrictTo)
    {
        $this->findLinkPatterns = $findLinkPatterns;
        $this->visitedLinks = $this->foundLinkPatterns = [];
        $this->totalPages = 0;

        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = "http://$url";
        } // add "http://" if needed
        $this->mainUrlParts = $this->parseURL($url);
        if (@$this->mainUrlParts['path'] == '') {
            $url = $url . '/';
        }
        $this->mainurl = $url;

        $this->mainUrlRootDomain = self::getRootDomain($this->mainUrlParts['host']);
        if ($this->mainUrlRootDomain == '') {
            logWarning("spiderSite can't get root domain for $url.");

            return false;
        }
        $this->mainUrlDirectory = $this->remove_file_from_url($url);

        $this->restrictTo = $this->RESTRICT_TO[$restrictTo];
        if ($this->restrictTo == 0) {
            throw new Exception("Unknown restrictTo $restrictTo.");
        }

        if ($this->verbose) {
            echo "mainurl: $this->mainurl, mainUrlParts.host = " . $this->mainUrlParts['host'] . ", mainUrlRootDomain = $this->mainUrlRootDomain, mainUrlDirectory = $this->mainUrlDirectory, restrictTo = $this->restrictTo<br>";
        }

        // $disallowed = $this->checkRobotsDotTxt($this->mainUrlParts['host']);

        $this->curl = curl_init();
        curl_setopt_array($this->curl, [CURLOPT_HEADER=>false, CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>7, CURLOPT_CONNECTTIMEOUT=>25, CURLOPT_TIMEOUT=>40, CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36']);
        if ($this->abortDownloadIfOverKB) {
            curlAbortIfOverDownloadSize($this->curl, $this->abortDownloadIfOverKB);
        }

        $this->visitedLinks = [];
        $this->spiderURL($url, $depth);

        curl_close($this->curl);

        if ($this->verbose) {
            echo '<p>found:';
        }
        foreach ($this->foundLinkPatterns as $type=>$matches) {
            if ($this->verbose) {
                echo "<br>$type:<br>";
            }
            foreach ($matches as $linkTo=>$linkFrom) {
                if ($this->verbose) {
                    echo "$linkFrom links to $linkTo<br>";
                }
            }
        }

        return $this->foundLinkPatterns;
    }

    private function spiderURL($url, $depth)
    {
        $this->totalPages++;
        if ($this->totalPages > $this->maxTotalPages) {
            return;
        }

        if ($this->verbose) {
            echo "<br>$depth" . str_repeat('&nbsp;', 3 * (7 - $depth)) . $url . ' ';
        }
        $this->visitedLinks[] = $url;

        // $data = @file_get_contents($url,0,NULL,0,$this->maxPageDataRead); didn't give us a way to detect redirects

        curl_setopt($this->curl, CURLOPT_URL, $url);
        $data = curl_exec($this->curl);
        if (curl_errno($this->curl)) {
            return;
        }

        // there apparently isn't a curl option to limit the size of incoming data, so we just truncate it and hope we didn't already run out of memory
        if (strlen($data) > $this->maxPageDataRead) {
            $data = substr($data, 0, $this->maxPageDataRead);
        }

        $code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $finalURL = curl_getinfo($this->curl, CURLINFO_EFFECTIVE_URL);
        if ($finalURL != $url) {
            $this->visitedLinks[] = $url = $this->makeFullURL($finalURL, '');
            if ($this->verbose) {
                echo "($url)";
            }
        }
        if (substr($code, 0, 1) != '2') {
            return;
        }

        if ($data === false) {
            return; // some kind of error fetching the page
        }

        $analysis = $this->clean_file($data, $url);
        // if ($this->verbose) { echo "<br>Analysis: "; print_r($analysis); }
        $links = $this->getLinks($data, $url, $analysis['base']);
        if ($this->verbose) {
            echo '<br>Links: ';
            print_r($links);
        }

        foreach ($this->findLinkPatterns as $key=>$pattern) {
            foreach ($links as $link=>$shouldFollow) {
                preg_match($pattern, $link, $matches);
                // echo "preg_match($pattern, $link)"; print_r($matches);
                if (@$matches[1] != '') {
                    $this->foundLinkPatterns[$key][$link] = $url;
                }
            }
        }

        if ($depth > 0) {
            $count = 0;
            foreach ($links as $link=>$shouldFollow) {
                if ($shouldFollow && ! in_array($link, $this->visitedLinks)) {
                    $this->spiderURL($link, $depth - 1);
                    $count++;
                    if ($count == $this->maxLinksToFollowPerPage) {
                        break;
                    }
                }
            }
        }
    }

    /* old version, doesn't work when the file contains certain characters
    private function remove_file_from_url($url) {
        $url_parts = $this->parseURL($url);
        if (!$url_parts) return;
        $path = $url_parts['path'];

        $regs = Array ();
        if (preg_match('/([^\/]+)$/i', $path, $regs)) {
            $file = $regs[1];
            $check = $file.'$';
            $path = preg_replace("/".$check."/i", "", $path);
        }

        if ($url_parts['port'] == 80 || $url_parts['port'] == "") {
            $portq = "";
        } else {
            $portq = ":".$url_parts['port'];
        }

        $url = $url_parts['scheme']."://".$url_parts['host'].$portq.$path;
        return $url;
    } */

    private function remove_file_from_url($url)
    {
        $url_parts = $this->parseURL($url);
        if (! isset($url_parts['path'])) {
            return null;
        }
        if (! isset($url_parts['scheme'])) {
            // (not sure yet when or why this happens, see what urls have no scheme and then we'll figure out how to handle it)
            logError("No scheme in '$url'.");

            return null;
        }
        if (! isset($url_parts['host'])) {
            // (not sure yet when or why this happens, see what urls have no host and then we'll figure out how to handle it)
            logError("No host in '$url'.");

            return null;
        }

        $path = $url_parts['path'];

        $regs = [];
        $path = substr($path, 0, 1 + strpos($path, '/'));
        if (@$url_parts['port'] == 80 || @$url_parts['port'] == '') {
            $portq = '';
        } else {
            $portq = ':' . $url_parts['port'];
        }

        $url = $url_parts['scheme'] . '://' . $url_parts['host'] . $portq . $path;

        return $url;
    }

    private function getLinks($data, $url, $base)
    {
        $chunklist = [];

        // The base URL comes from either the meta tag or the current URL.
        if (! empty($base)) {
            $url = $base;
        } /* ?? ignore the url and uses $base instead?? */

        $linkPatterns = [
            "/href\s*=\s*[\'\"]?([+:%\/\?~=&;\\\(\),._a-zA-Z0-9-@]*)(#[.a-zA-Z0-9-]*)?[\'\" ]?(\s*rel\s*=\s*[\'\"]?(nofollow)[\'\"]?)?/i",
            "/(?:frame[^>]*src[[:blank:]]*)=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i",
            "/(?:window[.]location)[[:blank:]]*=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i",
            "/(?:http-equiv=['\"]refresh['\"] *content=['\"][0-9]+;url)[[:blank:]]*=[[:blank:]]*[\'\"]?(([[a-z]{3,5}:\/\/(([.a-zA-Z0-9-])+(:[0-9]+)*))*([+:%\/?=&;\\\(\),._ a-zA-Z0-9-]*))(#[.a-zA-Z0-9-]*)?[\'\" ]?/i",
        ];

        $links = $regs = $checked_urls = [];

        foreach ($linkPatterns as $linkPattern) {
            preg_match_all($linkPattern, $data, $regs, PREG_SET_ORDER);
            foreach ($regs as $val) {
                if (@$checked_urls[$val[1]] == true) {
                    continue;
                } // already did this one
                $checked_urls[$val[1]] = true;
                $linkURL = $this->makeFullURL($val[1], $url);
                if (! (@$val[4] != '' && $this->obeyNoFollow) && $this->shouldFollow($linkURL)) { // if nofollow is not set
                    $links[$linkURL] = true;
                } // this is a url that should be followed
                else {
                    $links[$linkURL] = false;
                } // this is a url that shouldn't be followed
            }
        }

        return $links;
    }

    private function shouldFollow($url)
    {
        $urlparts = $this->parseURL($url);
        if (! $urlparts) {
            if ($this->verbose) {
                echo "[shouldFollow($url) can't parse_url] ";
            }

            return false;
        }

        if (@$urlparts['host'] != '' && strcasecmp($urlparts['host'], $this->mainUrlParts['host']) != 0) {
            if ($this->restrictTo >= $this->RESTRICT_TO['host']) {
                if ($this->verbose) {
                    echo "[shouldFollow($url) -> $urlparts[host] but can't leave host " . $this->mainUrlParts['host'] . '] ';
                }

                return false;
            }
            if ($this->restrictTo >= $this->RESTRICT_TO['domain']) {
                $rootDomain = self::getRootDomain($urlparts['host']);
                if (strcasecmp($rootDomain, $this->mainUrlRootDomain) != 0) {
                    if ($this->verbose) {
                        echo "[shouldFollow($url) -> domain $rootDomain but can't leave $this->mainUrlRootDomain] ";
                    }

                    return false;
                }
            }
        }

        reset($this->ignoreExtensions);
        foreach ($this->ignoreExtensions as $id => $excl) {
            if (preg_match("/\.$excl$/i", $url)) {
                if ($this->verbose) {
                    echo "[shouldFollow($url) -> ignored extension] ";
                }

                return false;
            }
        }

        if (substr($url, -1) == '\\') {
            if ($this->verbose) {
                echo "[shouldFollow($url) -> umm... i think this is impossible] ";
            }

            return false;
        }

        /*
        global $apache_indexes;
        if (isset($urlparts['query'])) {
            if ($apache_indexes[$urlparts['query']]) {
                if ($this->verbose) echo "[shouldFollow($url) -> apache_indexes] ";
                return '';
            }
        }
        */

        if (preg_match("/[\/]?mailto:|[\/]?javascript:|[\/]?news:/i", $url)) {
            if ($this->verbose) {
                echo "[shouldFollow($url) -> mailto/js/news] ";
            }

            return false;
        }

        // only http and https links are followed
        if (isset($urlparts['scheme']) && $urlparts['scheme'] != 'http' && $urlparts['scheme'] != '' && $urlparts['scheme'] != 'https') {
            if ($this->verbose) {
                echo "[shouldFollow($url) -> unknown scheme] ";
            }

            return false;
        }

        if ($this->restrictTo < $this->RESTRICT_TO['directory']) {
            return true;
        }

        // only urls in staying in the starting domain/directory are followed
        $url = $this->convert_url($url);
        if (strpos($url, $this->mainUrlDirectory) !== 0) {
            if ($this->verbose) {
                echo "[url_purify($url) -> /only urls in staying in the starting domain/directory] ";
            }

            return false;
        } else {
            return true;
        }
    }

    // parent url might be used to build an url from relative path

    private function makeFullURL($url, $parent_url)
    {
        $urlparts = $this->parseURL($url);

        if ($parent_url != '') {
            $parent_url = $this->remove_file_from_url($parent_url);
            $parent_url_parts = $this->parseURL($parent_url);

            if (isset($parent_url_parts['scheme']) && substr($url, 0, 1) == '/') {
                $url = @$parent_url_parts['scheme'] . '://' . @$parent_url_parts['host'] . $url;
            } elseif (! isset($urlparts['scheme'])) {
                $url = $parent_url . $url;
            }

            // might have changed the url, so parse it again
            $urlparts = $this->parseURL($url);
        }

        $urlpath = @$urlparts['path'];

        $regs = [];

        while (preg_match("/[^\/]*\/[.]{2}\//", $urlpath, $regs)) {
            $urlpath = str_replace($regs[0], '', $urlpath);
        }

        // remove relative path instructions like ../ etc
        $urlpath = preg_replace("/\/+/", '/', $urlpath);
        $urlpath = preg_replace("/[^\/]*\/[.]{2}/", '', $urlpath);
        $urlpath = str_replace('./', '', $urlpath);
        if ($urlpath == '') {
            $urlpath = '/';
        }
        $query = '';
        if (isset($urlparts['query'])) {
            $query = '?' . $urlparts['query'];
        }
        if (@$urlparts['port'] == 80 || @$urlparts['port'] == '') {
            $portq = '';
        } else {
            $portq = ':' . $urlparts['port'];
        }

        $result = @$urlparts['scheme'] . '://' . @$urlparts['host'] . $portq . $urlpath . $query;

        return $result;
    }

    /* not sure this should be done at all, but could maybe use urldecode() */
    private function convert_url($url)
    {
        $url = str_replace('&amp;', '&', $url);
        $url = str_replace(' ', '%20', $url);

        return $url;
    }

    private function clean_file($data, $url, $type = '')
    {
        $urlparts = $this->parseURL($url);
        $host = $urlparts['host'];
        //remove filename from path
        $path = preg_replace('/([^\/]+)$/i', '', $urlparts['path']);
        $data = preg_replace('/<link rel[^<>]*>/i', ' ', $data);
        $data = preg_replace("@<!--sphider_noindex-->.*?<!--\/sphider_noindex-->@si", ' ', $data);
        $data = preg_replace('@<!--.*?-->@si', ' ', $data);
        $data = preg_replace('@<script[^>]*?>.*?</script>@si', ' ', $data);
        $headdata = $this->getHeadData($data);
        $regs = [];
        if (preg_match("@<title *>(.*?)<\/title*>@si", $data, $regs)) {
            $title = trim($regs[1]);
            $data = str_replace($regs[0], '', $data);
        } elseif ($type == 'pdf' || $type == 'doc') { //the title of a non-html file is its first few words
            $title = substr($data, 0, strrpos(substr($data, 0, 40), ' '));
        } else {
            $title = '';
        }

        $data = preg_replace("@<style[^>]*>.*?<\/style>@si", ' ', $data);

        //create spaces between tags, so that removing tags doesnt concatenate strings
        $data = preg_replace("/<[\w ]+>/", '\\0 ', $data);
        $data = preg_replace("/<\/[\w ]+>/", '\\0 ', $data);
        $data = strip_tags($data);
        $data = preg_replace('/&nbsp;/', ' ', $data);

        $fulltext = $data;
        $data .= ' ' . $title;

        //replace codes with ascii chars
        $data = preg_replace_callback('~&#x([0-9a-f]+);~i', function ($m) {
            return chr(hexdec($m[1]));
        }, $data);
        $data = preg_replace_callback('~&#([0-9]+);~', function ($m) {
            return chr($m[1]);
        }, $data);
        $data = strtolower($data);
        /*
                reset($entities);
                while ($char = each($entities)) {
                    $data = preg_replace("/".$char[0]."/i", $char[1], $data);
                }
        */
        $data = preg_replace('/&[a-z]{1,6};/', ' ', $data);
        $data = preg_replace("/[\*\^\+\?\\\.\[\]\^\$\|\{\)\(\}~!\"\/@#£$%&=`´;><:,]+/", ' ', $data);
        $data = preg_replace("/\s+/", ' ', $data);

        if (@$headdata['base'] != '') {
            $headdata['base'] = $this->makeFullURL($headdata['base'], $url);
        }

        return [
            'fulltext' => addslashes($fulltext),
            'content' => addslashes($data),
            'title' => addslashes($title),
            'description' => @$headdata['description'],
            'keywords' => @$headdata['keywords'],
            'host' => $host,
            'path' => $path,
            'nofollow' => @$headdata['nofollow'],
            'noindex' => @$headdata['noindex'],
            'base' => @$headdata['base'],
        ];
    }

    private function getHeadData($data)
    {
        $headdata = '';

        preg_match("@<head[^>]*>(.*?)<\/head>@si", $data, $regs);

        $headdata = @$regs[1];

        $description = '';
        $robots = '';
        $keywords = '';
        $base = '';
        $res = [];
        $return = [];
        if ($headdata != '') {
            preg_match("/<meta +name *=[\"']?robots[\"']? *content=[\"']?([^<>'\"]+)[\"']?/i", $headdata, $res);
            if ($res) {
                $robots = $res[1];
            }

            preg_match("/<meta +name *=[\"']?description[\"']? *content=[\"']?([^<>'\"]+)[\"']?/i", $headdata, $res);
            if ($res) {
                $description = $res[1];
            }

            preg_match("/<meta +name *=[\"']?keywords[\"']? *content=[\"']?([^<>'\"]+)[\"']?/i", $headdata, $res);
            if ($res) {
                $keywords = $res[1];
            }
            // e.g. <base href="http://www.consil.co.uk/index.php" />
            preg_match("/<base +href *= *[\"']?([^<>'\"]+)[\"']?/i", $headdata, $res);
            if ($res) {
                $base = $res[1];
            }
            $keywords = preg_replace('/[, ]+/', ' ', $keywords);
            $robots = explode(',', strtolower($robots));
            $nofollow = 0;
            $noindex = 0;
            foreach ($robots as $x) {
                if (trim($x) == 'noindex') {
                    $noindex = 1;
                }
                if (trim($x) == 'nofollow') {
                    $nofollow = 1;
                }
            }
            $return['description'] = addslashes($description);
            $return['keywords'] = addslashes($keywords);
            $return['nofollow'] = $nofollow;
            $return['noindex'] = $noindex;
            $return['base'] = $base;
        }

        return $return;
    }

    /* not currently used
    private function checkRobotsDotTxt($host)
    {
        global $user_agent;
        $url = 'http://'.$host."/robots.txt";

        $robot = @file($url);

        if ($robot) {
            $regs = [ ];
            $this_agent = "";
            while(list ($id, $line) = each($robot)) {
                if (preg_match("`^user-agent: *([^#]+) *`i", $line, $regs)) {
                    $this_agent = trim($regs[1]);
                    if ($this_agent == '*' || $this_agent == $user_agent)
                        $check = 1;
                    else
                        $check = 0;
                }

                if (preg_match("/disallow: *([^#]+)/i", $line, $regs) && $check == 1) {
                    $disallow_str = preg_replace("/[\n ]+/i", "", $regs[1]);
                    if (trim($disallow_str) != "") {
                        $omit[] = $disallow_str;
                    } else {
                        if ($this_agent == '*' || $this_agent == $user_agent) {
                            return null;
                        }
                    }
                }
            }
        }

        return $omit;
    }
    */

    private function parseURL($url)
    {
        $result = @parse_url($url);

        // For protocol-relative URLs ("//foo.com"), the scheme may be missing. So we assume it's http.
        if ($result && ! @$result['scheme']) {
            $result['scheme'] = 'http';
        }

        return $result;
    }
}
