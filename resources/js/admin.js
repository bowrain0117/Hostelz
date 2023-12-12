$(document).ready(function() {
  var wrap = $('#showLaravelErrors');
  if (wrap.length === 0) {
    return true;
  }

  wrap.html(wrap.html().replace(/^\[[^\]]+]([^{(\[]+)/gm, function(match, p) {
    return match.replace(p, '<b>' + p + '</b><br/>');
  }))

  wrap.html(wrap.html().replaceAll('.WARNING:', '.<span style="background-color: yellow">WARNING</span>:'));
  wrap.html(wrap.html().replaceAll('.NOTICE:', '.<span style="background-color: blue; color: white;">NOTICE</span>:'));
  wrap.html(wrap.html().replaceAll('.ERROR:', '.<span style="background-color: red; color: white;">ERROR</span>:'));
});