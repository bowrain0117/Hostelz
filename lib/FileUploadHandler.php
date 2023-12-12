<?php

namespace Lib;

use Carbon\Carbon;
use File;
use Request;
use Response;

class FileUploadHandler
{
    public $debugLogging = false;

    // Set in the constructor
    public $allowedFileExtensions;

    public $maxFileSizeMB;

    public $maxFiles;

    public $existingFileCount;

    // Other filters that can be set
    public $minImageHeight = null;

    public $minImageWidth = null;

    public function __construct($allowedFileExtensions = false, $maxFileSizeMB = false, $maxFiles = false, $existingFileCount = false)
    {
        $this->allowedFileExtensions = ($allowedFileExtensions ? array_map('mb_strtolower', $allowedFileExtensions) : null); // changed to lowercase to to let our comparison be case-insensitive
        $this->maxFileSizeMB = $maxFileSizeMB;
        $this->maxFiles = $maxFiles;
        $this->existingFileCount = $existingFileCount;
    }

    public function handleUpload($saveFileCallback)
    {
        switch (Request::input('fileUploadMode')) {
            // (could have other modes in the future)

            case 'upload':
                set_time_limit(20 * 60);

                // Check file extension (the upload Javascript should have already ignored it, but this is an extra safe-guard)

                if ($this->allowedFileExtensions && Request::hasFile('file')) {
                    if (! in_array(mb_strtolower(Request::file('file')->getClientOriginalExtension()), $this->allowedFileExtensions)) {
                        return $this->uncachedJsonResponse(['jsonrpc' => '2.0', 'error' => ['code' => 104, 'message' => 'Not an allowed filename extension.'], 'id' => 'id']);
                    }
                }

                if (! File::exists(storage_path() . '/uploadTemp')) {
                    File::makeDirectory(storage_path() . '/uploadTemp');
                }
                $tempUploadFile = storage_path() . '/uploadTemp/' . md5(Request::input('uniqid') . Request::server('REMOTE_ADDR') . Request::input('name'));

                // Get parameters (these default to 0 if not submitted)
                $chunk = (int) Request::input('chunk');
                $chunks = (int) Request::input('chunks');

                // Look for the content type header
                $contentType = Request::server('CONTENT_TYPE') != '' ? Request::server('CONTENT_TYPE') : Request::server('HTTP_CONTENT_TYPE');

                // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
                if (strpos($contentType, 'multipart') !== false) {
                    if (Request::hasFile('file')) {
                        // Open temp file
                        if ($this->debugLogging) {
                            debugOutput("Creating temp file '$tempUploadFile'.");
                        }
                        $out = fopen($tempUploadFile, $chunk == 0 ? 'wb' : 'ab');
                        if ($out) {
                            // Read binary input stream and append it to temp file
                            if ($this->debugLogging) {
                                debugOutput("Reading from upload file '" . Request::file('file')->getRealPath() . "'.");
                            }
                            $in = fopen(Request::file('file')->getRealPath(), 'rb');

                            if ($in) {
                                while ($buff = fread($in, 4096)) {
                                    fwrite($out, $buff);
                                }
                            } else {
                                //triggerError("Failed to open input stream.");
                                if ($this->debugLogging) {
                                    debugOutput('Failed to open input stream.');
                                }

                                return $this->uncachedJsonResponse(['jsonrpc' => '2.0', 'error' => ['code' => 101, 'message' => 'Failed to open input stream.'], 'id' => 'id']);
                            }
                            fclose($in);
                            fclose($out);
                            if ($this->debugLogging) {
                                debugOutput('Deleting upload file.');
                            }
                            @unlink(Request::file('file')->getRealPath());
                        } else {
                            if ($this->debugLogging) {
                                debugOutput('Failed to open output stream.');
                            }

                            return $this->uncachedJsonResponse(['jsonrpc' => '2.0', 'error' => ['code' => 102, 'message' => 'Failed to open output stream.'], 'id' => 'id']);
                        }
                    } else {
                        if ($this->debugLogging) {
                            debugOutput('Failed to move uploaded file.');
                        }

                        return $this->uncachedJsonResponse(['jsonrpc' => '2.0', 'error' => ['code' => 103, 'message' => 'Failed to move uploaded file.'], 'id' => 'id']);
                    }
                } else {
                    // Open temp file
                    $out = fopen($tempUploadFile, $chunk == 0 ? 'wb' : 'ab');
                    if ($out) {
                        // Read binary input stream and append it to temp file
                        if ($this->debugLogging) {
                            debugOutput('Reading from input stream.');
                        }
                        $in = fopen('php://input', 'rb');

                        if ($in) {
                            while ($buff = fread($in, 4096)) {
                                fwrite($out, $buff);
                            }
                        } else {
                            if ($this->debugLogging) {
                                debugOutput('Failed to open input stream.');
                            }

                            return $this->uncachedJsonResponse(['jsonrpc' => '2.0', 'error' => ['code' => 101, 'message' => 'Failed to open input stream.'], 'id' => 'id']);
                        }

                        fclose($in);
                        fclose($out);
                    } else {
                        if ($this->debugLogging) {
                            debugOutput('Failed to open output stream.');
                        }

                        return $this->uncachedJsonResponse(['jsonrpc' => '2.0', 'error' => ['code' => 102, 'message' => 'Failed to open output stream.'], 'id' => 'id']);
                    }
                }

                // Check if file has been uploaded
                if (! $chunks || $chunk == $chunks - 1) {
                    if ($this->debugLogging) {
                        debugOutput('Complete.  Calling callback.');
                    }
                    $saveFileCallback(Request::file('file')->getClientOriginalName(), $tempUploadFile);
                    if ($this->debugLogging) {
                        debugOutput('Callback complete, deleting temp file.');
                    }
                    @unlink($tempUploadFile);
                }

                // Return JSON-RPC response
                return $this->uncachedJsonResponse(['OK' => 1]);
        }

        return null;
    }

    private function uncachedJsonResponse($data)
    {
        return preventBrowserCaching(Response::json($data));
    }

    /* Maintenance */

    // (called daily by WebsiteMaintenance)
    public static function dailyMaintenance()
    {
        $output = '';

        // Delete old files from storage_path().'/uploadTemp'

        $output .= 'Delete old FileUploadHandler temp files: ';

        $count = 0;
        foreach (File::files(storage_path() . '/uploadTemp') as $file) {
            $lastModified = Carbon::createFromTimeStamp(File::lastModified($file));
            if ($lastModified->diffInDays() > 1) {
                $output .= $file . ' (' . $lastModified->format('Y-m-d') . ') ';
                File::delete($file);
                $count++;
            }
        }

        if (! $count) {
            $output .= '(None)';
        }

        return $output;
    }
}
