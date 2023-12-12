@if (@$plainText)
    @yield('content')
@else
    <!DOCTYPE html>
    <html lang="en-US">
        <head>
            <meta charset="utf-8">
            <style type="text/css">
                {{-- Some ideas based on ideas from http://htmlemailboilerplate.com/ --}}
    
        		{{-- Forces Hotmail to display normal line spacing.  More on that: http://www.emailonacid.com/forum/viewthread/43/ --}} 
        		#backgroundTable {margin:0; padding:0; width:100% !important; line-height: 100% !important;}
                    
        		{{-- Some sensible defaults for images --}}
        		img {outline:none; text-decoration:none; -ms-interpolation-mode: bicubic;} 
        		a img {border:none;} 
        		.image_fix {display:block;}
        
        		{{-- Yahoo paragraph fix --}}
        		p {margin: 1em 0;}
            </style>
        </head>
    	<body>
            @yield('content')
    	</body>
    </html>
@endif
