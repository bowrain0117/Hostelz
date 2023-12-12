<div>
    {{-- load svg icons --}}
    <div id="svg-container" style="display: none"></div>
    <script>
      !function() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "{!! routeURL('images', 'icons.svg', 'absolute') !!}");
        xhr.onload = function() {
          document.getElementById('svg-container').innerHTML = xhr.responseText;
        }
        xhr.send();
      }();
    </script>
</div>