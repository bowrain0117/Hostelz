<?php

namespace Lib;

use App;
use Artisan;
use Exception;
use File;
use Lib\PageCache;
use Request;
use URL;
use View;

class DevSync
{
    public $fileSet;

    public $serverForRemoteCommandsURL;

    public $remoteSyncKey;

    public $view;

    public $dryRun = false;

    public function go()
    {
        switch (Request::input('mode')) {
            case 'doSync':
                return $this->doSync(Request::input('copyList'), Request::input('deleteList'));

            default:
                return $this->showFiles();
        }
    }

    private function showFiles()
    {
        set_time_limit(15 * 60);

        // Get Files

        $sourceList = $this->fileSet['source'] == 'server' ?
            $this->sendRemoteCommand('buildFileList', ['ignoreDirs' => serialize($this->fileSet['ignoreDirs'])]) :
            $this->buildFileList($this->fileSet['source'], $this->fileSet);

        if ($sourceList === false) {
            throw new Exception("Couldn't get sourceList.");
        }

        $destList = $this->fileSet['destination'] == 'server' ?
            $this->sendRemoteCommand('buildFileList', ['ignoreDirs' => serialize($this->fileSet['ignoreDirs'])]) :
            $this->buildFileList($this->fileSet['destination'], $this->fileSet);

        if ($destList === false) {
            throw new Exception("Couldn't get destList.");
        }

        // Compare

        $copyList = [];
        foreach ($sourceList as $sourceFileInfo) {
            $destFileInfo = $this->findFileInList($sourceFileInfo['path'], $sourceFileInfo['filename'], $destList);

            if (! $sourceFileInfo['modTime']) {
                continue;
            }

            if (! $destFileInfo || $sourceFileInfo['modTime'] > $destFileInfo['modTime'] + 1) { // The +1 is because apparently when we zip it and move it to the local PC and then re-zip it and re-upload it, the unmodified files end up with a modified time that is one second ahead of what it originally was.
                /* (this was just a temporary thing to see if it would make sense to compare file contents before adding files to the copy list.  but
                this wouldn't work for remote source/destination, and instead we do a contents check just for local files when we actually do the copy [or at least before saving the history])
                */
                if (
                    $destFileInfo !== false &&
                    file_exists($this->fileSet['destination'] . "{$destFileInfo['path']}/{$destFileInfo['filename']}") &&
                    (md5_file($this->fileSet['source'] . "{$sourceFileInfo['path']}/{$sourceFileInfo['filename']}") == md5_file($this->fileSet['destination'] . "$destFileInfo[path]/$destFileInfo[filename]"))) {
                    continue;
                }
                $copyList[] = $sourceFileInfo;
            }
            /* elseif ($sourceFileInfo['modTime'] < $destFileInfo['modTime'])
                    echo "($this->fileSet[destination]$file newer than source!)<br>"; */
        }

        $deleteList = [];
        foreach ($destList as $destFileInfo) {
            $sourceFileInfo = $this->findFileInList($destFileInfo['path'], $destFileInfo['filename'], $sourceList);
            if (! $sourceFileInfo) {
                $deleteList[] = $destFileInfo;
            }
        }

        // Make View

        return view($this->view, compact('copyList', 'deleteList'))->with('mode', 'showFiles')->with('fileSet', $this->fileSet);
    }

    private function doSync($copyList, $deleteList)
    {
        // Handle Execution of Copy / Delete

        set_time_limit(30 * 60);

        if ($copyList) {
            foreach ($copyList as $key => $fileInfo) {
                $fileInfo = $copyList[$key] = unserialize($fileInfo);
                $this->doCopy($this->fileSet['source'], $this->fileSet['destination'], $fileInfo['path'], $fileInfo['filename'], $fileInfo['modTime']);
            }
        }

        if ($deleteList) {
            foreach ($deleteList as $key => $fileInfo) {
                $fileInfo = $deleteList[$key] = unserialize($fileInfo);
                $this->doDelete($this->fileSet['destination'], $fileInfo['path'], $fileInfo['filename'], $fileInfo['modTime']);
            }
        }

        // We have to output the page first before clearing everything, or else it won't output anything...

        echo view($this->view, compact('copyList', 'deleteList'))->with('mode', 'doSync')->with('fileSet', $this->fileSet);

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        Artisan::call('view:clear'); // Laravel's view cache
        Artisan::call('optimize:clear');
        PageCache::clearAll(); // our PageCache

        $commandOutput = '';

        if (App::environment() == 'production') {
            $commandOutput = shell_exec(
                'cd ' . base_path() . ';' .
                'composer dumpautoload --no-dev 2>&1'
                /*
                (was going to have it run "gulp --production", but can't unless we sync the node_modules and bower_components,
                which slows down syncing.  so we just assume gulp is run as --production on the dev server before syncing)
                'export HOME=/home/hostelz/dev/storage/gulpTemp && export DISABLE_NOTIFIER=true && gulp --production 2>&1'
                */
            );

            $commandOutput .= "\nNote: NOT running 'gulp --production' -- assuming Gulp was already run in production mode!\n";

            /*
            (our routes can't currently be cached)
            Artisan::call('route:cache');
            $commandOutput .= "\n".Artisan::output();
            */

//            Artisan::call('optimize');
//            $commandOutput .= "\n".Artisan::output();
//
//            Artisan::call('view:cache');
//            $commandOutput .= "\n".Artisan::output();
        }

        return $commandOutput;
    }

