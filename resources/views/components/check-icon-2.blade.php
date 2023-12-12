@props(['checked' => false])

@include('partials.svg-icon', ['svg_id' => $checked ? 'green-check' : 'red-restriction', 'svg_w' => '24', 'svg_h' => '24'])