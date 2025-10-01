<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;

class ProductForTwoCatsSeeder extends Seeder
{
    public function run(): void
    {
        // حددي التصنيفين المستهدفين
        $targetCategories = ['خضار', 'مواد غذائية'];

        // تأكيد وجود التصنيفات (اختياري لو بتحبي تحذير)
        $foundCats = Category::whereIn('name', $targetCategories)->pluck('id', 'name');
        foreach ($targetCategories as $cName) {
            if (!isset($foundCats[$cName])) {
                $this->command?->warn("التصنيف غير موجود: {$cName}");
            }
        }

        // جيبي المتاجر التي تملك "خضار" و"مواد غذائية" معًا
        $stores = User::query()
            ->where('type', 'store')
            ->where(function ($q) use ($targetCategories) {
                // whereHas لكل تصنيف للتأكد أنه يمتلكهما معًا
                foreach ($targetCategories as $name) {
                    $q->whereHas('categories', fn($qq) => $qq->where('name', $name));
                }
            })
            ->get(['id', 'name']);

        if ($stores->isEmpty()) {
            $this->command?->warn('لا يوجد متاجر تملك التصنيفين معًا.');
            return;
        }

        // المنتجات لكل تصنيف
        $productsByCategory = [
            'خضار' => [
                ['name' => 'موز',            'price' => 15000, 'image' => 'products/موز.jpg',            'quantity' => 1,   'unit' => 'كيلوغرام', 'details' => 'طازج'],
                ['name' => 'كرز',            'price' => 30000, 'image' => 'products/كرز.jpg',            'quantity' => 1,   'unit' => 'كيلوغرام', 'details' => 'طازج'],
            ],
            'مواد غذائية' => [
                ['name' => 'مرتديلا',        'price' => 10000, 'image' => 'products/مرتديلا.jpg',        'quantity' => 1,   'unit' => 'غرام',      'details' => 'محشوة بالزيتون'],
                ['name' => 'معجون الطماطم',  'price' => 25000, 'image' => 'products/معجون الطماطم.jpg', 'quantity' => 350, 'unit' => 'غرام',      'details' => 'أجود الانواع'],
            ],
        ];

        foreach ($stores as $store) {
            foreach ($targetCategories as $catName) {
                $list = $productsByCategory[$catName] ?? [];
                foreach ($list as $p) {
                    Product::updateOrCreate(
                        ['store_id' => $store->id, 'name' => $p['name']],
                        [
                            'price'    => $p['price'],
                            'image'    => $p['image'], // مسار نسبي داخل storage/app/public
                            'status'   => 'available',
                            'quantity' => $p['quantity'],
                            'unit'     => $p['unit'],
                            'details'  => $p['details'] ?? null,
                        ]
                    );
                }
            }
            $this->command?->info("تم تزويد متجر: {$store->name}");
        }
    }
}
