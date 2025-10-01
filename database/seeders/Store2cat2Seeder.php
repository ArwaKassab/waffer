<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;

class Store2cat2Seeder extends Seeder
{
    public function run(): void
    {
        // خريطة: اسم المتجر => قائمة تصنيفات
        $map = [
            'خضار ومواد غذائية مزة'       => ['خضار', 'مواد غذائية'],
            'خضار ومواد غذائية ببيلا'     => ['خضار', 'مواد غذائية'],
            'خضار ومواد غذائية ركن الدين' => ['خضار', 'مواد غذائية'],
        ];

        // جهزي لوك أب للمتاجر والتصنيفات
        $storeNames = array_keys($map);
        $stores = User::query()
            ->where('type', 'store')
            ->whereIn('name', $storeNames)
            ->pluck('id', 'name'); // name => id

        $allCatNames = collect($map)->flatten()->unique()->values();
        $cats = Category::query()
            ->whereIn('name', $allCatNames)
            ->pluck('id', 'name'); // name => id

        foreach ($map as $storeName => $categoryNames) {
            $storeId = $stores[$storeName] ?? null;
            if (!$storeId) {
                $this->command?->warn("Store not found: {$storeName}");
                continue;
            }

            $catIds = collect($categoryNames)
                ->map(fn ($n) => $cats[$n] ?? null)
                ->filter()
                ->values()
                ->all();

            if (empty($catIds)) {
                $this->command?->warn("No valid categories for: {$storeName}");
                continue;
            }

            // لا يحذف القديم، ويضيف الناقص فقط
            $store = User::find($storeId);
            $store->categories()->syncWithoutDetaching($catIds);
        }

        $this->command?->info('Store-category links added without deleting old data.');
    }
}
