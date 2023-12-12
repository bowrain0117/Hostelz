<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
	<title>Code Editor</title>
	
	<link href='//fonts.googleapis.com/css?family=Open+Sans:400italic,700italic,400,700' rel='stylesheet' type='text/css'/>
	<link href='//fonts.googleapis.com/css?family=Droid+Sans+Mono' rel='stylesheet' type='text/css'/>
	<link href='//fonts.googleapis.com/css?family=VT323&v1' rel='stylesheet' type='text/css'>
	<link href='/codeEditor/css/kendo_styles/kendo.common.min.css' rel='stylesheet' type='text/css'/>
	<link href='/codeEditor/css/default.css' rel='stylesheet' type='text/css'/>
	<link href='/codeEditor/css/kendo_styles/kendo.black.min.css' id="kendoStyle" rel='stylesheet' type='text/css'/>

	<script type="text/javascript" src='/codeEditor/js/jquery.js'></script><!--  theirs is 1.6.4 -->
	<script type="text/javascript" src='/codeEditor/js/jquery.ui.all.js'></script><!-- theirs is 1.8.13.custom.min -->
	<script type="text/javascript" src='/codeEditor/js/jquery.mouseClick.js'></script>
	<script type="text/javascript" src='/codeEditor/js/kendo.all.min.js'></script>
	<script type="text/javascript" src='/codeEditor/js/ace/src-min/ace.js'></script>
	<script src="/codeEditor/js/ace/src-min/mode-css.js" type="text/javascript"></script>
	<script src="/codeEditor/js/ace/src-min/mode-html.js" type="text/javascript"></script>
	<script src="/codeEditor/js/ace/src-min/mode-javascript.js" type="text/javascript"></script>
	<script src="/codeEditor/js/ace/src-min/mode-php.js" type="text/javascript"></script>
	
	<script src="/codeEditor/js/ace/src-min/theme-twilight.js" type="text/javascript"></script>
    
	<script type="text/javascript" src='/codeEditor/js/ace/src-min/keybinding-emacs.js'></script>
	<script type="text/javascript" src='/codeEditor/js/ace/src-min/keybinding-vim.js'></script>
	<script type="text/javascript" src='/codeEditor/js/common.js'></script>
	<script type="text/javascript" src='/codeEditor/js/default.js'></script>
	<script type="text/javascript" src='/codeEditor/js/menu.js'></script>
	<script type="text/javascript" src='/codeEditor/js/modals.js'></script>
	<script type="text/javascript" src='/codeEditor/js/search.js'></script>
	
    <script type="text/javascript">
      var pref = {
    	uitheme: 'black',
    	
    	theme: 'twilight',
    	fontsize: '15px', /* doesn't seem to do anything, use css font-size below */
    	keybind: '{* ace *}',
    	swrap: 'free',
    	tabsize: 4,
    	
    	hactive: true,
    	hword: true,
    	invisibles: false,
    	gutter: true,
    	pmargin: false,
    	softab: true,
    	behave: false, /* ACE "behaviors" (auto complete parentheses and things) */
    	
    	save_session: true,
      };
      
      var init_session = @if ($savedSession != ''){!! json_encode($savedSession) !!}  @else null @endif ;
      var skip_session = true;
      var basedir = "/home/hostelz/dev"; 
      var loaded_themes = ['twilight'];
      /* var static_url = '/static/'; */
      var track_ajax = false;
    </script>
    <style type="text/css">
      .ace_editor { font-size: 13px; text-align: left; }
      body { font: 12px/120% "verdana", "arial", sans-serif; }
    </style>
