<?php

namespace App\Http\Controllers;

use Cache;
use Exception;
use Request;

/*
based on http://neutronide.com/ (v12.03)

https://github.com/ajaxorg/ace/wiki/Embedding---API
https://github.com/ajaxorg/ace/wiki/Default-Keyboard-Shortcuts
http://groups.google.com/group/ace-discuss
*/

class CodeEditorController extends Controller
{
    private $fileSets;

    private function setFileSets(): void
    {
        // This is defined in a class method so we can use functions like config('custom.devRoot')
        $this->fileSets = [
            [
                'label' => 'hostelz dev',
                'path' => config('custom.devRoot'), // note: vendor is excluded (search totalcommercial laravel/vendor to search Laravel stuff).
                'grepByDefault' => true,
                'quickLinkPaths' => [
                    '/' => config('custom.devRoot'), 'app' => config('custom.devRoot') . '/app', 'lib' => config('custom.devRoot') . '/lib', 'views' => config('custom.devRoot') . '/resources/views', 'lang' => config('custom.devRoot') . '/resources/lang/en', 'controllers' => config('custom.devRoot') . '/app/Http/Controllers', 'public' => config('custom.devRoot') . '/public', 'secure' => config('custom.devRoot') . '/public-secure',
                ],
                'grepIgnorePaths' => [
                    config('custom.devRoot') . '/public/vendor', config('custom.devRoot') . '/public/generated-css', config('custom.devRoot') . '/public-secure/admin/temp', config('custom.devRoot') . '/node_modules', config('custom.devRoot') . '/bower_components', config('custom.devRoot') . '/vendor', config('custom.devRoot') . '/storage', config('custom.devRoot') . '/public/codeEditor',
                ],
            ],
            [
                'label' => 'hostelz production',
                'path' => config('custom.productionRoot'), // note: vendor is excluded (search totalcommercial laravel/vendor to search Laravel stuff).
                'grepByDefault' => false,
                'quickLinkPaths' => [
                    '/' => config('custom.productionRoot') . '/', 'app' => config('custom.productionRoot') . '/app', 'public' => config('custom.productionRoot') . '/public', 'secure' => config('custom.productionRoot') . '/public-secure',
                ],
                'grepIgnorePaths' => [
                    config('custom.productionRoot') . '/app/storage', config('custom.productionRoot') . '/public/pics',
                ],
            ],
            [
                'label' => 'hostelz + vendor',
                'path' => config('custom.devRoot'),
                'grepByDefault' => false,
                'grepIgnorePaths' => [config('custom.devRoot') . '/storage', config('custom.devRoot') . '/node_modules'],
            ],
        ];
    }

    public function codeEditor()
    {
        $this->setFileSets();

        switch (Request::input('cmd')) {
            case '': // show the code editor
                // Load saved session(s)
                $savedSession = Cache::get('codeEditor:session:previous');

                if ($savedSession != null) {
                    $savedSession = unserialize($savedSession);
                }

                //dd($savedSession);
                return view('staff/codeEditor', compact('savedSession'))->with('fileSets', $this->fileSets);

            case 'fileTree':
                return $this->fileTreeCommand();

            case 'fileSave':
                return $this->fileSaveCommand();

            case 'fileGet':
                return $this->fileGetCommand();

            case 'new':
                return $this->newCommand();

            case 'delete':
                return $this->deleteCommand();

            case 'rename':
                return $this->renameCommand();

            case 'saveSession':
                return $this->saveSessionCommand();

            case 'tempUpload':
                return $this->tempUploadCommand();

            case 'grep':
                return $this->grepCommand();

                /* (from the old code) Not sure this ever worked?
    	    case 'terminal':
                    list($resource,$host,$origin) = getheaders($buffer);
                    $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                               "Upgrade: WebSocket\r\n" .
                               "Connection: Upgrade\r\n" .
                               "WebSocket-Origin: " . $origin . "\r\n" .
                               "WebSocket-Location: ws://" . $host . $resource . "\r\n" .
                               "\r\n";
                    $handshake = true;
                    socket_write($socket,$upgrade.chr(0),strlen($upgrade.chr(0)));
                    exit();
                break;
                */
        }
    }

