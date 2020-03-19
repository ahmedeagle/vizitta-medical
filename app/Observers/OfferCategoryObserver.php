<?php

namespace App\Observers;

use App\Models\OfferCategory;
use Illuminate\Support\Facades\Cache;

class OfferCategoryObserver
{
    /**
     * Listen to the Entry deleting event.
     *
     * @param Category $category
     * @return void
     */
    public function deleting(OfferCategory $category)
    {
    }

    /**
     * Listen to the Entry saved event.
     *
     * @param Category $category
     * @return void
     */
    public function saved(OfferCategory $category)
    {
        // Removing Entries from the Cache
        $this->clearCache($category);
    }

    /**
     * Listen to the Entry deleted event.
     *
     * @param Category $category
     * @return void
     */
    public function deleted(OfferCategory $category)
    {
        // Removing Entries from the Cache
        $this->clearCache($category);
    }

    /**
     * Removing the Entity's Entries from the Cache
     *
     * @param $category
     */
    private function clearCache($category)
    {
        Cache::flush();
    }
}
