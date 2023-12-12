@foreach ($macros as $macroCategory => $categoryMacros)
    <b>{!! $macroCategory !!}</b>
    @foreach ($categoryMacros as $macro)
    	<a href="#" data-macro-text="{{{ $macro->macroText }}}">{{{ $macro->name }}}</a>
    @endforeach 
@endforeach
