{{--

Designed to work with lib/FileUpload.php.

--}}

@if (@$fileUploadMessage != '')
    <div class="alert alert-warning">{!! $fileUploadMessage !!}</div>
@endif

@if ($fileUpload->maxFiles && $fileUpload->maxFiles - $fileUpload->existingFileCount < 1)
    @if ($fileUpload->maxFiles == 1)
        <div class="alert alert-warning">Before you can upload a new file, you would first need to delete the existing one.</div>
    @else
        <div class="alert alert-warning">Maximum number of files reached.  Delete from the existing files first before uploading new files.</div>
    @endif
@else
    <div id="uploadBox">
        <div id="filelist">[Loading]</div>
        <br>
        
        <button id="pickfiles" class="btn btn-warning" href="javascript:;">@if ($fileUpload->maxFiles == 1) Select File @else Select Files @endif</button> 
        
        <button id="uploadfiles" style="visibility: hidden" class="btn btn-primary" href="javascript:;">Upload Now</button>
        <br><br>
        
        <small>
            @if ($fileUpload->allowedFileExtensions != '')
                <div>Accepted file types: <em>{!! strtoupper(implode(', ', $fileUpload->allowedFileExtensions)) !!}</em></div>
            @endif

            @if ($fileUpload->minImageWidth)
                <div>Minimum image width: <em>{!! $fileUpload->minImageWidth !!} pixels</em></div>
            @endif
                    
            @if ($fileUpload->minImageHeight)
                <div>Minimum image height: <em>{!! $fileUpload->minImageHeight !!} pixels</em></div>
            @endif
            
            @if ($fileUpload->maxFileSizeMB)
                <div>Maximum file size: <em>{!! $fileUpload->maxFileSizeMB !!} MB</em></div>
            @endif
            
            @if ($fileUpload->maxFiles)
                <div>Maximum total number of files: <em>{!! $fileUpload->maxFiles !!}</em></div>
            @endif
         </small>
    </div>
    
    
    @section('pageBottom')
    
        <?php
            Lib\HttpAsset::requireAsset('plupload');
        ?>
        
        <script type="text/javascript">
        
            @if ($fileUpload->minImageWidth)
    
                plupload.addFileFilter('min_img_width', function(minWidth, file, cb) {
                    var self = this, img = new o.Image();
                    function finalize(result) {
                        // cleanup
                        img.destroy();
                        img = null;
                        // if rule has been violated in one way or another, trigger an error
                        if (!result) {
                            self.trigger('Error', {
                                code : plupload.IMAGE_DIMENSIONS_ERROR,
                                message : "Image must be at least " + minWidth  + " pixels wide.",
                                file : file
                            });
                        }
                        cb(result);
                    }
                    img.onload = function() { finalize(img.width >= minWidth); };
                    img.onerror = function() { finalize(false); };
                    img.load(file.getSource());
                });
            
            @endif
            
            @if ($fileUpload->minImageHeight)
    
                plupload.addFileFilter('min_img_height', function(minHeight, file, cb) {
                    var self = this, img = new o.Image();
                    function finalize(result) {
                        // cleanup
                        img.destroy();
                        img = null;
                        // if rule has been violated in one way or another, trigger an error
                        if (!result) {
                            self.trigger('Error', {
                                code : plupload.IMAGE_DIMENSIONS_ERROR,
                                message : "Image must be at least " + minHeight  + " pixels in height.",
                                file : file
                            });
                        }
                        cb(result);
                    }
                    img.onload = function() { finalize(img.height >= minHeight); };
                    img.onerror = function() { finalize(false); };
                    img.load(file.getSource());
                });
            
            @endif


            <?php
                $url = '/'.Request::path().'?';
                $url .= (Request::server('QUERY_STRING') != '') ? Request::server('QUERY_STRING').'&' : '';

                $url = ! empty($requestURL) ? $requestURL.'?' : $url;
            ?>
        
            var uploader = new plupload.Uploader({
                filters : {
                    @if ($fileUpload->maxFileSizeMB)
                	    max_file_size : '{!! $fileUpload->maxFileSizeMB !!}mb',
                    @endif
                    @if ($fileUpload->allowedFileExtensions != '')
                    	mime_types: [
                    		{ title : "Accepted File Types", extensions : "{!! implode(',', $fileUpload->allowedFileExtensions) !!}" }
                    	],
                    @endif
                    @if ($fileUpload->minImageWidth)
                        min_img_width : {!! $fileUpload->minImageWidth !!},
                    @endif
                    @if ($fileUpload->minImageHeight)
                        min_img_height : {!! $fileUpload->minImageHeight !!},
                    @endif
                    dummy : true {{-- just here so that IE doesn't complain about ending the set with a comma --}}
            	},
                @if ($fileUpload->maxFiles == 1)
                    multi_selection : false,
                @endif
                runtimes : 'html5,flash,silverlight,html4',
                flash_swf_url : '/vendor/plupload/Moxie.swf',
                silverlight_xap_url : '/vendor/plupload/Moxie.xap',
                browse_button : 'pickfiles',
            	container: 'uploadBox',
                prevent_duplicates: true,
            	url : '{!! $url !!}fileUploadMode=upload&uniqid={!! uniqid() !!}',
            	multipart_params : {
                    "_token" : "{!! csrf_token() !!}" {{-- (for CSRF) --}}
                }
            });
    
            uploader.bind('Init', function(up, params) {
            	$('#filelist').html(""); {{-- <div>Current runtime: " + params.runtime + "</div>"; --}}
            	up.refresh(); {{-- Fixes an issue where the Select Files button wasn't working sometimes with firefox (refresh has to be done after anything on the page changes). --}}
            });
            
            uploader.bind('Error', function(up, err) {
                alert("\nError: " + err.message);
            });
            
            uploader.bind('FilesAdded', function(up, files) {
                @if ($fileUpload->maxFiles)
                    if (up.files.length  + 1 >= {!! $fileUpload->maxFiles - $fileUpload->existingFileCount !!}) {
                        $(up.settings.browse_button).hide();
                    }
                @endif
    
                $.each(files, function(i, file) {
                    $('#filelist').append('<div id="' + file.id + '">' + file.name + ' <a href="#" id="' + file.id + '" class="removeFile" style="font-size: 12px; vertical-align: super;"><span class="glyphicon glyphicon-remove text-danger"></span></a> <b></b></div>'); {{-- progress bar is inserted between the <b></b> tags --}}
                });
                $('#uploadfiles').css('visibility', 'visible');
                
                up.refresh(); {{-- Reposition Flash/Silverlight --}}
                
            });
            
            uploader.bind('FilesRemoved', function(up, files) {
                if (up.files.length <= {!! $fileUpload->maxFiles - $fileUpload->existingFileCount !!}) {
                    $(up.settings.browse_button).show();
                }
                if (!up.files.length) {
                    $('#uploadfiles').css('visibility', 'hidden');   
                }
            });
        
            {{-- Remove file "x" button --}}
            $("#filelist" ).on("click", "a.removeFile", function(e) {
                uploader.removeFile(uploader.getFile(this.id));
                $('#'+this.id).remove();
                e.preventDefault();
            });
            
            uploader.bind('UploadProgress', function(up, file) {
            	$("#" + file.id).children('b').html('<span>' + file.percent + "%</span>");
            });
            
            $('#uploadfiles').click(function(e) {
            	uploader.start();
            	e.preventDefault();
            });
            
            uploader.bind('UploadComplete', function() {
                location.reload();
            });
        
            uploader.init();
            
        </script>
    
        @parent
    @stop

@endif
