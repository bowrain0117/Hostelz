<style>
   div.fileList {
	 margin: 4px 1px 4px 1px;
	}
   div.filelistTitle {
	 margin: 12px 3px 5px 3px;
	 font-size: 11px;
	}
   div.fileGroup {
    margin: 0px 0px 1px 0px;
    padding: 2px 2px 2px 4px;
	 background-color: #993e3e;
	 color: #decaca;
	 font-size: 13px;
	}
   div.quickLinks {
    margin: 0px 0px 1px 0px;
    padding: 2px 2px 2px 4px;
	 background-color: #3e994f;
	 color: #fff;
	 font-size: 13px;
	}
	div.file {
    margin: 1px;
    padding: 2px 2px 2px 4px;
	 background-color: #5c5999;
	 color: #cadece;
	}	
   div.recentFile {
    margin: 1px;
	 padding: 1px 2px 1px 4px;
	 background-color: #5c5999;
	 color: #cfcfde;
	}
	div.dir {
    margin: 1px;
	 padding: 2px 1px 2px 4px;
	 background-color: #9693c5;
	 color: white;
	}
</style>

<script type="text/javascript">
    var rightClickPath, rightClickFile, rightClickWorkingPath;
  
    $('.dir, .file').bind('contextmenu', function(e){
        rightClickPath = $(e.target).attr('filepath');
        rightClickWorkingPath = $(e.target).attr('workingPath');
        rightClickFile = $(e.target).text();
        if (rightClickFile == '..') return;
        
        var rightMenuID = $(e.target).attr('class') + "_right";
        
        $("#"+rightMenuID+" .fd").text(rightClickFile);
        $("#"+rightMenuID).css("display", 'block');
                
        var t = e.pageY - 20;
        var h = $("#"+rightMenuID).height();
        var bottom = t + h;
        var doch = $(window).height();
        if (bottom > doch) {
            t = doch - h;
        }
        $("#"+rightMenuID).css("top", t + 'px');
                
        var ol = e.pageX - 20;
        $("#"+rightMenuID).css("left", ol + 'px');
        return false;
    });
</script>

<div class=fileList>
@foreach ($outputList as $key => $file)
    @if ($file['type'] == 'title')
		<div class=filelistTitle>{{{ $file['name'] }}}</div>
    @elseif($file['type'] == 'fileGroup')
		<a href="#" onclick="javascript:file_browser('{{{ $file['path'] }}}')"><div class=fileGroup>{{{ $file['name'] }}}</div></a>
    @elseif($file['type'] == 'quickLinks')
		<a href="#" onclick="javascript:file_browser('{{{ $file['path'] }}}')"><div class=quickLinks>{{{ $file['name'] }}}</div></a>
    @elseif($file['type'] == 'dir')
		<a href="#" onclick="javascript:file_browser('{{{ $file['path'] }}}')"><div class=dir filepath="{{{ $file['path'] }}}" workingPath="{{{ $file['workingPath'] }}}">{{{ $file['name'] }}}</div></a>
    @elseif($file['type'] == 'file')
		<a href="#" onclick="javascript:get_file('{{{ $file['path'] }}}')"><div class=file filepath="{{{ $file['path'] }}}" workingPath="{{{ $file['workingPath'] }}}">{{{ $file['name'] }}}</div></a>
    @elseif($file['type'] == 'recentFile')
		<a href="#" onclick="javascript:get_file('{{{ $file['path'] }}}')"><div class=recentFile filepath="{{{ $file['path'] }}}">{{{ $file['name'] }}}</div></a>
	@endif 
@endforeach 
</div>

<div id="dir_right" class="right_menu">
<ul>
<li class="fd"></li>
<li class="btop"><a href="javascript: void(0)" onclick="new_stuff(rightClickPath, rightClickPath)" class="new">New File/Dir</a></li>
<li><a href="javascript: void(0)" onclick="file_browser(rightClickWorkingPath)" class="refresh">Refresh</a></li>
<li><a href="javascript: rename(rightClickWorkingPath, rightClickPath)" class="rename">Rename</a></li>
<li><a href="javascript: delete_me(rightClickWorkingPath, rightClickPath)" class="delete">Delete</a></li>
</ul>
</div>

<div id="file_right" class="right_menu">
<ul>
<li class="fd"></li>
<li class="btop"><a href="javascript: void(0)" onclick="new_stuff(rightClickWorkingPath, rightClickWorkingPath)" class="new">New File/Dir</a></li>
<li><a href="javascript: void(0)" onclick="file_browser(rightClickWorkingPath)" class="refresh">Refresh</a></li>
<li><a href="javascript: rename(rightClickWorkingPath, rightClickPath)" class="rename">Rename</a></li>
<li><a href="javascript: delete_me(rightClickWorkingPath, rightClickPath)" class="delete">Delete</a></li>
</ul>
</div>