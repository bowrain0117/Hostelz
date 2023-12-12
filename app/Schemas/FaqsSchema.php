<?php

namespace App\Schemas;

use Illuminate\Support\Collection;
use Spatie\SchemaOrg\FAQPage;
use Spatie\SchemaOrg\Schema;

class FaqsSchema
{
    public function __construct(
        private Collection $faqs
    ) {
    }

    public static function for(Collection $items)
    {
        return new static($items);
    }

    public function getSchema(): FAQPage
    {
        return Schema::fAQPage()
            ->mainEntity(
                $this->faqs->map(fn ($item) => Schema::question()->name(clearTextForSchema($item['question']))
                        ->acceptedAnswer(Schema::answer()->text(clearTextForSchema($item['answer'])))
                )->toArray()
            );
    }
}
