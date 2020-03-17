<?php
namespace App\Observers;

use App\Models\PromoCode;
use App\Models\PromoCode_branch;
use App\Models\PromoCodeCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class PromoCodeCategoryObserver
{
    /**
     * Listen to the Entry deleting event.
     *
     * @param Category $category
     * @return void
     */
    public function deleting(PromoCodeCategory $category)
    {
    }

    /**
     * Listen to the Entry saved event.
     *
     * @param Category $category
     * @return void
     */
    public function saved(PromoCodeCategory $category)
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
    public function deleted(PromoCodeCategory $category)
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
