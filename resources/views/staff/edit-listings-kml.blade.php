<?xml version="1.0" encoding="UTF-8"?>

<kml xmlns="http://earth.google.com/kml/2.1">
    <Document>
        @foreach ($formHandler->list as $listing)
            @if ($listing->hasLatitudeAndLongitude())
                <Placemark><Point><coordinates>{!! $listing->longitude !!},{!! $listing->latitude !!}</coordinates></Point>
                <name>{!! htmlspecialchars($listing->name, ENT_XML1, 'UTF-8') !!} ({!! $listing->formatCombinedRating() !!}) {!! htmlspecialchars($listing->address, ENT_XML1, 'UTF-8') !!}</name>
                <description>{!! htmlspecialchars($listing->address, ENT_XML1, 'UTF-8') !!}</description>
                </Placemark>
            @endif 
        @endforeach 
    </Document>
</kml>
