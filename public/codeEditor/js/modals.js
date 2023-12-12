var new_stuff_win;

$(document).ready(function () {
  new_stuff_win = $("#new_stuff").kendoWindow({modal: true, width: "500px"}).data("kendoWindow");
});

function new_stuff(workingPath) {
  close_right();
  
  new_stuff_win.title("New File/Dir in " + workingPath);
  new_stuff_win.center();
  new_stuff_win.open();
  
  $("#ui-dialog-title-new_stuff").html("New File/Dir in " + workingPath);
  $('#span_new_upload_file').html('');
  $("#create_new_dpath").val(workingPath);
  /* $("#create_new_did").val(did); */
  $('#new_stuff input').removeAttr('disabled');
  $("#new_file").focus().select();
}

function create_new() {
  $('#new_stuff input').attr("disabled", "disabled");
  var new_type = $('input:radio[name=new_type]:checked').val();
  
  if (new_type == 'up') {
    uploadFile('new_upload_file', function (evt) {
      var data = $.parseJSON(evt.target.responseText);
      $("#temp_file").val(data.message);
      do_create_ajax();
    })
  }
  else {
    do_create_ajax();
  }
  
  return false;
}

function do_create_ajax() {
  var workingPath = $("#create_new_dpath").val();
  var new_type = $('input:radio[name=new_type]:checked').val();
  if(new_type == 'up')
    var name = document.getElementById('new_upload_file').files[0].fileName;
  else 
    var name = $("#new_file").val();
  var temp_file = $("#temp_file").val();
  
  $.ajax({
     type: "POST",
     dataType: 'json',
     url: "?cmd=new",
     data: {dir: workingPath, new_type: new_type, name: name, temp_file: temp_file},
     success: function (data, textStatus, jqXHR) {
        new_stuff_win.close();
        file_browser(workingPath); /* refresh */
     },
     error: function (jqXHR, textStatus, errorThrown) {
        alert('Error creating new file/directory.');
     }
  });
  
}

function close_right() {
  $(".right_menu").css('display', 'none');
}

function show_new_fn () {
  $("#new_filename").css('display', 'block');
  $("#new_upload").css('display', 'none');
}

function show_new_upload () {
  $("#new_filename").css('display', 'none');
  $("#new_upload").css('display', 'block');
}

function delete_me(workingPath, filePath) {
  if (confirm('Are you sure you want to delete: ' + filePath + '?')) {
    $.ajax({
       type: "POST",
       dataType: 'json',
       url: "?cmd=delete",
       data: {dir: filePath},
       success: function (data, textStatus, jqXHR) {
         file_browser(workingPath); /* refresh */
       },
       error: function (jqXHR, textStatus, errorThrown) {
         alert('Error deleting ' + filePath);
       }
    });
  }
  close_right();
}

function rename(workingPath, filePath) {
  var newPath = prompt('Rename', filePath);
  if (newPath) {
    $.ajax({
       type: "POST",
       dataType: 'json',
       url: "?cmd=rename",
       data: {dir: filePath, name: newPath},
       success: function (data, textStatus, jqXHR) {
         if (data.result) {
          file_browser(workingPath); /* refresh */
         }
         
         else {
           alert(data.message);
         }
       },
       error: function (jqXHR, textStatus, errorThrown) {
         alert('Error renaming ' + filePath);
       }
    });
  }
  close_right();
}
