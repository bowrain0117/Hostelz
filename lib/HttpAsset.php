<?php

namespace Lib;

/*
Manage HTML assets (CSS, Javascript).  Lets views declare what they require, then only include that asset once, with dependencies, in the right order.

Vaguely based on Orchestra\Asset, but that one just lets you declare the assets and then output all of them.
This class lets us declare assets, and then declare in a view what assets it depends on.
*/

use Exception;

class HttpAsset
{
    private static $version;

    private static $registeredAssets = [];

    private static $requiredAssets = [];

    /*
    static public function register($name, $path, $dependencies = false, $type = null)
    {
        self::$registeredAssets[$name] = [
            'path' => $path,
            'dependencies' => $dependencies,
            'type' => $type === null ? pathinfo($path, PATHINFO_EXTENSION) : $type
        ];
    }
    */

    public static function loadRegisteredAssets()
    {
        $config = require base_path() . '/resources/httpAssets.php';
        self::$registeredAssets = $config['assets'];
        self::$version = $config['assetsVersion'];
    }

    // The purpose of ignored is mostly to avoid circular dependencies (which we detect this automatically),
    // but the calling user may also want to use it to ignore certain dependencies for whatever reason.

    public static function requireAsset($name, $ignoredDependencies = [])
    {
        if (! self::$registeredAssets) {
            self::loadRegisteredAssets();
        }

        if (empty(self::$registeredAssets[$name])) {
            throw new Exception("Asset '$name' not registered.");
        }

        if (in_array($name, self::$requiredAssets)) {
            return;
        } // already requiring this asset

        $ignoredDependencies[] = $name; // add this to the ignored dependencies to avoid circular dendency

        if (! empty(self::$registeredAssets[$name]['dependencies'])) {
            foreach (self::$registeredAssets[$name]['dependencies'] as $dependency) {
                if (in_array($dependency, $ignoredDependencies)) {
                    continue;
                }
                self::requireAsset($dependency, $ignoredDependencies);
            }
        }

        self::$requiredAssets[] = $name;
    }

    public static function output($type, $location = '')
    {
        if ($location === '') {
            if ($type === 'css') {
                $location = 'header';
            }

            if ($type === 'js') {
                $location = 'bottom';
            }
        }

        $output = '';

        foreach (self::$requiredAssets as $name) {
            $asset = self::$registeredAssets[$name];
            $versionString = (self::$version ? '?' . self::$version : '');

            $assetType = (@$asset['type'] !== null ? $asset['type'] : pathinfo($asset['path'], PATHINFO_EXTENSION));
            if ($assetType != $type) {
                continue;
            }

            $assetLocation = self::getAssetLocation($asset, $assetType);
            if ($assetLocation !== $location) {
                continue;
            }

            switch ($assetType) {
                case 'css':
                    $output .= '<link rel="stylesheet" href="' . $asset['path'] . $versionString . '">';
                    break;

                case 'js':
                    $module = isset($asset['is_module']) && $asset['is_module'] ? ' type="module" ' : '';

                    $output .= '<script src="' . $asset['path'] . $versionString . '" ' . $module . '></script>';
                    break;

                default:
                    throw new Exception("Unknown asset type '$assetType'.");
            }
        }

        return $output;
    }

    private static function getAssetLocation($asset, $assetType)
    {
        if (! empty($asset['location'])) {
            return $asset['location'];
        }

        if ($assetType === 'css') {
            return 'header';
        }

        //  for js and other assets
        return 'bottom';
    }
}
