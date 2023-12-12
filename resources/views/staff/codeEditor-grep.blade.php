<style>
   div.grepResultList {
    margin: 4px 1px 4px 1px;
	}
   a.grepFile {
    margin: 1px;
    padding: 2px 2px 2px 4px;
    font-size: 12px;
	 background-color: #5c5999;
	 color: #cfcfde;
    display: block;
    word-wrap: break-word;
	}
   div.grepResultText {
    margin: 1px;
    padding: 2px 2px 2px 4px;
    font-size: 12px;
    @if (in_array('expanded', $options)) display: block; @else display: none; @endif 
	}
	
    .grepResultHighlight {
        background-color: #5D4848;
    }
   
   div.dottedDivider {
    border-style: dotted;
    border-width: 1px 0px 0px 0px;
    border-color: #666666;
   }

   div.grepResultText a {
    color: #dedede;
   }
   div.grepResultText span {
    font-weight: 700;
    color: #c58080;
   }
</style>

@if (!$results)
   No matches found.
@else 
   <div class=grepResultList>
   <?php $lastType = ''; ?>
   @foreach ($results as $key => $item)
      @if ($item['type'] == 'file')
         <a onclick="javascript:$('.grepResult{!! crc32($item['path']) !!}').toggle()" class=grepFile parentDir="{{{ $item['parentDir'] }}}">{{{ $item['path'] }}}</a>
      @elseif($item['type'] == 'text')
         <div class="grepResultText grepResult{!! crc32($item['path']) !!} grepResultItem{{{ $key }}} @if ($item['type'] == $lastType) dottedDivider @endif "><a href="javascript: void(0)" onclick="$('.grepResultText').removeClass('grepResultHighlight');$('.grepResultItem{{{ $key }}}').addClass('grepResultHighlight');go_to_line('{{{ $item['path'] }}}', {!! $item['startLine'] !!},{!! $item['startCol'] !!}, {!! $item['endLine'] !!},{!! $item['endCol'] !!})">{!! str_replace([ "\tMATCH_START\t", "\tMATCH_END\t" ], [ "<span>", "</span>" ], htmlentities($item['text'])) !!}</a></div>{{-- note: the <div> is needed to make it show as a block if initially hidden --}}
      @endif 
      <?php $lastType = $item['type']; ?>
   @endforeach 
   </div>
   <a href="#" style="color:#666" onClick="javascript:$('div.grepResultText a').click()">Open All</a>
@endif 

<script type="text/javascript">
    var rightClickParentDir;
  
    $('.grepFile').bind('contextmenu', function(e) {
        rightClickParentDir = $(e.target).attr('parentDir');

        var rightMenuID = $(e.target).attr('class') + "_right";
        
        /* $("#"+rightMenuID+" .fd").text(rightClickPath); */
        $("#"+rightMenuID).css("display", 'block');

        var wrapperY = $('#grep_results').position().top;
        var t = e.pageY - 30;
        var h = $("#"+rightMenuID).height();
        var bottom = t + h;
        var doch = $(window).height();
        if (bottom > doch) {
            t = doch - h;
        }
        $("#"+rightMenuID).css("top", (t-wrapperY) + 'px');
         
        var ol = e.pageX - 20;
        $("#"+rightMenuID).css("left", ol + 'px');
        return false;
    });
</script>

<div id="grepFile_right" class="right_menu">
<ul>
<li class="fd"></li>
<li class="btop"><a href="javascript: void(0)" onclick="javascript:$('#fbtab').click();file_browser(rightClickParentDir);" class="view">Go to directory</a></li>
</ul>
</div>


{{--
<div class="title"><em>All Tabs</em><strong>Search For: ' + search_options['needle'] + '</strong></div>';
      html += '<table>';
      html += '<tr><td><strong>Filename</strong></td><td><strong>Matches</strong></td></tr>';
      
      var item = $("#search_panel_replace");
      search_panel.select(item);
      search_panel.expand(item);
      
      for (dp in tab_paths) {
        var ranges = current_search.findAll(tab_paths[dp].session);
        if (ranges.length > 0) {
          var fn = dp.replace(basedir + "/", "");
          
          lines = '<div class="lines" id="line_results_' + tab_paths[dp].uid + '" style="display: none;">';
          for (var i=0; i < ranges.length; i++) {
            var row = ranges[i].start.row + 1;
            var col = ranges[i].start.column + 1;
            lines += '<a href="javascript: void(0)" onclick="go_to_line(\'' + escape(dp) + '\', ' + ranges[i].start.row + ', ' + ranges[i].start.column + ', ' + ranges[i].end.row + ', ' + ranges[i].end.column + ')">Line ' + row + ', Column ' + col + '</a>';
          }
          
          lines += '</div>';
          html += '<tr>';
          html += '<td><a class="expand" href="javascript: void(0)" onclick="show_line_results(\'' + tab_paths[dp].uid + '\')">' + fn +'</a>' + lines + '</td><td>' + ranges.length + '</td>';
          html += '</tr>';
--}}