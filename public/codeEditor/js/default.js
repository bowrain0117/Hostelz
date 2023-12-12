$(document).ajaxSend(function(event, xhr, settings) {
    function sameOrigin(url) {
        // url could be relative or scheme relative or absolute
        var host = document.location.host; // host + port
        var protocol = document.location.protocol;
        var sr_origin = '//' + host;
        var origin = protocol + sr_origin;
        // Allow absolute or scheme relative URLs to same origin
        return (url == origin || url.slice(0, origin.length + 1) == origin + '/') ||
            (url == sr_origin || url.slice(0, sr_origin.length + 1) == sr_origin + '/') ||
            // or any other URL that isn't scheme relative or absolute i.e relative.
            !(/^(\/\/|http:|https:).*/.test(url));
    }
    function safeMethod(method) {
        return (/^(GET|HEAD|OPTIONS|TRACE)$/.test(method));
    }
    
    if (track_ajax) {
      _gaq.push(['_trackPageview', settings.url.split("?")[0]]);
    }
    
    if (!safeMethod(settings.type) && sameOrigin(settings.url)) {
        xhr.setRequestHeader("X-CSRFToken", getCookie('csrftoken'));
    }
});

var tab_counter = 0;
var $tabs = null;
var load_data = "";
var tab_paths = {}; // path => { tab, session, filename, uid, unsaved, unsaved, savedAtUndoCount }
var tab_counts = {}; // tab number => path
var track_int = 600000;

/* Gets the tab # from the tab's href */
function tabNumFromHref(href) {
    if(!href) return false;
    var tabNum = href.split('-');
    return tabNum[tabNum.length - 1];
}

function CurrentTabNum() {
    return tabNumFromHref($("ul.ui-tabs-nav li.ui-tabs-selected a").attr('href'));
}

function get_ui_tab_index(tabNum) {
  $ele = $('ul.ui-tabs-nav li a[href$="#tabs-'+tabNum+'"]').parent();
  return $("li", $tabs).index($ele);
}

function CurrentTabPath() {
    var tabNum = CurrentTabNum();
    if(tabNum === false) return false;
    return tab_counts[tabNum];
}

// pass tabPath false to use current tab path
function markTabSaved(tabPath) {
   if(tabPath === false) tabPath = CurrentTabPath();
   if(tabPath === false) return;

   var tabInfo = tab_paths[tabPath];
   tabInfo.savedAtUndoCount = tabInfo.session.getUndoManager().$undoStack.length;
   updateEditCount(tabPath);
}

// pass tabPath false to use current tab path
function markTabUnsaved(tabPath) {
   if(tabPath === false) tabPath = CurrentTabPath();
   if(tabPath === false) return;
   
   var tabInfo = tab_paths[tabPath];
   if(tabInfo.unsaved == true) return;
   $("ul.ui-tabs-nav li.ui-tabs-selected a").addClass('unsavedTab');
   tabInfo.unsaved = true;
}

// pass tabPath false to use current tab path
// savedAtUndoCount - set to false if never saved
function updateEditCount(tabPath) {
    if(tabPath === false) tabPath = CurrentTabPath();
    if(tabPath === false) return;
    
    var tabInfo = tab_paths[tabPath];
    var currentEdits = tabInfo.session.getUndoManager().$undoStack.length - tabInfo.savedAtUndoCount;
    // alert(tabPath+': unsaved:'+tabInfo.unsaved+' edits:'+currentEdits);
    if(!tabInfo.unsaved && (currentEdits != 0 || tabInfo.savedAtUndoCount === false)) {
      $('ul.ui-tabs-nav li a[href$="#tabs-'+tabInfo.tab+'"]').addClass('unsavedTab');
      tabInfo.unsaved = true;
    } else if(tabInfo.unsaved && currentEdits == 0) {
      $('ul.ui-tabs-nav li a[href$="#tabs-'+tabInfo.tab+'"]').removeClass('unsavedTab');
      tabInfo.unsaved = false;
    }
    $("#currentEdits").html(currentEdits);
}

function SaveCurrentTab() {
  var dp = CurrentTabPath();
  var contents = editor_global.getSession().getValue();
  
  $("#status").html('Saving ' + tab_paths[dp].filename);

  $.ajax({
    type: 'POST',
    url: '?cmd=fileSave',
    data: {'path': dp, 'contents': contents},
    success: function (data, textStatus, jqXHR) {
        $("#status").html('');
        if (data.result == 'bad') {
            alert("Could't save file.");    
        } else {
            markTabSaved(data.path);
        }
    },
    error: function (jqXHR, textStatus, errorThrown) { alert('Error Saving: ' + dp); $("#status").html(''); },
  });
}

