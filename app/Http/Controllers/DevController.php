<?php

namespace App\Http\Controllers;

use App;
use Exception;
use Lib\DevSync;

class DevController extends Controller
{
    public function devSync($fileSetName, $remoteCommand = '')
    {
        $devSync = new DevSync();

        $devSync->serverForRemoteCommandsURL = 'https://' . config('custom.publicDynamicDomain');
        $devSync->remoteSyncKey = '68gs.QRRL!fbbgg40@';
        $devSync->view = 'staff/devSync';

        // Set $fileSet

        if (App::environment('local')) {
            // Local Dev
            switch ($fileSetName) {
                case 'local-download':
                    $devSync->fileSet = [
                        'source' => 'server',
                        'destination' => str_replace('\\', '/', base_path()),
                        'revisionHistory' => config('custom.userRoot') . '/hostelz-revision-history',
                        // Can be paths in either the source (for ignored for copying) or the destination (ignored for deleting)...
                        'ignoreDirs' => ['/public/pics', '/storage', '/node_modules', '/bower_components'],
                        'revisionHistoryIgnoreDirs' => ['/vendor', '/public/vendor', '/public/generated-css', '/public/images/generated'],
                    ];

                    break;

                case 'local-upload':
                    $devSync->fileSet = [
                        'source' => str_replace('\\', '/', base_path()),
                        'destination' => 'server',
                        'revisionHistory' => '', // (remote site handles its own revision saving)
                        // Can be paths in either the source (for ignored for copying) or the destination (ignored for deleting)...
                        'ignoreDirs' => ['/public/pics', '/storage', '/node_modules', '/bower_components'],
                        'revisionHistoryIgnoreDirs' => ['/vendor', '/public/vendor', '/public/generated-css', '/public/images/generated'],
                    ];

                    break;

                default:
                    throw new Exception("Unknown file set '$fileSetName'.");
            }
        } else {
            // on the server
            switch ($fileSetName) {
                case 'dev':
                    $devSync->fileSet = [
                        'source' => config('custom.devRoot'),
                        'destination' => config('custom.productionRoot'),
                        'revisionHistory' => config('custom.userRoot') . '/revisions-dev',

                        // Can be paths in either the source (for ignored for copying) or the destination (ignored for deleting)...
                        // dot (.) files are already automatically skipped.
                        //
                        //  ignoreDirsAndFiles !!!
                        'ignoreDirs' => [
                            '/public/pics', '/public/sitemap', '/storage', '/vendor',
                            '/node_modules', '/bower_components', '/public/temp', '/.git', '/.github', '/.gitignore',
                            '/bootstrap/cache' // cache
                            , 'sitemap_index.xml', 'robots.txt', '.shiftrc',
                        ],
                        'ignorePattern' => ['/public/sitemap_'],
                        'revisionHistoryIgnoreDirs' => ['/vendor', '/public/vendor', '/public/generated-css', '/public/images/generated'],
                    ];

                    break;

                case 'remote-command':
                    $devSync->fileSet = [
                        //'source' => $userRoot.'dev',
                        'destination' => config('custom.devRoot'),
                        'revisionHistory' => config('custom.userRoot') . '/localdev-sync-history',
                        'ignoreDirs' => [],
                        'revisionHistoryIgnoreDirs' => ['/vendor', '/public/vendor', '/public/generated-css', '/public/images/generated'],
                    ];

                    break;

                default:
                    throw new Exception("Unknown file set '$fileSetName'.");
            }
        }

        if ($remoteCommand != '') {
            return $devSync->handleRemoteCommand($remoteCommand);
        } else {
            return $devSync->go();
        }
    }

    public function devSyncRemoteCommand($command)
    {
        return $this->devSync('remote-command', $command);
    }

    /* Generate Images */

    public function regenerateGeneratedImages()
    {
        $output = '';

        $output .= "Generate ratings circles.\n";
        self::generateRatingsCircles();

        /*
        $output .= "Generate world map.\n";
        self::generateWorldMap();
        */
        return $output;
    }

    private function generateRatingsCircles(): void
    {
        $sizes = ['small' => 45, 'large' => 65];

        foreach ($sizes as $sizeName => $diameter) {
            $tempSize = 650; // start with really big, then down-scaled so it's anti-aliased
            $tempGap = round(2 * ($tempSize / $diameter));
            $circleCount = 10;
            $startHue = 0.00;
            $endHue = 0.38;
            $saturation = 0.65;
            $value = 0.85;

            /*
                Choose what percent of the total hue range to use for each rating value.
                We also have Listing::scoreAsHue($score), but those hues are more calibrated for imported review percents.
            */

            $hueScale = [0, 0, 10, 20, 30, 40, 50, 60, 70, 100];

            $template = imagecreatefrompng(public_path() . '/images/ratingsCircles-template.png');
            imagesavealpha($template, true);

            $img = imagecreatetruecolor($tempSize, $tempSize * $circleCount);
            imagesavealpha($img, true);
            imagealphablending($img, false);
            $color = imagecolorallocatealpha($img, 255, 255, 255, 127);
            imagefilledrectangle($img, 0, 0, $tempSize, $tempSize * $circleCount, $color);

            for ($i = 0; $i < $circleCount; $i++) {
                // $skewed = round(10 - 10 * log(10-$i)/log(10));
                $skewed = $hueScale[$i];
                $rgb = self::hsvToRgb($startHue + ($endHue - $startHue) * ($skewed / 100), $saturation, $value);
                $color = imagecolorallocatealpha($img, $rgb['R'], $rgb['G'], $rgb['B'], 0);
                imagefilledellipse($img, $tempSize / 2, $tempSize / 2 + $tempSize * $i, $tempSize - $tempGap, $tempSize - $tempGap, $color);

                imagecopymerge($img, $template, 0, $tempSize * $i, 0, 0, 650, 650, 15);
            }
            $finalImg = imagecreatetruecolor($diameter, $diameter * $circleCount);
            imagesavealpha($finalImg, true);
            imagealphablending($finalImg, false);
            imagecopyresampled($finalImg, $img, 0, 0, 0, 0, $diameter, $diameter * $circleCount, $tempSize, $tempSize * $circleCount);

            //imagetruecolortopalette($finalImg, true, 128); (messes up transparency, maybe not the right function?)
            imagepng($finalImg, public_path() . "/images/generated/ratingsCircles-$sizeName.png");
        }
    }