    private function fileSaveCommand()
    {
        $path = Request::input('path');
        $result = $path ? file_put_contents($path, Request::input('contents')) : false;
        if ($result) {
            $this->recentFiles($path);
        }

        return [
            'uid' => md5($path),
            'path' => $path,
            'result' => $result !== false ? 'good' : 'bad',
            'error' => null,
        ];
    }

    private function saveSessionCommand(): void
    {
        Cache::forever(
            'codeEditor:session:previous',
            serialize(explode("\n", trim(Request::input('files'))))
        );
    }

    private function tempUploadCommand()
    {
        $tempName = tempnam('/tmp/', 'codeEdit-upload-');
        $result = (file_put_contents($tempName, file_get_contents('php://input')) != false);

        header('Content-type: application/json');

        return [
            'message' => $tempName,
            'result' => $result,
        ];
    }

    private function grepCommand()
    {
        set_time_limit(7 * 60 * 60);

        $options = Request::input('options');

        $results = [];
        foreach (Request::input('filesets') as $fileset) {
            if (in_array('filenameSearch', $options)) {
                $result = $this->filenameSearch(Request::input('needle'), $this->fileSets[$fileset]['path'], $this->fileSets[$fileset]['path'], $options, $this->fileSets[$fileset]['grepIgnorePaths']);
            } else {
                $result = $this->grepSearch(Request::input('needle'), $this->fileSets[$fileset]['path'], $options, $this->fileSets[$fileset]['grepIgnorePaths']);
            }
            $results = array_merge($results, $result);
        }

        return view('staff/codeEditor-grep', compact('results', 'options'));
    }

    private function deleteCommand()
    {
        $dir = Request::input('dir');

        if (is_dir($dir)) {
            $result = rmdir($dir);
        } else {
            $result = unlink($dir);
        }

        header('Content-type: application/json');

        return [
            'message' => md5($dir),
            'result' => $result,
        ];
    }

    private function renameCommand()
    {
        $result = rename(Request::input('dir'), Request::input('name'));

        header('Content-type: application/json');

        return [
            'message' => md5(Request::input('dir') . Request::input('name')),
            'result' => $result,
        ];
    }

    private function newCommand()
    {
        $name = Request::input('name');
        $dir = Request::input('dir');

        switch (Request::input('new_type')) {
            case 'file':
                if ($name == '' || $dir == '') {
                    $result = false;
                } else {
                    $result = (file_put_contents("$dir/$name", '') != false);
                }

                break;

            case 'url':
                if ($name == '' || $dir == '') {
                    $result = false;
                } elseif (($data = file_get_contents($name)) == '') {
                    $result = false;
                } else {
                    $result = (file_put_contents("$dir/_DOWNLOADED_FILE_", $data) != false);
                }

                break;

            case 'up':
                if (Request::input('temp_file') == '' || $name == '' || $dir == '') {
                    $result = false;
                } else {
                    $result = rename(Request::input('temp_file'), $dir . '/' . $name);
                }

                break;

            case 'dir':
                if ($name == '' || $dir == '') {
                    $result = false;
                } else {
                    $result = mkdir("$dir/$name");
                }

                break;

            default:
                throw new Exception('Unknown type.');
        }

        header('Content-type: application/json');

        return [
            // 'message' => md5($message),
            'result' => $result,
        ];
    }