function set_all_pref () {
  for (dp in tab_paths) {
    set_edit_pref(tab_paths[dp].session, "editor_" + tab_paths[dp].tab);
  }
  
  $("#kendoStyle").remove();
  $('head').append('<link rel="stylesheet" href="css/kendo_styles/kendo.' + pref.uitheme + '.min.css" id="kendoStyle" type="text/css" />');
}

function set_edit_pref(sess, id) {
  load_theme = true;
  for (i in loaded_themes) {
    if (loaded_themes[i] == pref.theme) {
      load_theme = false;
      break;
    }
  }
  
  if (load_theme) {
    $.ajax({
      url: 'js/ace/src-min/theme-' + pref.theme + '.js',
      dataType: "script",
      async: false,
    });
    loaded_themes.push(pref.theme);
  }
  
  editor_global.setTheme("ace/theme/" + pref.theme);
  editor_global.setScrollSpeed(7); 
  
  /* editor_global.setTheme("/home/hostelz/dev/public-secure/admin/codeEditor/myCustomTheme.js"); (didn't work) */

  var handler = null;
  if (pref.keybind == 'emacs') {
    handler = require("ace/keyboard/keybinding/emacs").Emacs;
  }
  
  else if (pref.keybind == 'vim') {
    handler = require("ace/keyboard/keybinding/vim").Vim;
  }
  
  editor_global.setKeyboardHandler(handler);
  
  editor_global.setHighlightActiveLine(pref.hactive);
  editor_global.setHighlightSelectedWord(pref.hword);
  editor_global.setShowInvisibles(pref.invisibles);
  editor_global.setBehavioursEnabled(pref.behave);
  
  editor_global.renderer.setShowGutter(pref.gutter);
  editor_global.renderer.setShowPrintMargin(pref.pmargin);
  
  sess.setTabSize(pref.tabsize);
  sess.setUseSoftTabs(pref.softab);
  
  switch (pref.swrap) {
    case "off":
      sess.setUseWrapMode(false);
      editor_global.renderer.setPrintMarginColumn(80);
      break;
      
    case "40":
      sess.setUseWrapMode(true);
      sess.setWrapLimitRange(40, 40);
      editor_global.renderer.setPrintMarginColumn(40);
      break;
      
    case "80":
      sess.setUseWrapMode(true);
      sess.setWrapLimitRange(80, 80);
      editor_global.renderer.setPrintMarginColumn(80);
      break;
      
    case "free":
      sess.setUseWrapMode(true);
      sess.setWrapLimitRange(null, null);
      editor_global.renderer.setPrintMarginColumn(80);
      break;
  }
  
  $("#" + id).css('font-size', pref.fontsize);
}

var editor_global = null;
var EditSession = require('ace/edit_session').EditSession;
var UndoManager = require("ace/undomanager").UndoManager;
var editCountTimeout;

function create_tab(data, textStatus, jqXHR, range) {
  if (data.path in tab_paths) {
    $tabs.tabs('select', "#tabs-" + tab_paths[data.path].tab);
  }
  else {
    if (data.fileType == 'text') {
      $tabs.tabs("add", "#tabs-" + tab_counter, data.filename);
      $tabs.tabs('select', "#tabs-" + tab_counter);
      
      if (!editor_global) {
        editor_global = ace.edit("editor_global");
        editor_global.commands.removeCommands([
            'find', 'findnext', 'findprevious', /* we use our own quickfind instead */
            'transposeletters' // control-t interferes with new tab command
        ]); 
        editor_global.commands.bindKey("Ctrl-shift-3", 'gotoline'); /* redefine gotoline as ctrl-# */
        add_commands(editor_global);
      }
      
      var sess = new EditSession(data.data);

      sess.setUndoManager(new UndoManager());
      editor_global.setSession(sess);
           
      if (data.mode) {
        var Mode = require("ace/mode/" + data.mode).Mode;
        sess.setMode(new Mode());
        $("li.ui-tabs-selected").addClass('mode_'+data.mode);
      }
      
      set_edit_pref(sess, "editor_" + tab_counter);
      
      editor_global.resize();
      editor_global.focus();
      
      tab_paths[data.path] = {tab: tab_counter, session: sess, filename: data.filename, uid: data.uid, 
        unsaved: false, savedAtUndoCount: 0};
      tab_counts[tab_counter] = data.path
      
      tab_counter++;
      
      updateCurrentPathDisplay(data.path);
      
        /* this apparently worked just as well:
      sess.addEventListener("change", function () { }); */
      sess.on('change', function () {
          // We have to delay before the update because otherwise the undo count hasn't yet been updated
          if(editCountTimeout) clearTimeout(editCountTimeout); // remove any pending timeouts
          editCountTimeout = setTimeout("updateEditCount(false)", 100); 
      });
      
      if (pref.save_session) {
        save_session();
      }
      
      if (range) {
        sess.getSelection().setSelectionRange(range, false);
      }
     }
    else if (data.fileType == 'binary') {
      alert('binary file');
    }
  }
}

