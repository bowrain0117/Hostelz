<?php

/*
    This extends the LengthAwarePaginator class to show less numbered links when there are a lot of pages,
    and also to use our own special URL (just '#' and the page number, which is used by the Javascript code).
*/

namespace App\Helpers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\HtmlString;

class ListingsPaginator extends LengthAwarePaginator
{
    // Override the render() function from LengthAwarePaginator so we can use a custom UrlWindow

    public function render($view = null, $data = [])
    {
        $window = $this->getCustomUrlWindow();

        $elements = [
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ];

        return new HtmlString(static::viewFactory()->make($view ?: static::$defaultView, [
            'paginator' => $this,
            'elements' => array_filter($elements),
        ])->render());
    }

    // Override the url() function from AbstractPaginator so we can use our own special URL (just '#' and the page number, which is used by the Javascript code)

    public function url($page)
    {
        if ($page <= 0) {
            $page = 1;
        }

        return '#' . $page;
    }

    // **
    // ** Private Methods
    // **

    private function getCustomUrlWindow()
    {
        $page = $this->currentPage();
        $pages = $this->lastPage();

        // No pages
        if ($pages === 0) {
            return [
                'first' => null,
                'slider' => null,
                'last' => null,
            ];
        }

        // Small number of pages (show all)
        if ($pages <= 6) {
            return [
                'first' => $this->pageRange(1, $pages),
                'slider' => null,
                'last' => null,
            ];
        }

        // Current page near the beginning
        if ($page <= 4) {
            return [
                'first' => $this->pageRange(1, $page + 1),
                'slider' => null,
                'last' => $this->pageRange($pages, $pages),
            ];
        }

        // Current page near the end
        if ($page >= $pages - 3) {
            return [
                'first' => $this->pageRange(1, 1),
                'slider' => null,
                'last' => $this->pageRange($page - 1, $pages),
            ];
        }

        // Current page in the middle span
        return [
            'first' => $this->pageRange(1, 1),
            'slider' => $this->pageRange($page - 1, $page + 1),
            'last' => $this->pageRange($pages, $pages),
        ];
    }

    private function pageRange($from, $to)
    {
        $return = [];
        for ($i = $from; $i <= $to; $i++) {
            $return[$i] = $this->url($i);
        }

        return $return;
    }
}