    private function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === '' || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }

    private function fileGetCommand()
    {
        $file = Request::input('f');
        $pathParts = pathinfo($file);

        $modes = ['php' => 'php', 'inc' => 'php', 'css' => 'css', 'sass' => 'css', 'scss' => 'css', 'less' => 'css', 'html' => 'html', 'js' => 'javascript'];
        $extension = $pathParts['extension'] ?? null;
        $mode = $modes[$extension] ?? null;

        if ($extension == 'php' && $this->endsWith($file, '.blade.php')) {
            // Handle blade files, which may be css, js, html
            if ($this->endsWith($pathParts['dirname'], 'js')) {
                $mode = 'javascript';
            } elseif ($this->endsWith($pathParts['dirname'], 'css')) {
                $mode = 'css';
            } else {
                $mode = 'html';
            }
        }
        if ($mode == '') {
            $mode = 'text';
        }

        $data = $file ? file_get_contents($file) : false;
        if ($data === false) {
            return null;
        }
        $this->recentFiles($file);

        header('Content-type: application/json');

        return [
            'path' => $file,
            'filename' => $pathParts['basename'],
            'fileType' => 'text',
            'data' => $data,
            'mode' => $mode,
            'uid' => md5($file),
        ];
    }

    private function fileTreeCommand()
    {
        $dir = Request::input('dir');

        $outputList = [];

        if ($dir == '') {
            $outputList = [
                ['name' => 'Paths', 'type' => 'title'],
            ];

            foreach ($this->fileSets as $fileSet) {
                $outputList[] = ['name' => $fileSet['label'], 'path' => $fileSet['path'], 'type' => 'fileGroup'];
            }
            $recent = $this->recentFiles();
            if ($recent) {
                $outputList[] = ['name' => 'Recent', 'type' => 'title'];
                $outputList = array_merge($outputList, $recent);
            }
        } else {
            $workingPath = $dir;
            $outputList = [];

            $topItems = [
                ['name' => 'Paths', 'type' => 'fileGroup', 'path' => ''],
            ];

            // quickLinkPaths
            foreach ($this->fileSets as $fileSet) {
                if (! isset($fileSet['quickLinkPaths'])) {
                    continue;
                }
                if (strpos($workingPath, $fileSet['path']) !== 0) {
                    continue;
                }

                $topItems[] = ['name' => $fileSet['label'], 'type' => 'title'];
                foreach ($fileSet['quickLinkPaths'] as $name => $path) {
                    $topItems[] = ['name' => $name, 'type' => 'quickLinks', 'path' => $path];
                }
            }

            $topItems[] = ['name' => $workingPath, 'type' => 'title'];

            if ($dir != '') {
                $outputList[] = ['name' => '..', 'type' => 'dir', 'path' => dirname($dir), 'workingPath' => $workingPath];
            }

            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..' || is_link($dir . '/' . $file)) {
                    continue;
                } // ignore links

                if (is_dir($dir . '/' . $file)) {
                    $outputList[] = ['name' => $file, 'path' => $dir . '/' . $file, 'type' => 'dir', 'workingPath' => $workingPath];
                } else {
                    $outputList[] = ['name' => $file, 'path' => $dir . '/' . $file, 'type' => 'file', 'workingPath' => $workingPath];
                }
            }
            usort($outputList, function ($a, $b) {
                return $this->dirSortCmp($a, $b);
            });

            // *after* sorting the other items, add the special top items
            foreach (array_reverse($topItems) as $item) {
                array_unshift($outputList, $item);
            }
        }

        return view('staff/codeEditor-files', compact('outputList'));
    }

    // options: sensitive, regex, followLinks
    private function grepSearch($needle, $path, $options, $grepIgnorePaths, &$results = false)
    {
        $MAX_MATCHES_PER_FILE = 500;
        $MAX_RESULTS = 3000;
        $MAX_LINE_LENGTH = 200;
        $MAX_FILE_SIZE = 2 * 1000 * 1000;
        $MATCH_START = "\tMATCH_START\t";
        $MATCH_END = "\tMATCH_END\t";

        if (in_array($path, $grepIgnorePaths)) {
            return $results;
        }

        if ($results === false) {
            $results = [];
        } // start with empty results

        if ($options == '') {
            $options = [];
        }

        $files = scandir($path);
        if (! $files) {
            echo "$path not a directory.";
            exit();
        }

        foreach ($files as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }

            $file = $path . '/' . $filename;

            if (is_link($file) && ! in_array('followLinks', $options)) {
                continue;
            }
            if (is_dir($file)) {
                $this->grepSearch($needle, $file, $options, $grepIgnorePaths, $results);

                continue;
            }

            $size = filesize($file);
            if ($size <= $MAX_FILE_SIZE) {
                $lines = file($file, FILE_IGNORE_NEW_LINES);
                $matchedLines = [];
                foreach ($lines as $lineNum => $line) {
                    if (in_array('regex', $options)) {
                        $c = preg_match('`' . $needle . '`' . (in_array('sensitive', $options) ? '' : 'i'), $line, $matches, PREG_OFFSET_CAPTURE);
                        if ($c) {
                            $resultPos = $matches[0][1];
                            $resultString = $matches[0][0];
                        } else {
                            $resultPos = false;
                        }
                    } else {
                        if (in_array('sensitive', $options)) {
                            $resultPos = strpos($line, $needle);
                        } else {
                            $resultPos = stripos($line, $needle);
                        }

                        if ($resultPos !== false) {
                            $resultString = substr($line, $resultPos, strlen($needle));
                        }
                    }
                    if ($resultPos !== false) {
                        $resultLine = substr($line, 0, $resultPos) . $MATCH_START . $resultString . $MATCH_END . substr($line, $resultPos + strlen($resultString));
                        if (strlen($resultLine) > $MAX_LINE_LENGTH) {
                            $resultLine = substr($resultLine, 0, $MAX_LINE_LENGTH);
                        }
                        $matchedLines[] = ['type' => 'text', 'text' => $resultLine, 'path' => $file,
                            'startLine' => $lineNum, 'startCol' => $resultPos, 'endLine' => $lineNum, 'endCol' => $resultPos + strlen($resultString), ];
                        if (count($matchedLines) >= $MAX_MATCHES_PER_FILE) {
                            break;
                        }
                    }
                }
                if ($matchedLines) {
                    $results[] = ['type' => 'file', 'path' => $file, 'parentDir' => $path];
                    $results = array_merge($results, $matchedLines);
                }
                if (count($results) > $MAX_RESULTS) {
                    echo 'MAX RESULTS LIMIT REACHED!<p>';

                    return $results;
                }
            }
        }

        return $results;
    }

    // options: sensitive, regex, followLinks
    private function filenameSearch($needle, $path, $originalPath, $options, $grepIgnorePaths, &$results = false)
    {
        $MAX_RESULTS = 3000;

        if (in_array($path, $grepIgnorePaths)) {
            return $results;
        }

        if ($results === false) {
            $results = [];
        } // start with empty results

        if ($options == '') {
            $options = [];
        }

        $files = scandir($path);
        if (! $files) {
            echo "$path not a directory.";
            exit();
        }

        foreach ($files as $filename) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }

            $file = $path . '/' . $filename;

            if (is_link($file) && ! in_array('followLinks', $options)) {
                continue;
            }
            if (is_dir($file)) {
                $this->filenameSearch($needle, $file, $originalPath, $options, $grepIgnorePaths, $results);

                continue;
            }

            $foundMatch = false;
            if (in_array('regex', $options)) {
                $c = preg_match('`' . $needle . '`' . (in_array('sensitive', $options) ? '' : 'i'), $file, $matches, PREG_OFFSET_CAPTURE);
                if ($c) {
                    $foundMatch = true;
                }
            } else {
                if (in_array('sensitive', $options)) {
                    $foundMatch = (stripos($file, $needle) !== false);
                } // (decided to make it always case insensitive) (strpos($file, $needle) !== false);
                else {
                    $foundMatch = (stripos($file, $needle) !== false);
                }
            }

            if ($foundMatch) {
                $results[] = ['type' => 'text', 'path' => $file, 'parentDir' => $path, 'text' => str_replace($originalPath, '', $file), 'startLine' => 0, 'startCol' => 0, 'endLine' => 0, 'endCol' => 0];
                if (count($results) > $MAX_RESULTS) {
                    echo 'MAX RESULTS LIMIT REACHED!<p>';

                    return $results;
                }
            }
        }

        return $results;
    }

    private function recentFiles($path = '')
    {
        $recent = unserialize(Cache::get('codeEditor:recent'));
        if (! $recent) {
            $recent = [];
        }

        if ($path != '') {
            // remove if already in the list
            foreach ($recent as $k => $r) {
                if ($r['path'] == $path) {
                    if ($k == 0) {
                        return $recent;
                    } // it's already #1
                    unset($recent[$k]);
                }
            }
            array_unshift($recent, ['name' => basename($path), 'path' => $path, 'type' => 'recentFile']);
            if (count($recent) > 40) {
                array_pop($recent);
            }
            $recent = array_merge($recent); // re-numbers the keys starting from 0
            Cache::forever('codeEditor:recent', serialize($recent));
        }

        return $recent;
    }

    private function dirSortCmp($a, $b)
    {
        $typePrecidence = ['fileGroup' => 0, 'dir' => 1, 'file' => 2];
        if ($a['type'] != $b['type']) {
            return $typePrecidence[$a['type']] < $typePrecidence[$b['type']] ? -1 : 1;
        }

        return strcasecmp($a['name'], $b['name']);
    }
}
