<?php

namespace App\Schemas;

use App\Models\User;
use Spatie\SchemaOrg\Person;
use Spatie\SchemaOrg\Schema;

class AuthorSchema
{
    public function __construct(
        private User $author
    ) {
    }

    public static function for(User $author)
    {
        return new static($author);
    }

    public function getSchema(): Person
    {
        return Schema::person()
            ->name($this->author->nickname)
            ->if(
                $this->author->isPublic,
                fn (Person $schema) => $schema->url($this->author->path_public_page)
            )
            ->if(
                $this->author->image,
                fn (Person $schema) => $schema->image($this->author->image)
            )
            ->jobTitle('Author')
            ->worksFor(
                Schema::organization()
                    ->name(config('custom.globalDomain'))
                    ->url(route('home'))
            );
    }
}