function updateCurrentPathDisplay(path) {
  try {
    /* path = path.replace(basedir + '/', ''); na, just show the whole thing */
    $("#current_path").html(path);
    updateEditCount(false);
  }
  catch (e) {}
}

function resize_editor(skip_splitter) {
  var edith = $(window).height() - 33;
  
  ksplitter.size("#neutron_body", edith + "px");
  $("#splitter, #splitter > div").height(edith - 2);
  
  var nbw = $("#neutron_body").width() + 1;
  $("#neutron_body").width(nbw);
  
  var dp = CurrentTabPath();
  if (dp) {
    updateCurrentPathDisplay(dp);
    if (tab_paths[dp]) {
      editor_global.setSession(tab_paths[dp].session);
    }
  }
  
  var h = $("#splitter_right").height();
  $("#editor_global").height(h - $('.ui-widget-header').outerHeight());
  $("#editor_global").width($("#splitter_right").width());
  
  if (editor_global) {
    editor_global.resize();
    editor_global.focus();
  }
  
  resize_tabs();
}

function resize_tabs() {
  var h = $('#tooltabs').height();
  var t = $('#tooltabs > ul').height() + 2;
  
  $("#tooltabs > div").height(h - t);
}

function CloseTab(tabNum, forceClose) {
  /* var tabNum = tabNumFromHref(ui.tab.href); */
  var dp = tab_counts[tabNum];
  if(tab_paths[dp].unsaved && !forceClose) {
    if(!confirm('"'+dp+'"\n\nClose without saving changes?')) return;
  }
  
  tabIndex = get_ui_tab_index(tabNum);
  $tabs.tabs("remove", tabIndex);
  
  updateCurrentPathDisplay('');
  
  if (tab_paths[dp] && tab_paths[dp].session) {
    tab_paths[dp].session.$stopWorker();
    delete tab_paths[dp].session;
    delete tab_paths[dp];
  }
  
  if (tab_counts[tabNum]) {
    delete tab_counts[tabNum];
  }
  
  if (pref.save_session) {
    save_session();
  }
  
  var dp = CurrentTabPath();
  if (dp) {
    updateCurrentPathDisplay(dp);
    resize_editor();
  }
  
  else {
    editor_global = null;
    $('#editor_global').html('');
  }
}

function CloseAll () {
    if (confirm('Are you sure you wish to close all tabs?')) {
        for(var tabNum in tab_counts) {
            CloseTab(tabNum, false); // pass true if we want to skip the confirm if unsaved prompts
        }
    }
}

function SaveAll() {
  save_win.center();
  save_win.open();
  
  $("#saveall").css('display', 'block');
  $("#saveall").empty();
  for (dp in tab_paths) {
    var contents = tab_paths[dp].session.getValue();
    
    $("#saveall").append('<p id="saveall_' + tab_paths[dp].uid + '">Saving ' + tab_paths[dp].filename + ' ...</p>');
 
    $.ajax({
      type: 'POST',
      url: '?cmd=fileSave',
      data: {'path': dp, 'contents': contents},
      success: function (data, textStatus, jqXHR) {
        $("#saveall_" + data.uid).remove();
        markTabSaved(data.path);
        if (data.result == 'bad') {
          alert(data.error);
        }
        if ($('#saveall').children().size() == 0) {
          save_win.close();
        }
      },
      error: function (jqXHR, textStatus, errorThrown) { 
         alert('Error Saving: ' + dp); $("#status").html('');
        $("#saveall_" + data.uid).remove();
        if ($('#saveall').children().size() == 0) {
         save_win.close();
        }
      },
    });
  }
}

