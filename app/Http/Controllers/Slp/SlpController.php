<?php

namespace App\Http\Controllers\Slp;

use App\Enums\CategorySlp;
use App\Http\Controllers\Controller;
use App\Models\SpecialLandingPage;
use App\Schemas\AuthorSchema;

class SlpController extends Controller
{
    public function bestIndex()
    {
        return $this->index(CategorySlp::Best);
    }

    public function partyIndex()
    {
        return $this->index(CategorySlp::Party);
    }

    public function privateIndex()
    {
        return $this->index(CategorySlp::Private);
    }

    public function cheapIndex()
    {
        return $this->index(CategorySlp::Cheap);
    }

    public function index(CategorySlp $category)
    {
        return view('slp.index', ['category' => $category]);
    }

    public function privateShow(string $slug)
    {
        return $this->show(CategorySlp::Private, $slug);
    }

    public function bestShow(string $slug)
    {
        return $this->show(CategorySlp::Best, $slug);
    }

    public function partyShow(string $slug)
    {
        return $this->show(CategorySlp::Party, $slug);
    }

    public function cheapShow(string $slug)
    {
        return $this->show(CategorySlp::Cheap, $slug);
    }

    public function show(CategorySlp $category, $slug)
    {
        try {
            $slp = SpecialLandingPage::query()
                ->publishBySlug($category, $slug)
                ->firstOrFail();
        } catch (\Throwable $e) {
            abort(404);
        }

        $schema = AuthorSchema::for($slp->author)->getSchema();

        return view('slp.show', compact('slp', 'schema'));
    }
}
