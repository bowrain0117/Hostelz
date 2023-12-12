<?php

namespace App\Lib\Slp;

use Illuminate\Support\Collection;

class EditorShortcodes
{
    public static function create(): self
    {
        return new static();
    }

    public function replaceShortcodes(string $content, callable|null $callback)
    {
        if (! is_null($callback)) {
            $content = $callback($content);
        }

        return $this->getList()
                    ->reduce(fn ($content, $item) => $this->replace($item, $content), $content);
    }

    private function replace($item, $content)
    {
        return str_replace($item['shortcode'], $item['template'], $content);
    }

    public function getList(): Collection
    {
        return collect([
            [
                'name' => 'Short List',
                'shortcode' => '[slp:ListHostels]',
                'template' => '<x-slp.shortcodes.list-hostels-best :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Slider',
                'shortcode' => '[slp:SliderHostels]',
                'template' => '<x-slp.shortcodes.slider-hostels :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Card List',
                'shortcode' => '[slp:CardHostels]',
                'template' => '<x-slp.shortcodes.card-hostels-best :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Compare Table',
                'shortcode' => '[slp:CompareTable]',
                'template' => '<x-slp.shortcodes.compare-table :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Average Price Graph',
                'shortcode' => '[slp:AveragePriceGraph]',
                'template' => '<x-slp.shortcodes.average-price-graph :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Top Hostel',
                'shortcode' => '[slp:TopHostel]',
                'template' => '<x-slp.shortcodes.top-hostel :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'When To Book',
                'shortcode' => '[slp:WhenBook]',
                'template' => '<x-slp.shortcodes.when-book :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'FAQ',
                'shortcode' => '[slp:FAQ]',
                'template' => '<x-slp.shortcodes.faq :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Private Hostels List',
                'shortcode' => '[slp:PrivateHostels]',
                'template' => '<x-slp.shortcodes.card-hostels-private :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Cheap Hostels List',
                'shortcode' => '[slp:CheapHostelsList]',
                'template' => '<x-slp.shortcodes.list-hostels-cheap :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'Cheap Hostels Card List',
                'shortcode' => '[slp:CheapHostelsCard]',
                'template' => '<x-slp.shortcodes.card-hostels-cheap :slp="$slp" :hostels="$hostels" />',
            ],
            [
                'name' => 'year',
                'shortcode' => '[year]',
                'template' => now()->format('Y'),
            ],
            [
                'name' => 'month',
                'shortcode' => '[month]',
                'template' => now()->format('F'),
            ],
        ]);
    }
}