function uploadProgress(id, evt) {
  if (evt.lengthComputable) {
    var pc = Math.round(evt.loaded * 100 / evt.total);
    
    $('#span_' + id).html('Uploading ' + pc + '%');
  }
}

function uploadFile(id, onComplete) {
  var xhr = new XMLHttpRequest();
  var fd = document.getElementById(id).files[0];
  
  xhr.upload.addEventListener("progress", function (evt) { uploadProgress(id, evt); }, false);
  xhr.addEventListener("load", function (evt) { onComplete(evt); }, false);
  xhr.addEventListener("error", function (evt) { alert('Upload Failed'); }, false);
  xhr.addEventListener("abort", function (evt) { alert('Upload Cancel'); }, false);
  
  xhr.open("POST", "?cmd=tempUpload&name=" + encodeURIComponent(fd.fileName));
  xhr.setRequestHeader("X-CSRFToken", getCookie('csrftoken'));
  xhr.send(fd);
}

function get_file(file, range) {
    $.ajax({
		type: 'POST',
		async: false,
		url: '?cmd=fileGet',
		data: {f: file},
		success: function (data, textStatus, jqXHR) { create_tab(data, textStatus, jqXHR, range); },
		error: function (jqXHR, textStatus, errorThrown) { alert('Error Opening: ' + file); },
	});
}

function file_browser(dir) {
	$('#file_browser > div.inner').load('?cmd=fileTree', {'dir': dir});
}

function save_session() {
  if (skip_session) 
    {}
  else {
    var files = '';
    $("ul.ui-tabs-nav > li > a").each(function (index, ele) {
      var tabNum = tabNumFromHref(ele.href);
      var dp = tab_counts[tabNum];
      files = files + dp + "\n";
    });
    
    files = files.substring(0, files.length-1);
    
    $.ajax({
      type: 'POST',
      url: '?cmd=saveSession',
      data: {'files': files},
      success: function (data, textStatus, jqXHR) {},
      error: function (jqXHR, textStatus, errorThrown) { alert('Error Saving Session'); },
    });
  }
}

function sort_change(event, ui) {
  if (pref.save_session) {
    save_session();
  }
}

var stopMiddle = false;
function middleClick(e, ele) {
  if (e && e.button == 1) {
    if (stopMiddle) {
      stopMiddle = false;
    }
    
    else {
      var p = $(ele).parent();
      if ($(p).hasClass('ui-tabs-selected')) {
        stopMiddle = true;
      }
      CloseTab(tabNumFromHref($(ele).attr("href")), false); 
      return false;
    }
  }
  return false; // always stop the event
  /* return true; */
}

$(document).ready( function() {
    file_browser('');
    
    $tabs = $("#tabsinner").tabs({
      tabTemplate: "<li><a class='middle' href='#{href}' onmousedown='return middleClick(event, this)'>#{label}</a></li>", /*  <span class='ui-icon ui-icon-close'><sup>x</sup></span> */
      show: function( event, ui) { resize_editor(); },
			add: function( event, ui) {
        $(ui.panel).append( "<div class=\"editor\" id=\"editor_" + tab_counter + "\"></div>" );
      },
      /* remove: function (event, ui) { remove_tab(ui); } */
    });
    /* tab dragging (not working well) $tabs.find( ".ui-tabs-nav" ).sortable({ axis: "x", update: sort_change}); */
    $( "#tabs span.ui-icon-close" ).live( "click", function() {
      var p = $(this).parent();
      var tabNum = tabNumFromHref( $("a",p).attr('href') );
      CloseTab(tabNum, false);
    });
});

function size_search (e) {
  setTimeout(function () {
    var width = $("#splitter_left").width() - 83;
    $("#search_panel_results a.expand").width(width);
    
    $("#editor_global").width($("#splitter_right").width());
  
    if (editor_global) {
      editor_global.resize();
    }
  }, 400);
}

var ksplitter;
var esplitter;
var tabstrip;
var tooltabs;
var search_panel;
var grep_panel;

