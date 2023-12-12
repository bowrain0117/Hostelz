<?php

namespace App\Models;

use App\Enums\District\Type;
use App\Models\Listing\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Lib\BaseModel;
use Lib\GeoPoint;

/**
 * @property GeoPoint $geoPoint
 * @property CityInfo $city
 * @property Collection $neighborhoods
 */
class District extends BaseModel
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $attributes = [
        'cityId' => null,
        'type' => 'in',
        'is_active' => false,
        'is_city_centre' => false,
        'description' => '',
    ];

    protected $casts = [
        'type' => Type::class,
        'is_active' => 'bool',
        'is_city_centre' => 'bool',
        'created_at' => 'date:Y-m-d',
        'updated_at' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $district) {
            $district->slug = $district->getSlug();
        });

        static::saved(function (self $district) {
            $district->city->clearRelatedPageCaches();
        });
    }

    public function city()
    {
        return $this->belongsTo(CityInfo::class, 'cityId');
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'subjectable');
    }

    /*  Scopes  */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByFullLocation($query, $country, $city, $slug)
    {
        return $query->where('slug', $slug)
            ->active()
            ->whereHas(
                'city',
                fn (Builder $query) => $query->fromUrlParts(country: $country, city: $city)
            );
    }

    public function getSlug(): string
    {
        return str(
            $this->type === Type::In ? $this->name : 'near-' . $this->name,
        )->slug()->value();
    }

    public function title(): Attribute
    {
        return Attribute::make(
            get: fn () => sprintf(
                'Hostels %s %s, %s',
                $this->type->value,
                $this->name,
                $this->city->city,
            ),
        );
    }

    public function path(): Attribute
    {
        return Attribute::make(
            get: fn () => route(
                'city',
                [
                    'country' => replaceUrlCharWith($this->city->country),
                    'region' => replaceUrlCharWith($this->city->city),
                    'city' => $this->slug,
                ]),
        );
    }

    public function pathEdit(): Attribute
    {
        return Attribute::make(
            get: fn () => route('staff:district:edit', $this->id),
        );
    }

    public function geoPoint(): Attribute
    {
        return Attribute::make(
            get: fn () => new GeoPoint($this),
        );
    }

    public function neighborhoods(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->city
                ?->districts()
                ->where('id', '!=', $this->id)
                ->active()
                ->orderBy('name')
                ->get(),
        );
    }

    public function hostels()
    {
        $listings = Listing::query()
            ->select(['id', 'name', 'city', 'address', 'latitude', 'longitude'])
            ->selectRaw(
                '(ST_Distance_Sphere(point(longitude, latitude), point(?,?)) * 0.001 * 0.621371192) as distanseInMiles',
                [
                    $this->longitude,
                    $this->latitude,
                ]
            )
            ->selectRaw(
                '(ST_Distance_Sphere(point(longitude, latitude), point(?,?)) * 0.001) as distanseInKm',
                [
                    $this->longitude,
                    $this->latitude,
                ]
            )
            ->areLive()
            ->with(['cityInfo'])
            ->byCityInfo($this->city)
            ->orderBy('distanseInKm')
            ->take(10)
            ->get();

        return $listings->toArray();
    }
}