    /* not yet converted from old site code
    private function generateWorldMap()
    {
        $filename = DOC_ROOT.'images/generated/worldMap.png';
        $cssFilename = USER_ROOT.'dev/templates/css/worldMap.css';

        $mapWidth = 300; // 260;
        $mapHeight = 150; // 130;

        $backgroundColor = false; // hexdec($COLORS[2]['background'][0])
        $landColor = hexdec($COLORS[2]['background'][3]); // hexdec('222222'); // hexdec($COLORS[2]['background'][2]);
        $outlineColor = hexdec('0000001'); // hexdec($COLORS[2]['border'][0]); // hexdec('AAAAAA'); // false; // (transparent)
        $landSelectedColor = hexdec($COLORS[2]['background'][2]); // hexdec($COLORS[2]['background'][3]);

        $continents = dbGetCol("SELECT continent FROM countries WHERE continent!='' AND cityCount > 0 GROUP BY continent");
        $continents = array_merge(array(''), $continents); // add empty continent

        $combinedImage = imagecreatetruecolor($mapWidth, $mapHeight*count($continents));

        if(!$backgroundColor) {
            imagesavealpha($combinedImage, true);
            imagealphablending($combinedImage, false);
            $transColor = imagecolorallocatealpha($combinedImage, 0,0,200, 127);
            imagefilledrectangle($combinedImage, 0,0, $mapWidth,$mapHeight*count($continents), $transColor);
        }

        $fillBoxes = [ ];
        foreach ($continents as $continentNum=>$continent) {
        	$data = $continent=='' ? [ ] : dbGetAll("SELECT latitude,longitude FROM cityInfo WHERE totalListingCount>0 AND (latitude!=0 OR longitude!=0) AND continent=".dbQuote($continent));
        	$return = generateWorldMap($data, $landSelectedColor, false, 1, false,0, $mapWidth,$mapHeight, false, $landColor, $backgroundColor, $outlineColor, true);
        	if($return['imageObject']) {
        		imagecopy($combinedImage, $return['imageObject'], 0,$continentNum*$mapHeight, 0,0, $mapWidth,$mapHeight);
        		imagedestroy($return['imageObject']);
                $return['fillBox']['continentCode'] = makeContinentCode($continent);
            	$return['fillBox']['continent'] = $continent;
        		$fillBoxes[$continentNum] = $return['fillBox'];
        	}
        }

        if($combinedImage) {
        	//imagetruecolortopalette($combinedImage, true, 256);
            //if($backgroundColor === false) imagecolorset($combinedImage, 0, 0,0,0, 127);
            echo $filename;
        	imagepng($combinedImage, $filename);
        }

        $smarty->assign('boxes', $fillBoxes);
        $smarty->assign('mapHeight', $mapHeight);
        $css = $smarty->fetch('worldMap.css');
        file_put_contents($cssFilename, '{* generated by generateWorldMap.php! *}{literal}'.$css.'{/literal}');
    }
    */

    // Returns RGB array with values ranging from 0-255.

    private function hsvToRgb($h, $s, $v) // HSV Values:Number 0-1
    {
        $rgb = [];

        if ($s == 0) {
            $r = $g = $b = $v * 255;
        } else {
            $var_H = $h * 6;
            $var_i = floor($var_H);
            $var_1 = $v * (1 - $s);
            $var_2 = $v * (1 - $s * ($var_H - $var_i));
            $var_3 = $v * (1 - $s * (1 - ($var_H - $var_i)));

            if ($var_i == 0) {
                $var_R = $v;
                $var_G = $var_3;
                $var_B = $var_1;
            } elseif ($var_i == 1) {
                $var_R = $var_2;
                $var_G = $v;
                $var_B = $var_1;
            } elseif ($var_i == 2) {
                $var_R = $var_1;
                $var_G = $v;
                $var_B = $var_3;
            } elseif ($var_i == 3) {
                $var_R = $var_1;
                $var_G = $var_2;
                $var_B = $v;
            } elseif ($var_i == 4) {
                $var_R = $var_3;
                $var_G = $var_1;
                $var_B = $v;
            } else {
                $var_R = $v;
                $var_G = $var_1;
                $var_B = $var_2;
            }

            $r = $var_R * 255;
            $g = $var_G * 255;
            $b = $var_B * 255;
        }

        $rgb['R'] = $r;
        $rgb['G'] = $g;
        $rgb['B'] = $b;

        return $rgb;
    }
}