    // Private Functions

    private function buildFileList($rootPath, $options, $path = '', &$list = [])
    {
        if (in_array($path, $options['ignoreDirs'])) {
            return;
        }

        $files = scandir($rootPath . $path);
        if (! $files) {
            throw new Exception("$rootPath$path not a directory.");
        }

        $dirHasRealFiles = false;
        foreach ($files as $filename) {
            //  ignore files
            if (in_array($filename, $options['ignoreDirs'])) {
                continue;
            }

            // if (substr($filename, 0, 1) == '.') continue; // ignore all dot files
            if ($filename == '.' || $filename == '..' || $filename == '.env') {
                continue;
            }
            $dirHasRealFiles = true;
            $file = $rootPath . $path . '/' . $filename;

            //  ignore patterns
            if (isset($options['ignorePattern']) && str_replace($options['ignorePattern'], '', $file) !== $file) {
                continue;
            }

            if (is_link($file)) {
                continue;
            } // ignore links
            if (is_dir($file)) {
                $this->buildFileList($rootPath, $options, $path . '/' . $filename, $list);
                continue;
            }

            $modTime = filemtime($file);
            $list[] = [
                'filename' => $filename,
                'path' => $path,
                'modTime' => $modTime,
            ];
        }

        if (! $dirHasRealFiles) {
            $list[] = ['path' => $path, 'filename' => '', 'modTime' => ''];
        } // record that there's an empty directory (no filename/modTime)

        return $list;
    }

    private function findFileInList($path, $filename, $list)
    {
        foreach ($list as $l) {
            if ($filename == '') { // match an empty dir
                if ($l['path'] == $path) {
                    return $l;
                }
            } else {
                if (($path . '/' . $filename) == ($l['path'] . '/' . $l['filename'])) {
                    return $l;
                }
            }
        }

        return false;
    }

    private function doCopy($sourcePath, $destPath, $path, $filename, $modTime)
    {
        $file = $path . '/' . $filename;

        if ($sourcePath == 'server') {
            if (! file_exists($destPath . $path)) {
                if ($this->dryRun) {
                    echo "mkdir($destPath$path) ";
                } else {
                    mkdir($destPath . $path, 0770, true);
                }
            }
            if ($filename != '') {
                $data = $this->sendRemoteCommand('get', ['file'=>$file]);
                if ($data === false) {
                    throw new Exception("Couldn't get remote '$file'.");
                }
                if ($this->dryRun) {
                    echo 'Save ' . strlen($data) . " bytes to $destPath$file. ";
                } else {
                    $result = file_put_contents($destPath . $file, $data);
                    if ($result === false) {
                        throw new Exception("Couldn't write to file.");
                    }
                    $touch = touch($destPath . $file, $modTime);
                    if ($result === false) {
                        throw new Exception("Couldn't touch file.");
                    }
                    $this->saveToRevisionHistory($path, $filename, $data, $modTime);
                }
            }
        } elseif ($destPath == 'server') {
            if ($filename != '') {
                $data = file_get_contents($sourcePath . $file);
                if ($data === false) {
                    throw new Exception("Couldn't get '$sourcePath$file'.");
                }
            }
            if ($this->dryRun) {
                if ($filename == '') {
                    echo '(empty dir) ';
                } else {
                    echo 'Put ' . strlen($data) . ' bytes. ';
                }
            } else {
                $this->sendRemoteCommand('put', ['path'=>$path, 'filename'=>$filename, 'data'=>$data, 'modTime'=>$modTime]);
            }
        } else {
            // (Local copy)

            if (! file_exists($destPath . $path)) {
                if ($this->dryRun) {
                    echo "mkdir($destPath$path) ";
                } else {
                    mkdir($destPath . $path, 0770, true);
                }
            }

            if ($filename != '') {
                if ($this->dryRun) {
                    echo "copy($sourcePath$file, $destPath$file) ";
                    echo "touch($destPath$file, $modTime) ";
                } else {
                    // Check to see if the files are really different
                    $filesAreDifferent = ! file_exists($destPath . $file) || (md5_file($sourcePath . $file) != md5_file($destPath . $file));

                    if ($filesAreDifferent) {
                        $result = copy($sourcePath . $file, $destPath . $file);
                        if ($result === false) {
                            throw new Exception("Couldn't copy file.");
                        }
                    }
                    $result = touch($destPath . $file, $modTime);
                    if ($result === false) {
                        throw new Exception("Couldn't touch file.");
                    }
                    if ($filesAreDifferent) {
                        $this->saveToRevisionHistory($path, $filename, file_get_contents($sourcePath . $file), $modTime);
                    }
                }
            }
        }
    }

