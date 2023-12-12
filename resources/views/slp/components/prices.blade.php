@props(['price'])

@php
    use App\Lib\Listings\ListingPrices;
        /** @var ListingPrices $price */
@endphp

@if($price->dorm->isset() && $price->private->isset())
    <p>
        Prices start from {{ $price->dorm->min->formated }} for a dorm
        and {{ $price->private->min->formated }} for a private room.
    </p>
@elseif($price->dorm->isset())
    <p>
        Prices start from {{ $price->dorm->min->formated }} for a dorm.
    </p>
@elseif($price->private->isset())
    <p>
        Prices start from {{ $price->private->min->formated }} for a private room.
    </p>
@endif