</head>
<body>
    <div id="neutron_ui">
      <div id="neutron_menubar">
        {{-- <span id="logo" class="logo">logo goes here</span> --}}
    	<div>Filename Search: <input type="text" id="quick_filesearch"></div>
    	<button class="k-button" onclick="javascript:SaveCurrentTab()">Save</button>
    	<button class="k-button" onclick="javascript:SaveAll()">Save All</button>
        <button class="k-button" onclick="javascript:CloseAll()">Close All</button>
        <button class="k-button" onclick="javascript:blank_tab()">New</button>
    	<button id="menu_button" class="k-button" onclick="javascript:return show_menu()"><img src="/codeEditor/img/menu.png" alt="Menu" title="Menu"/></button>
    	<div><input type="text" id="quick_search" placeholder="find"></div>
        <div id="current_path" onClick="javascript:change_path()"></div>
        <div id="currentEdits" class=lt2Color onClick="javascript:updateCurrentEditCount()"></div>
    	<div id="status"></div>
      </div>
      <div id="neutron_body">
    	<div id="splitter">
    	  <div id="splitter_left">
    		<div id="tooltabs">
    		  <ul>
    			<li id="fbtab">Files</li>
        		<li id="searchtab">Search</li>
        		<li id="greptab">Grep</li>
    		  </ul>
    		  <div>
    			<div id="file_browser">
    			  <div class="inner"></div>
    			</div>
    		  </div>
    		  <div>
    			<ul id="search_panel">
                  <li id="search_panel_search">
                	Search Options
                	<div>
                	  <form onsubmit="return do_search()">
                		<div class="top_radio">
                		  <div class="opt">
                			<input type="radio" id="stype_search" value="search" name="stype" checked="checked" onclick="search_ui('search')"/>
                			<label for="stype_search">Search</label>
                		  </div>
                		  <div class="opt">
                			<input type="radio" id="stype_replace" value="replace" name="stype" onclick="search_ui('replace')"/>
                			<label for="stype_replace">Search/Replace</label>
                		  </div>
                		  <div class="clear"></div>
                		</div>
                		<div>
                		  Search:<br/>
                		  <input type="text" id="search_term" name="search_term"/>
                		</div>
                		<div>
                		  Replace:<br/>
                		  <input type="text" id="replace_term" name="replace_term" disabled="disabled"/>
                		</div>
                		<div>
                		  Search In:
                		  <div class="indent">
                			<div class="opt">
                			  <input type="radio" id="sin_current" value="current" name="sin" checked="checked" onclick="search_ui('sin_current')"/>
                			  <label for="sin_current">Current Tab</label>
                			</div>
                			<div class="opt">
                			  <input type="radio" id="sin_all" value="all" name="sin" onclick="search_ui('sin_all')"/>
                			  <label for="sin_all">All Tabs</label>
                			</div>
                			
                			<div class="clear"></div>
                			
                		  </div>
                		</div>
                		<div>
                		  Options:
                		  <div class="indent">
                			<!--<div class="opt">
                			  <input type="checkbox" id="search_select" value="ON" name="search_select"/>
                			  <label for="search_select">Search Selection</label>
                			</div>-->
                			<div class="opt">
                			  <input type="checkbox" id="search_wrap" value="ON" name="search_wrap" checked="checked"/>
                			  <label for="search_wrap">Wrap Search</label>
                			</div>
                			<div class="opt">
                			  <input type="checkbox" id="search_back" value="ON" name="search_back"/>
                			  <label for="search_back">Search Backwards</label>
                			</div>
                			<div class="opt">
                			  <input type="checkbox" id="search_sensitive" value="ON" name="search_sensitive"  checked="checked"/>
                			  <label for="search_sensitive">Case Sensitive</label>
                			</div>
                			<div class="opt">
                			  <input type="checkbox" id="search_whole" value="ON" name="search_whole"/>
                			  <label for="search_whole">Whole Word</label>
                			</div>
                			<div class="opt">
                			  <input type="checkbox" id="search_regex" value="ON" name="search_regex"/>
                			  <label for="search_regex">Regex</label>
                			</div>
                			<div class="clear"></div>
                		  </div>
                		</div>
                		<div id="search_buttons">
                		  <div id="search_submit">
                			<input type="submit" id="search_search" value="Search" name="search_search"/>
                		  </div>
                		  <div id="next_prev" style="display: none;">
                			<input type="button" value="&laquo; Prev" name="do_search_prev" onclick="search_next('back')"/>
                			<input type="button" value="Next &raquo;" name="do_search_next" onclick="search_next('forward')"/>
                			<input type="button" value="Done" name="search_new" onclick="search_ui('new_search')"/>
                		  </div>
                		  <div id="replace_next" style="display: none;">
                			<input type="button" value="Next &raquo;" name="do_search_next" onclick="search_next('forward')"/>
                			<input type="button" value="Replace &amp; Next &raquo;" name="do_replace_next" onclick="replace_next()"/>
                			<input type="button" value="Replace All" name="do_replace_all" onclick="replace_all()"/>
                			<input type="button" value="New Search" name="search_new" onclick="search_ui('new_search')"/>
                		  </div>
                		  <div id="replace_all_tabs" style="display: none;">
                			<input type="button" value="Replace All" name="do_replace_all_tab" onclick="replace_all_tab()"/>
                			<input type="button" value="New Search" name="search_new" onclick="search_ui('new_search')"/>
                		  </div>
                		  <div id="search_status" style="display: none;">
                			<input type="button" value="Cancel" name="cancel" onclick="cancel_search()"/>
                			<span></span>
                		  </div>
                		  <div id="replace_status" style="display: none;">
                			<input type="button" value="New Search" name="search_new" onclick="search_ui('new_search')"/>
                			<input type="button" value="Replace All" name="replace" onclick="replace_dfiles()"/>
                		  </div>
                		  <div id="replace_status_started" style="display: none;">
                			<input type="button" value="Cancel" name="cancel" onclick="cancel_replace()"/>
                			<span></span>
                		  </div>
                		</div>
                	  </form>
                	</div>
                  </li>
                  <li id="search_panel_replace">
                	Search Results
                	<div id="search_panel_results">
                	  <h2>No Search Started</h2>
                	</div>
                  </li>
                </ul>
    		  </div>
        	  <div>
    			<ul id="grep_panel">
                  <li id="grep_panel_search">
                	Grep Options
                	<div>
                      <form onsubmit="javascript:return do_grep()">
                		<div>
                		  Search:<br/>
                		  <input type="text" id="grep_term" name="grep_term"/>
                		</div>
                       <div>
                		  File groups:
                		  <div class="indent">
                            @foreach ($fileSets as $key => $fileSet)
                    			<div class="opt">
                    			  <input type="checkbox" value="{{{ $key }}}" id="grepSet_{{{ $key }}}" @if ($fileSet['grepByDefault']) CHECKED @endif />
                    			  <label for="grepSet_{{{ $key }}}">{{{ $fileSet['label'] }}}</label>
                    			</div>
                            @endforeach 
                			<div class="clear"></div>
                		  </div>
                		</div>
                      <div>
                		  Options:
                		  <div class="indent">
                       		<div class="opt">
                    			  <input type="checkbox" value="ON" id="grepOption_sensitive" checked="checked"/>
                       		  <label for="grepOption_sensitive">Case Sensitive</label>
                    			</div>
                         	<div class="opt">
                    			  <input type="checkbox" value="ON" id="grepOption_regex"/>
                       		  <label for="grepOption_regex">Regex</label>
                    			</div>
                            <div class="opt">
                    			  <input type="checkbox" value="ON" id="grepOption_followLinks"/>
                       		  <label for="grepOption_followLinks">Follow Links</label>
                    			</div>
                            <div class="opt">
                    			  <input type="checkbox" value="ON" id="grepOption_expanded" checked="checked"/>
                       		  <label for="grepOption_expanded">Expand Results</label>
                    			</div>
                            <div class="opt">
                    			  <input type="checkbox" value="ON" id="grepOption_filenameSearch"/>
                       		  <label for="grepOption_filenameSearch">Filename Search</label>
                    			</div>
                			<div class="clear"></div>
                		  </div>
                		</div>
                		<div id="grep_buttons">
                		  <div id="grep_submit">
                            <input type="submit" id="grep_search" value="Grep" name="grep_search"/>
                		  </div>
                		</div
                	  </form>
                	</div>
                  </li>
                  <li id="grep_results_panel">
                   Results
                	<div id="grep_results">
                	</div>
                  </li>
                </ul>
              </div>
    		</div>
    	  </div>
    	  <div id="splitter_right">
    		<div id="tabs">
    		  <div id="tabsinner">
    			<ul></ul>
    		  </div>
    		</div>
    		<div id="editor_global"></div>
    	  </div>
    	</div>
      </div>
    </div>
    	
    <div id="main_menu">
      <ul>
    	<li><a href="javascript: void(0)" onclick="show_pref()" class="pref">Editor Preferences</a></li>
    	<li class="btop">
    	  <span class="submenu">
    		<span class="arrow">&raquo;</span>Editor Mode
    		<ul class="emodes">
    		  <li><a href="javascript: void(0)" onclick="set_editor_mode('html')">HTML</a></li>
    		  <li><a href="javascript: void(0)" onclick="set_editor_mode('javascript')">Javascript</a></li>
    		  <li><a href="javascript: void(0)" onclick="set_editor_mode('php')">PHP</a></li>
    		  <li><a href="javascript: void(0)" onclick="set_editor_mode('text')">Plain Text</a></li>
    		</ul>
    		<div class="clear"></div>
    	  </span>
    	</li>
    	<li class="btop">
    	  <span class="submenu">
    		<span class="arrow">&raquo;</span>Help! 
    		<ul>
    		  <li><a href="https://github.com/pizzapanther/Neutron-IDE/wiki/Default-Key-Bindings" target="_blank" class="link">Key Bindings</a></li>
    		  <li><a href="https://github.com/pizzapanther/Neutron-IDE/wiki/Emacs-Key-Bindings" target="_blank" class="link">EMacs Key Bindings</a></li>
    		  <li><a href="https://github.com/pizzapanther/Neutron-IDE/wiki/Vim-Key-Bindings" target="_blank" class="link">Vim Key Bindings</a></li>
    		  <li><a href="http://neutronide.com/" target="_blank" class="link">NeutronIDE.com</a></li>
    		</ul>
    	  </span>
    	</li>
    	
    	<li><a href="javascript: void(0)" onclick="about()" class="about">About</a></li>
      </ul>
    </div>
    
    	<div style="display: none;">
    	  <div id="new_stuff">
      <form onsubmit="return create_new()" enctype="multipart/form-data">
    	New Type:
    	<div class="indent">
    	  <input type="radio" name="new_type" value="file" id="new_type_file" checked="checked" onclick="show_new_fn()"/> 
    	  <label for="new_type_file">Empty File</label><br/>
    	  <input type="radio" name="new_type" value="url" id="new_type_url" onclick="show_new_fn()"/> 
    	  <label for="new_type_url">File by URL</label><br/>
    	  <input type="radio" name="new_type" value="up" id="new_type_up" onclick="show_new_upload()"/> 
    	  <label for="new_type_up">File by Upload</label><br/>
    	  <input type="radio" name="new_type" value="dir" id="new_type_dir" onclick="show_new_fn()"/> 
    	  <label for="new_type_dir">Directory</label>
    	</div>
    	<p></p>
    	<p id="new_filename">
    	  <label>Name/URL:</label>&nbsp; <input type="text" name="new_file" id="new_file" value="" style="width: 350px"/>
    	</p>
    	<p id="new_upload" style="display: none;">
    	  <label>File:</label>&nbsp; <input type="file" name="new_upload_file" id="new_upload_file"/> <span id="span_new_upload_file"></span>
    	</p>
    	<p>
    	  <input type="button" name="create_new_file" id="create_new_file" value="Create" class="floatr" onclick="create_new()"/>
    	</p>
    	<input type="hidden" name="create_new_dpath" id="create_new_dpath" value=""/>
    	<input type="hidden" name="create_new_did" id="create_new_did" value=""/>
    	<input type="hidden" name="temp_file" id="temp_file" value=""/>
      </form>
    </div>
    <div id="saveall" onClick="javascript:save_win.close()" {{-- close when clicked... we had a problem at least once of it not closing on its own --}}>
      <div>Saving:</div>
    </div>
    <div id="editor_pref">
      <iframe width="100%" height="340" frameBorder="0" scrolling="auto" src=""></iframe>
    </div>
    <div id="dir_chooser">
      <div class="browser">
    	
      </div>
      <div>
    	<input type="text" name="dir_chooser_dialog" id="dir_chooser_dialog"/>
    	<input type="button" name="ok_go" value="OK" onclick="choose_dir_ok()"/>
    	<input type="button" name="cancel" value="Cancel" onclick="dir_win.close()"/>
      </div>
    </div>
    
    </div>
</body>