    private function doDelete($destPath, $path, $filename, $modTime)
    {
        $file = $path . '/' . $filename;

        if ($destPath == 'server') {
            if ($this->dryRun) {
                echo "sendRemoteCommand('delete') ";
            } else {
                $this->sendRemoteCommand('delete', ['path'=>$path, 'filename'=>$filename]);
            }
        } else {
            // (Local delete)
            if ($this->dryRun) {
                echo "delete $destPath$path. ";
            } else {
                if ($filename == '') {
                    if (! rmdir($destPath . $path)) {
                        throw new Exception("Couldn't delete $destPath$path.");
                    }
                } else {
                    $this->saveToRevisionHistory($path, $filename, file_get_contents($destPath . $file), null);
                    if (! unlink($destPath . $file)) {
                        throw new Exception("Couldn't delete '$destPath$file'.");
                    }
                }
            }
        }
    }

    private function sendRemoteCommand($command, $params)
    {
        static $curl = null;

        if (! $curl) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,

                //CURLOPT_HTTPHEADER => [ 'Content-Type: application/xml' ],
                //CURLOPT_HEADER => 1,
                //CURLOPT_USERPWD => "dev:ddeevv",
            ]);
        }
        curl_setopt($curl, CURLOPT_URL, $this->serverForRemoteCommandsURL . routeURL('devSync-remote-command', [$command], 'relative'));
        $params['key'] = $this->remoteSyncKey;
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        $response = curl_exec($curl);

        if ($response == '') {
            echo 'No remote data.';

            return false;
        }

        $decodedResponse = @unserialize($response);
        if ($decodedResponse === false) {
            echo "Couldn't decode data.";
            print_r($response);

            return false;
        }

        if ($decodedResponse['status'] != 'ok') {
            print_r($decodedResponse);

            return false;
        }

        return array_key_exists('data', $decodedResponse) ? $decodedResponse['data'] : true;
    }

    public function handleRemoteCommand($command)
    {
        set_time_limit(15 * 60);

        // if (App::environment() != 'production') throw new Exception("Only the production server can handle remote commands.");
        if (Request::input('key') != $this->remoteSyncKey) {
            throw new Exception('Invalid key.');
        }

        $rootPath = $this->fileSet['destination'];

        switch ($command) {
            case 'buildFileList':
                set_time_limit(5 * 60);

                $ignoreDirs = unserialize(Request::input('ignoreDirs'));
                $list = $this->buildFileList($rootPath, ['ignoreDirs' => $ignoreDirs]);

                return serialize(['status' => 'ok', 'data' => $list]);

            case 'put':
                set_time_limit(30 * 60);

                $file = Request::input('path') . '/' . Request::input('filename');
                if (! file_exists($rootPath . Request::input('path'))) {
                    mkdir($rootPath . Request::input('path'), 0770, true);
                }
                $result = file_put_contents($rootPath . $file, Request::input('data'));
                if ($result === false) {
                    throw new Exception("Couldn't write to file.");
                }
                $result = touch($rootPath . $file, Request::input('modTime'));
                if ($result === false) {
                    throw new Exception("Couldn't touch file.");
                }
                $this->saveToRevisionHistory(Request::input('path'), Request::input('filename'), Request::input('data'), Request::input('modTime'));

                return serialize(['status'=>'ok']);

            case 'get':
                set_time_limit(30 * 60);

                if (Request::input('file') == '') {
                    throw new Exception('No file.');
                }
                $data = file_get_contents($rootPath . Request::input('file'));
                if ($data === false) {
                    return serialize(['status'=>'error', 'data'=>"Couldn't get '" . Request::input('file') . "'."]);
                } else {
                    return serialize(['status'=>'ok', 'data'=>$data]);
                }

            case 'delete':
                if (Request::input('filename') == '') {
                    rmdir($rootPath . Request::input('path'));
                } else {
                    $filePath = Request::input('path') . '/' . Request::input('filename');
                    $data = file_get_contents($rootPath . $filePath);
                    if ($data === false) {
                        throw new Exception('Original file not found.');
                    }
                    $this->saveToRevisionHistory(Request::input('path'), Request::input('filename'), $data, null);
                    $result = unlink($rootPath . $filePath);
                    if ($result === false) {
                        throw new Exception("Couldn't delete file.");
                    }
                }

                return serialize(['status'=>'ok']);

            default:
                throw new Exception("Unknown command '$command'.");
        }
    }

    /*
        $modifiedTime - Pass null if the file was deleted.
    */

    private function saveToRevisionHistory($relativePath, $file, $data, $modifiedTime = null)
    {
        $revisionPath = $this->fileSet['revisionHistory'];
        if (! $revisionPath) {
            return;
        }
        foreach ($this->fileSet['revisionHistoryIgnoreDirs'] as $ignoreDir) {
            if (strpos($relativePath, $ignoreDir) === 0) {
                continue;
            }
        }
        if (! file_exists($revisionPath . $relativePath)) {
            mkdir($revisionPath . $relativePath, 0770, true);
        }
        $result = file_put_contents($revisionPath . $relativePath . '/' . $file . ($modifiedTime ? '-' . date('Y-m-d', $modifiedTime) : '-deleted'), $data);
        if ($result === false) {
            throw new Exception("Couldn't write to revision file.");
        }
    }
}
