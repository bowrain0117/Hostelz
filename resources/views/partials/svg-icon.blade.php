<span @isset($class) class="{{$class}}" @endisset style="width: {{ $svg_w }}px; height: {{ $svg_h }}px;">
    <svg width="{{ $svg_w }}" height="{{ $svg_h }}"><use xlink:href="#{{ $svg_id }}"></use></svg>
</span>