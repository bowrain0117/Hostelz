<?php

use App\Enums\CategorySlp;

return [
    'categories' => [
        'breadcrumb' => [
            CategorySlp::Best->value => 'Best Hostels',
            CategorySlp::Private->value => 'Hostels with Private Rooms',
            CategorySlp::Cheap->value => 'Cheapest Hostels',
            CategorySlp::Party->value => 'Party Hostels',
        ],
        'title' => [
            CategorySlp::Best->value => 'Best Hostels Guides',
            CategorySlp::Private->value => 'Hostels with Private Rooms',
            CategorySlp::Cheap->value => 'Cheap Hostels around the World',
            CategorySlp::Party->value => 'Party Hostels around the World',
        ],
        'meta-title' => [
            CategorySlp::Best->value => 'BEST Hostels In the World [year] - by Hostelz.com',
            CategorySlp::Private->value => 'BEST Hostels in the World with Private rooms ([year])',
            CategorySlp::Cheap->value => 'CHEAPEST Hostels in the World [year] — Compared by Price and Rating',
            CategorySlp::Party->value => 'Crazy PARTY Hostels in the World [year] — Legendary Parties you won’t remember',
        ],
        'meta-desc' => [
            CategorySlp::Best->value => 'Hostelz shares the BEST hostels in the world based on ratings and prices; from Paris to Tokyo. Compare hostels at a glance and pick better hostels.',
            CategorySlp::Private->value => 'A great selection of the best hostels with private rooms: From Double Room to Family Rooms.',
            CategorySlp::Cheap->value => 'Find the CHEAPEST Hostels in the World [year]. Always compare hostel prices with Hostelz.com to save real money.',
            CategorySlp::Party->value => 'Find the COOLEST Party Hostels in the World [year]. Always compare hostel prices with Hostelz.com',
        ],
    ],
];
