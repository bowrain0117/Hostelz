<?php

namespace App\Models;

use App\Enums\CategorySlp;
use App\Enums\StatusSlp;
use App\Lib\Common\Images\Image;
use App\Models\Listing\Listing;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Pipeline;
use Lib\BaseModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SpecialLandingPage extends BaseModel implements HasMedia
{
    use HasFactory, InteractsWithMedia, HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => StatusSlp::class,
        'category' => CategorySlp::class,
        'created_at' => 'date:Y-m-d',
        'updated_at' => 'date:Y-m-d',
    ];

    protected $attributes = [
        'language' => 'en',
        'slug' => '',
        'meta_title' => '',
        'meta_description' => '',
        'notes' => '',
    ];

    public const FEATURED_HOSTELS = 11;

    protected static function booted(): void
    {
        static::updated(function (self $slp) {
            cache()->tags(["slp:$slp->id"])->flush();
        });
    }

    /* Accessors & Mutators */

    protected function slugId(): Attribute
    {
        return Attribute::make(
            get: fn () => str()->slug($this->meta->title)
        )->shouldCache();
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category ? route('slp.show.' . $this->category->value, [$this->slug]) : null,
        )->shouldCache();
    }

    protected function pathEdit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->category ? route('slpStaff:edit', [$this->id]) : null,
        )->shouldCache();
    }

    protected function meta(): Attribute
    {
        return Attribute::make(
            get: fn ($title) => (object) [
                'title' => $this->replaceShortcodes($this->title),
                'meta_title' => $this->replaceShortcodes($this->meta_title),
                'meta_description' => $this->replaceShortcodes($this->meta_description),
            ],
        )->shouldCache();
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMedia('mainPic')?->getFullUrl(),
        )->shouldCache();
    }

    protected function mainPic(): Attribute
    {
        return Attribute::make(
            get: fn () => Image::create($this->getFirstMedia('mainPic'), $this->meta->title),
        )->shouldCache();
    }

    protected function uniqueTextListingsNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->hostels
                ->filter(function (Listing $listing) {
                    return $listing->slp_text_exists;
                })
                ->count(),
        )->shouldCache();
    }

    protected function hostels(): Attribute
    {
        return Attribute::make(
            get: fn () => Listing::query()
                ->hostelsForCategory($this->category, $this->subjectable)
                ->withSlpTextExists($this->category)
                ->take($this->number_featured_hostels)
                ->get()
        )->shouldCache();
    }

    protected function isPublished(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === StatusSlp::Publish
        );
    }

    /*  scopes  */

    public function scopePublishBySlug(Builder $query, CategorySlp $category, $slug)
    {
        $query->where([
            ['slug', $slug],
            ['category', $category],
            ['language', 'en'],
        ])
            ->published()
            ->with('subjectable');
    }

    public function scopePublished(Builder $query)
    {
        $query->whereStatus(StatusSlp::Publish);
    }

    public function scopeForCity(Builder $query, CityInfo|string|int|null $city)
    {
        $query->whereHasMorph(
            'subjectable',
            CityInfo::class,
            fn (Builder $query) => $query->when(is_null($city) || is_string($city), fn ($q) => $q->whereCity($city))
                ->when(is_int($city), fn ($q) => $q->whereId($city))
                ->when(is_a(CityInfo::class, $city), fn ($q) => $q->whereId($city->id))
        );
    }

    /*  relations  */

    public function author(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subjectable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function faqs(): MorphMany
    {
        return $this->morphMany(Faq::class, 'subjectable');
    }

    /*  custom  */

    public function replaceShortcodes(string $data)
    {
        return Pipeline::send($data)
            ->through([
                fn (string $data, Closure $next) => $next(str_replace('[city]', $this->subjectable?->city, $data)),
                fn (string $data, Closure $next) => $next(str_replace('[number]', $this->number_featured_hostels, $data)),
                fn (string $data, Closure $next) => $next(replaceShortcodes($data)),
            ])
            ->thenReturn();
    }

    public function bestHostel()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingBetter($carry) ? $listing : $carry;
        });
    }

    public function bestPrivateHostel()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingPrivateBetter($carry) ? $listing : $carry;
        });
    }

    public function femaleSoloTraveller()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (! $listing->isFemaleSoloTraveller()) {
                return $carry;
            }

            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingBetter($carry) ? $listing : $carry;
        });
    }

    public function soloTraveler()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (! $listing->isSoloTraveler()) {
                return $carry;
            }

            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingBetter($carry) ? $listing : $carry;
        });
    }

    public function families()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (! $listing->isFamilies()) {
                return $carry;
            }

            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingBetter($carry) ? $listing : $carry;
        });
    }

    public function familiesPrivate()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (! $listing->isFamilies()) {
                return $carry;
            }

            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingPrivateBetter($carry) ? $listing : $carry;
        });
    }

    public function couples()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (! $listing->isCouples()) {
                return $carry;
            }

            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingBetter($carry) ? $listing : $carry;
        });
    }

    public function couplesPrivate()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (! $listing->isCouples()) {
                return $carry;
            }

            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingPrivateBetter($carry) ? $listing : $carry;
        });
    }

    public function parting()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (! $listing->isParting()) {
                return $carry;
            }

            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isRatingBetter($carry) ? $listing : $carry;
        });
    }

    public function cheapest()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isPriceLower($carry) ? $listing : $carry;
        });
    }

    public function cheapestPrivate()
    {
        return $this->hostels->reduce(function (Listing|null $carry, Listing $listing) {
            if (is_null($carry)) {
                return $listing;
            }

            return $listing->isPricePrivateLower($carry) ? $listing : $carry;
        });
    }

    public function hasListing($listingId): bool
    {
        return $this->hostels->contains('id', $listingId);
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('mainPic')
            ->singleFile()
            ->registerMediaConversions(function (Media $media) {
                $this
                    ->addMediaConversion('tiny')
                    ->fit('fill', 10, 10);

                $this
                    ->addMediaConversion('thumbnail')
                    ->fit('fill', 450, 300);

                $this->addMediaConversion('webp_thumbnail')
                    ->format('webp')
                    ->fit('fill', 450, 300)
                    ->background('FFFFFF');

                $this->addMediaConversion('webp_big')
                    ->format('webp');
            });
    }
}