$(document).ready(function () {
  $("body").click(function (eObj) {
    $('.right_menu').css('display', 'none');
    
    if (eObj.target.id && eObj.target.id == 'menu_button') {}
    else if (eObj.target.parentElement.id && eObj.target.parentElement.id == 'menu_button') {}
    else {
      hide_menu();
    }
  });
  
  search_panel = $("#search_panel").kendoPanelBar().data("kendoPanelBar");
  $("#search_panel_search > span").click(); /* open the top panel */
  
  grep_panel = $("#grep_panel").kendoPanelBar().data("kendoPanelBar");
  $("#grep_panel_search > span").click();  /* open the top panel */

  tooltabs = $("#tooltabs").kendoTabStrip({animation: false}).data("kendoTabStrip");
  tooltabs.select("#fbtab");
  $("#tooltabs > ul li").click(resize_tabs);
  
  ksplitter = $("#neutron_ui").kendoSplitter({
    panes: [{resizable: false, size: '35px', scrollable: false}, {scrollable:false, resizable: false, size: '311px'}],
    orientation: 'vertical'
  }).data("kendoSplitter");
  
  esplitter = $("#splitter").kendoSplitter({resize: size_search, panes: [{collapsible: true, size: '250px', scrollable: false}, {scrollable: false}], resize: size_search}).data("kendoSplitter");
  
  if (pref.save_session) {
    for (i in init_session) {
      get_file(init_session[i]);
    }
    
    skip_session = false;
  }
  
  //$(window).unbind();
  $(window).resize(resize_editor);
  resize_editor();
  
  if (track_ajax) {
    setTimeout(track_ide, track_int);
  }
});

function track_ide () {
  var d = new Date();
  var ts = d.getYear() + '-' + d.getMonth() + '-' + d.getDay() + '-' + d.getHours() + '-' + d.getMinutes();
  _gaq.push(['_trackPageview', "/?ts=" + ts]);
  setTimeout(track_ide, track_int);
}

window.onbeforeunload = function() {
    return 'May be unsaved changes!';
}

function change_path() {
    var currentPath = CurrentTabPath();
    var newPath = prompt("Path:", currentPath);
    if(newPath == null || newPath == '' || currentPath == newPath) return;

    try  {
        /* set tab_counts[] info */
        var tabNum = CurrentTabNum();
        tab_counts[tabNum] = newPath;
        
        /* set tab_paths[] info */
        var tabInfo = tab_paths[currentPath];
        delete tab_paths[currentPath];
        tabInfo.path = newPath;
        tabInfo.filename = newPath.replace(/^.*\//, '');
        tab_paths[newPath] = tabInfo;
        
        /* update tab / path display */
        $("ul.ui-tabs-nav li.ui-tabs-selected a").html(tabInfo.filename);
        tabInfo.savedAtUndoCount = false; // false indicates it is marked as unsaved
        updateCurrentPathDisplay(newPath);
    } catch (e) {
        return;
    }
}

var untitledCount = 0;

function blank_tab() {
    untitledCount++;
    create_tab({path: basedir+'/untitled'+untitledCount, filename: 'untitled'+untitledCount, fileType: 'text', data: '', mode: 'php', uid: 'untitled'+untitledCount});
}

function add_commands (e) {
  e.commands.addCommand({
      name: 'SaveFile',
      bindKey: {
        win: 'Ctrl-S',
        mac: 'Command-S',
        sender: 'editor'
      },
      exec: function(env, args, request) { SaveCurrentTab(); }
  });
  
  e.commands.addCommand({
      name: 'QuickSearch',
      bindKey: {
        win: 'Ctrl-F',
        mac: 'Command-F',
        sender: 'editor'
      },
      exec: function(env, args, request) { $('#quick_search').focus().select(); }
  });
  
e.commands.addCommand({
      name: 'QuickFilenameSearch',
      bindKey: {
        win: 'Ctrl-shift-F',
        mac: 'Command-shift-F',
        sender: 'editor'
      },
      exec: function(env, args, request) { $('#quick_filesearch').focus().select(); }
  });
  
  e.commands.addCommand({
    name: 'SaveAllFile',
    bindKey: {
      win: 'Ctrl-shift-S',
      mac: 'Command-shift-S',
      sender: 'editor'
    },
    exec: function(env, args, request) { SaveAll(); }
  });

  e.commands.addCommand({
    name: 'CloseFile',
    bindKey: {
      win: 'Ctrl-shift-X',
      mac: 'Command-shift-X',
      sender: 'editor'
    },
    exec: function(env, args, request) { CloseTab(CurrentTabNum(), false); }
  });
}
