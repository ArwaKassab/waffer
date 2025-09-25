<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\User;
use  App\Repositories\Contracts\StoreRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


class StoreRepository implements StoreRepositoryInterface
{

    public function getStoresByArea(int $areaId)
    {
        $stores = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->get(['id','area_id','name','image','status','note','open_hour','close_hour']);

        $stores->transform(function ($store) {
            $store->image = $store->image ? Storage::url($store->image) : null;
            return $store;
        });

        return $stores;
    }

    public function getStoresByAreaAndCategory($areaId, $categoryId)
    {
        $stores = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            })
            ->get(['id', 'area_id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour' ]);

        $stores->transform(function ($store) {
            $store->image = $store->image ? Storage::url($store->image) : null;
            return $store;
        });

        return $stores;
    }



    public function getStoreDetailsWithProductsAndDiscounts($storeId)
    {
        $store = User::where('type', 'store')
            ->where('id', $storeId)
            ->with('products','categories')
            ->first(['id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour']);

        if (!$store) {
            return null;
        }

        $store->image = $store->image ? Storage::url($store->image) : null;

        $productsWithDiscountsFirst = $store->products->sortByDesc(function ($product) {
            return $product->activeDiscountToday() ? 1 : 0;
        })->values();

        return [
            'store' => $store->only(['id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour']),
            'categories' => $store->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                ];
            }),
            'products' => $productsWithDiscountsFirst->map(function ($product) {
                $discount = $product->activeDiscountToday();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'image' => Storage::url($product->image),
                    'status' => $product->status,
                    'unit' => $product->unit,
                    'original_price' => $product->price,
                    'new_price' => $discount?->new_price,
                    'discount_title' => $discount?->title,
                ];
            })
        ];
    }

    public function searchStoresAndProductsGrouped(
        int $areaId,
        int $categoryId,
        string $q,
        ?int $productsPerStoreLimit = 10
    ) {
        // 1) تجهيز التوكنز والـ REGEXP
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn (string $t): string => preg_quote($t, '/');
        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,                               // بداية النص
                '(^|[[:space:][:punct:]])(ال)?' . $re,               // بداية كلمة + "ال" اختياري
            ];
        };

        // 2) متاجر تطابق الاسم ضمن المنطقة + التصنيف (بدون limit → كل المتاجر)
        $storesByName = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->select('id','area_id','name','image','status','note','open_hour','close_hour')
            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $buildPatterns) {
                foreach ($tokens as $t) {
                    [$p1, $p2] = $buildPatterns($t);
                    $query->where(function ($qq) use ($p1, $p2) {
                        $qq->whereRaw("`name` REGEXP ?", [$p1])
                            ->orWhereRaw("`name` REGEXP ?", [$p2]);
                    });
                }
            })
            ->orderBy('name')
            ->get();

        $storeNameMatchedIds = $storesByName->pluck('id')->all();

        // 3) منتجات تطابق الاسم ضمن المنطقة + التصنيف (بحسب متجرها)
        $productsMatched = Product::query()
            ->with(['store:id,name,area_id,image,status,note,open_hour,close_hour'])
            ->whereHas('store', function ($qs) use ($areaId, $categoryId) {
                $qs->where('type','store')
                    ->where('area_id', $areaId)
                    ->whereHas('categories', fn ($qc) => $qc->where('categories.id', $categoryId));
            })
            ->select('products.id','products.name','products.price','products.store_id','products.image')
            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $buildPatterns) {
                foreach ($tokens as $t) {
                    [$p1, $p2] = $buildPatterns($t);
                    $query->where(function ($qq) use ($p1, $p2) {
                        $qq->whereRaw("`products`.`name` REGEXP ?", [$p1])
                            ->orWhereRaw("`products`.`name` REGEXP ?", [$p2]);
                    });
                }
            })
            ->distinct() // حماية من أي تكرار بسبب join لاحقًا
            ->orderBy('products.name')
            ->get();

        // 4) بناء النتيجة المُجمَّعة (بدون تكرار متجر)
        $result = []; // keyed by store_id

        // (أ) أضف المتاجر المطابقة بالاسم (المنتجات تُملأ لاحقًا)
        foreach ($storesByName as $s) {
            $result[$s->id] = [
                'id'         => $s->id,
                'name'       => $s->name,
                'area_id'    => $s->area_id,
                'status'     => $s->status,
                'note'       => $s->note,
                'open_hour'  => $s->open_hour,
                'close_hour' => $s->close_hour,
                'image'      => $s->image ? Storage::url($s->image) : null,
                'products'   => [],
                '_matched_by_store_name' => true, // علامة داخلية
            ];
        }

        // (ب) أضف المتاجر الناتجة عن تطابق المنتجات + أدرج المنتج المطابق داخل متجره
        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                $result[$s->id] = [
                    'id'         => $s->id,
                    'name'       => $s->name,
                    'area_id'    => $s->area_id,
                    'status'     => $s->status ?? null,
                    'note'       => $s->note ?? null,
                    'open_hour'  => $s->open_hour ?? null,
                    'close_hour' => $s->close_hour ?? null,
                    'image'      => $s->image ? Storage::url($s->image) : null,
                    'products'   => [],
                ];
            }

            // أضف المنتج المطابق (لو غير مضاف)
            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $result[$s->id]['products'][] = [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'price' => $p->price,
                    'image' => $p->image ? Storage::url($p->image) : null,
                    'quantity_with_unit' => $p->quantity.','.$p->unit,
                    '_matched' => true, // علامة داخلية للفرز لاحقًا
                ];
            }
        }

        // 5) للمتاجر التي طابقت بالاسم: حمّل منتجاتها (لو بدك تعرض منتجات المتجر عند تطابق اسمه)
        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id','name','price','store_id','image')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                // اجمع المنتجات الموجودة مسبقًا (من التطابق بالمنتج)
                $existing = collect($result[$sid]['products'])->keyBy('id');

                // أضف بقية منتجات المتجر
                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;
                    $result[$sid]['products'][] = [
                        'id'    => $p->id,
                        'name'  => $p->name,
                        'price' => $p->price,
                        'image' => $p->image ? Storage::url($p->image) : null,
                    ];

                    // احترم الحد إن كان محدَّدًا
                    if (!is_null($productsPerStoreLimit) &&
                        count($result[$sid]['products']) >= $productsPerStoreLimit) {
                        break;
                    }
                }

                // قصّ النهائي: حافظ على المطابق أولاً ثم أكمل
                if (!is_null($productsPerStoreLimit) &&
                    count($result[$sid]['products']) > $productsPerStoreLimit) {
                    $matched = array_values(array_filter($result[$sid]['products'], fn($x) => !empty($x['_matched'])));
                    $others  = array_values(array_filter($result[$sid]['products'], fn($x) => empty($x['_matched'])));
                    $result[$sid]['products'] = array_slice(array_merge($matched, $others), 0, $productsPerStoreLimit);
                }

                unset($result[$sid]['_matched_by_store_name']);
                foreach ($result[$sid]['products'] as &$pp) { unset($pp['_matched']); }
            }
        }

        // 6) ترتيب المتاجر بالاسم وإرجاع كمصفوفة (بدون مفاتيح store_id)
        $result = array_values($result);
        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }

    public function searchStoresAndProductsGroupedInArea(
        int $areaId,
        string $q,
        ?int $productsPerStoreLimit = 10
    )
    {
        // 1) تحضير التوكنز وأنماط REGEXP (نفس منطقك السابق)
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn($t) => trim($t))
            ->filter(fn($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn(string $t): string => preg_quote($t, '/');
        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,                          // بداية النص
                '(^|[[:space:][:punct:]])(ال)?' . $re,          // بداية كلمة + "ال" اختياري
            ];
        };

        // 2) متاجر تطابق الاسم داخل المنطقة (بدون تقييد تصنيف)
        $storesByName = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->select('id', 'area_id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour')
            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $buildPatterns) {
                foreach ($tokens as $t) {
                    [$p1, $p2] = $buildPatterns($t);
                    $query->where(function ($qq) use ($p1, $p2) {
                        $qq->whereRaw("`name` REGEXP ?", [$p1])
                            ->orWhereRaw("`name` REGEXP ?", [$p2]);
                    });
                }
            })
            ->orderBy('name')
            ->get();

        $storeNameMatchedIds = $storesByName->pluck('id')->all();

        // 3) منتجات تطابق الاسم داخل المنطقة (حسب متجرها)، بدون تقييد تصنيف
        $productsMatched = Product::query()
            ->with(['store:id,name,area_id,image,status,note,open_hour,close_hour'])
            ->whereHas('store', function ($qs) use ($areaId) {
                $qs->where('type', 'store')->where('area_id', $areaId);
            })
            ->select('products.id', 'products.name', 'products.price', 'products.store_id', 'products.image')
            ->when($tokens->isNotEmpty(), function ($query) use ($tokens, $buildPatterns) {
                foreach ($tokens as $t) {
                    [$p1, $p2] = $buildPatterns($t);
                    $query->where(function ($qq) use ($p1, $p2) {
                        $qq->whereRaw("`products`.`name` REGEXP ?", [$p1])
                            ->orWhereRaw("`products`.`name` REGEXP ?", [$p2]);
                    });
                }
            })
            ->distinct()
            ->orderBy('products.name')
            ->get();

        // 4) بناء النتيجة المجمّعة بدون تكرار متجر
        $result = []; // keyed by store_id

        // (أ) المتاجر التي طابقت بالاسم
        foreach ($storesByName as $s) {
            $result[$s->id] = [
                'id' => $s->id,
                'name' => $s->name,
                'area_id' => $s->area_id,
                'status' => $s->status,
                'note' => $s->note,
                'open_hour' => $s->open_hour,
                'close_hour' => $s->close_hour,
                'image' => $s->image ? Storage::url($s->image) : null,
                'products' => [],
                '_matched_by_store_name' => true,
            ];
        }

        // (ب) المتاجر القادمة من تطابق المنتجات + إدراج المنتج المطابق
        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                $result[$s->id] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'area_id' => $s->area_id,
                    'status' => $s->status ?? null,
                    'note' => $s->note ?? null,
                    'open_hour' => $s->open_hour ?? null,
                    'close_hour' => $s->close_hour ?? null,
                    'image' => $s->image ? Storage::url($s->image) : null,
                    'products' => [],
                ];
            }

            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $result[$s->id]['products'][] = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'image' => $p->image ? Storage::url($p->image) : null,
                    '_matched' => true,
                ];
            }
        }

        // 5) لو المتجر طابق بالاسم، نحمّل منتجاته (بدون فلترة تصنيف)
        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id', 'name', 'price', 'store_id', 'image')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                $existing = collect($result[$sid]['products'])->keyBy('id');

                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;
                    $result[$sid]['products'][] = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => $p->price,
                        'image' => $p->image ? Storage::url($p->image) : null,
                    ];

                    if (!is_null($productsPerStoreLimit) &&
                        count($result[$sid]['products']) >= $productsPerStoreLimit) {
                        break;
                    }
                }

                if (!is_null($productsPerStoreLimit) &&
                    count($result[$sid]['products']) > $productsPerStoreLimit) {
                    $matched = array_values(array_filter($result[$sid]['products'], fn($x) => !empty($x['_matched'])));
                    $others = array_values(array_filter($result[$sid]['products'], fn($x) => empty($x['_matched'])));
                    $result[$sid]['products'] = array_slice(array_merge($matched, $others), 0, $productsPerStoreLimit);
                }

                unset($result[$sid]['_matched_by_store_name']);
                foreach ($result[$sid]['products'] as &$pp) {
                    unset($pp['_matched']);
                }
            }
        }

        // 6) ترتيب المتاجر وإرجاع مصفوفة
        $result = array_values($result);
        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }
}
