@if (auth()->user()->hasPermission('admin'))
    <h4>API Documentation</h4>
    <ul>
        <li>
            <a href="https://partner-api.hostelworld.com/" target="_blank">API Hostelworld</a>
            <ul>
                <li><a href="https://api-docs.partnerize.com/partner/" target="_blank">Partnerize Partners API</a></li>
                <li><a href="/pdf/Hostelworld-API.pdf" target="_blank">old pdf API Hostelworld</a></li>
            </ul>
        </li>
        <li><a href="https://developers.booking.com/api/technical.html?version=2.8#!/Availability/blockAvailability"
               target="_blank">API booking.com</a></li>
        <li><a href="/pdf/Hostelsclub-API.pdf" target="_blank">API Hostelsclub</a></li>
    </ul>
@endif
