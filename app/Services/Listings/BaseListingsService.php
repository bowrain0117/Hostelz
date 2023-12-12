<?php

namespace App\Services\Listings;

class BaseListingsService
{
    protected array $viewData;

    /**
     * @param array $viewData
     */
    public function setViewData(array $viewData): void
    {
        if (empty($this->viewData)) {
            $this->viewData = $viewData;
        }

        $this->viewData = array_merge($this->viewData, $viewData);
    }

    /**
     * @return array
     */
    public function getViewData(): array
    {
        return $this->viewData;
    }
}
