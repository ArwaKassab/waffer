<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\User;
use  App\Repositories\Contracts\StoreRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


class StoreRepository implements StoreRepositoryInterface
{

    public function getStoresByArea(int $areaId, int $perPage = 20)
    {
        $paginator = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->select('id', 'area_id', 'name', 'image', 'note', 'open_hour', 'close_hour', 'status')
            ->with(['categories:id'])
            ->orderBy('name')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function ($store) {
            $store->category_ids = $store->categories->pluck('id')->values();
            unset($store->categories);
            $store->image = $store->image_url;
            $store->open_hour  = $store->open_hour_formatted;
            $store->close_hour = $store->close_hour_formatted;

            return $store;
        });

        return $paginator;
    }


    public function getStoresByAreaAndCategoryPaged(int $areaId, int $categoryId, int $perPage = 20)
    {
        $paginator = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
            ->select('id', 'area_id', 'name', 'image','status',  'note', 'open_hour', 'close_hour')
            ->orderBy('name')
            ->paginate($perPage);

        return $paginator;
    }


    public function getStoresByAreaAndCategory($areaId, $categoryId)
    {
        $stores = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            })
            ->get(['id', 'area_id', 'name', 'image', 'note', 'status', 'open_hour', 'close_hour']);

        $stores->transform(function ($store) {
            $store->image = $store->image_url;
            $store->open_hour  = $store->open_hour_formatted;   // "08:00"
            $store->close_hour = $store->close_hour_formatted;  // "22:00"

            return $store;
        });

        return $stores;
    }



    public function searchStoresAndProductsGroupedInArea(
        int    $areaId,
        string $q,
        ?int   $productsPerStoreLimit = 10
    )
    {
        // 1) تجهيز التوكنز وأنماط REGEXP
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn($t) => trim($t))
            ->filter(fn($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn(string $t): string => preg_quote($t, '/');
        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,
                '(^|[[:space:][:punct:]])(ال)?' . $re,
            ];
        };

        // 2) متاجر تطابق الاسم داخل المنطقة
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

        // 3) منتجات تطابق الاسم داخل المنطقة + eager-load للخصم الفعّال
        $productsMatched = Product::query()
            ->with([
                'store:id,name,area_id,image,note,open_hour,close_hour',
                'activeDiscount' => function ($q2) {
                    $q2->select(
                        'discounts.id',
                        'discounts.product_id',
                        'discounts.new_price',
                        'discounts.start_date',
                        'discounts.end_date',
                        'discounts.status'
                    );
                },
            ])
            ->whereHas('store', fn($qs) => $qs->where('type', 'store')->where('area_id', $areaId))
            ->select('products.id', 'products.name', 'products.price', 'products.store_id', 'products.image', 'products.details','products.status')
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


        $result = [];

        // (أ) المتاجر التي طابقت بالاسم
        foreach ($storesByName as $s) {
            $result[$s->id] = [
                'id' => $s->id,
                'name' => $s->name,
                'area_id' => $s->area_id,
                'is_open_now' => (bool) $s->is_open_now,
                'note' => $s->note,
                'open_hour' => $s->open_hour,
                'close_hour' => $s->close_hour,
                'image' => $s->image_url,
                'products' => [],
                '_matched_by_store_name' => true,
            ];
        }

        // (ب) المتاجر الناتجة عن تطابق المنتجات + إدراج المنتج المطابق
        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                $result[$s->id] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'area_id' => $s->area_id,
                    'is_open_now'=> (bool) $s->is_open_now,
                    'note' => $s->note ?? null,
                    'open_hour' => $s->open_hour ?? null,
                    'close_hour' => $s->close_hour ?? null,
                    'image' => $s->image_url,
                    'products' => [],
                ];
            }

            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $productArr = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float)$p->price,
                    'isAvilable' => (bool) $p->status,
                    'image' => $p->image_url,
                    'details' => $p->details,
                    '_matched' => true,
                ];
                if ($p->activeDiscount?->new_price !== null) {
                    $productArr['new_price'] = (float)$p->activeDiscount->new_price; // يظهر فقط عند وجود خصم
                }
                $result[$s->id]['products'][] = $productArr;
            }
        }

        // 5) للمتاجر المطابقة بالاسم: أضف منتجاتها مع منطق السعر نفسه
        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->with([
                    'activeDiscount' => function ($q2) {
                        $q2->select(
                            'discounts.id',
                            'discounts.product_id',
                            'discounts.new_price',
                            'discounts.start_date',
                            'discounts.end_date',
                            'discounts.status'
                        );
                    }
                ])
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id', 'name', 'price', 'store_id', 'image', 'details','status')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                $existing = collect($result[$sid]['products'])->keyBy('id');

                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;

                    $productArr = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => (float)$p->price,
                        'isAvilable' => (bool) $p->status,
                        'details' => $p->details,
                        'image' => $p->image_url,
                    ];
                    if ($p->activeDiscount?->new_price !== null) {
                        $productArr['new_price'] = (float)$p->activeDiscount->new_price;
                    }

                    $result[$sid]['products'][] = $productArr;

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
    public function getStoreDetailsWithProductsAndDiscounts($storeId)
    {
        $store = User::query()
            ->where('type', 'store')
            ->whereKey($storeId)
            ->with(['products', 'categories'])
            ->first(['id', 'name', 'image', 'note','status', 'open_hour', 'close_hour']);

        if (!$store) {
            return null;
        }

        $store->append('image_url')->makeHidden(['image']);

        $productsWithDiscountsFirst = $store->products
            ->sortByDesc(fn($product) => $product->activeDiscountToday() ? 1 : 0)
            ->values();

        return [
            'store' => [
                'id'         => $store->id,
                'name'       => $store->name,
                'image_url'  => $store->image_url,
                'is_open_now'=> (bool) $store->is_open_now,
                'note'       => $store->note,
                'open_hour'  => $store->open_hour_formatted,
                'close_hour' => $store->close_hour_formatted,

            ],
            'categories' => $store->categories->map(fn($category) => [
                'id' => $category->id,
            ]),
            'products' => $productsWithDiscountsFirst->map(function ($product) {
                $discount = $product->activeDiscountToday();

                return [
                    'id'             => $product->id,
                    'name'           => $product->name,
                    'image_url'      => $product->image_url,
                    'isAvailable'    => (bool) $product->status,
                    'unit'           => $product->unit,
                    'details'        => $product->details,
                    'original_price' => $product->price,
                    'new_price'      => $discount?->new_price,
                    'discount_title' => $discount?->title,
                ];
            }),
        ];
    }

    public function searchStoresAndProductsGrouped(
        int    $areaId,
        int    $categoryId,
        string $q,
        ?int   $productsPerStoreLimit = 10
    )
    {
        // 1) تجهيز التوكنز وأنماط REGEXP
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn($t) => trim($t))
            ->filter(fn($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn(string $t): string => preg_quote($t, '/');
        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,
                '(^|[[:space:][:punct:]])(ال)?' . $re,
            ];
        };

        // 2) متاجر تطابق الاسم ضمن المنطقة + التصنيف
        $storesByName = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
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

        // 3) المنتجات المطابقة + خصم فعّال
        $productsMatched = Product::query()
            ->with([
                'store:id,name,area_id,image,status,note,open_hour,close_hour',
                'activeDiscount' => function ($q2) {
                    $q2->select(
                        'discounts.id',
                        'discounts.product_id',
                        'discounts.new_price',
                        'discounts.start_date',
                        'discounts.end_date',
                        'discounts.status'
                    );
                },
            ])
            ->whereHas('store', function ($qs) use ($areaId, $categoryId) {
                $qs->where('type', 'store')
                    ->where('area_id', $areaId)
                    ->whereHas('categories', fn($qc) => $qc->where('categories.id', $categoryId));
            })
            ->select('products.id', 'products.name', 'products.price', 'products.store_id', 'products.image', 'products.details','products.status')
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

        $result = [];

        // أ) المتاجر المطابقة بالاسم
        foreach ($storesByName as $s) {
            $result[$s->id] = [
                'id' => $s->id,
                'name' => $s->name,
                'area_id' => $s->area_id,
                'is_open_now' => (bool) $s->is_open_now,
                'note' => $s->note,
                'open_hour' => $s->open_hour,
                'close_hour' => $s->close_hour,
                'image' => $s->image_url,
                'products' => [],
                '_matched_by_store_name' => true,
            ];
        }

        // ب) المنتجات المطابقة → أضف متجرها بمنتج يحوي price و(اختياريًا) new_price
        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                $result[$s->id] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'area_id' => $s->area_id,
                    'is_open_now' => (bool) $s->is_open_now,
                    'note' => $s->note ?? null,
                    'open_hour' => $s->open_hour ?? null,
                    'close_hour' => $s->close_hour ?? null,
                    'image' => $s->image_url,
                    'products' => [],
                ];
            }

            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $productArr = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float)$p->price,
                    'image' => $p->image_url,
                    'isAvilable' => (bool) $p->status,
                    'details' => $p->details,
                    '_matched' => true,
                ];
                if ($p->activeDiscount?->new_price !== null) {
                    $productArr['new_price'] = (float)$p->activeDiscount->new_price;
                }
                $result[$s->id]['products'][] = $productArr;
            }
        }

        // ج) حمّل منتجات المتاجر التي طابقت بالاسم (احترام حدّ المنتجات)
        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->with([
                    'activeDiscount' => function ($q2) {
                        $q2->select(
                            'discounts.id',
                            'discounts.product_id',
                            'discounts.new_price',
                            'discounts.start_date',
                            'discounts.end_date',
                            'discounts.status'
                        );
                    }
                ])
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id', 'name', 'price', 'store_id', 'image', 'details','select')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                $existing = collect($result[$sid]['products'])->keyBy('id');

                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;

                    $productArr = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => (float)$p->price,
                        'isAvilable' => (bool) $p->status,
                        'details' => $p->details,
                        'image' => $p->image_url,
                    ];
                    if ($p->activeDiscount?->new_price !== null) {
                        $productArr['new_price'] = (float)$p->activeDiscount->new_price;
                    }

                    $result[$sid]['products'][] = $productArr;

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

        // 4) ترتيب المتاجر وإرجاع مصفوفة
        $result = array_values($result);
        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }

    public function getStoresByAreaAndCategoriesPaged(int $areaId, array $categoryIds, int $perPage = 20, string $matchMode = 'all')
    {
        $q = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->with(['categories:id'])
            ->select('id', 'area_id', 'name', 'image',  'note', 'open_hour', 'close_hour');

        if ($matchMode === 'all') { // الآن هذا هو الافتراضي
            $q->where(function ($qq) use ($categoryIds) {
                foreach ($categoryIds as $cid) {
                    $qq->whereHas('categories', fn($q2) => $q2->where('categories.id', $cid));
                }
            });
        } else {
            $q->whereHas('categories', fn($qq) => $qq->whereIn('categories.id', $categoryIds));
        }

        $paginator = $q->orderBy('name')->paginate($perPage);

        $paginator->getCollection()->transform(function ($store) {
            $store->category_ids = $store->categories->pluck('id')->values();
            unset($store->categories);
            return $store;
        });

        return $paginator;
    }

    public function searchStoresAndProductsGroupedByCategories(
        int    $areaId,
        array  $categoryIds,
        string $q,
        ?int   $productsPerStoreLimit = 10,
        string $matchMode = 'all' // بدل any
    )
    {
        // تجهيز التوكنز وأنماط REGEXP (نفس كودك الحالي)
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn($t) => trim($t))
            ->filter(fn($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn(string $t): string => preg_quote($t, '/');
        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,
                '(^|[[:space:][:punct:]])(ال)?' . $re,
            ];
        };

        // متاجر تطابق الاسم ضمن المنطقة + التصنيفات (OR/AND)
        $storesByName = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->when(true, function ($q) use ($categoryIds, $matchMode) {
                if ($matchMode === 'all') {
                    $q->where(function ($qq) use ($categoryIds) {
                        foreach ($categoryIds as $cid) {
                            $qq->whereHas('categories', fn($q2) => $q2->where('categories.id', $cid));
                        }
                    });
                } else {
                    $q->whereHas('categories', fn($qq) => $qq->whereIn('categories.id', $categoryIds));
                }
            })
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

        // المنتجات المطابقة ضمن المنطقة + التصنيفات (OR/AND)
        $productsMatched = Product::query()
            ->with([
                'store:id,name,area_id,image,status,note,open_hour,close_hour',
                'activeDiscount' => function ($q2) {
                    $q2->select('discounts.id', 'discounts.product_id', 'discounts.new_price', 'discounts.start_date', 'discounts.end_date', 'discounts.status');
                },
            ])
            ->whereHas('store', function ($qs) use ($areaId, $categoryIds, $matchMode) {
                $qs->where('type', 'store')->where('area_id', $areaId);

                if ($matchMode === 'all') {
                    $qs->where(function ($qq) use ($categoryIds) {
                        foreach ($categoryIds as $cid) {
                            $qq->whereHas('categories', fn($q2) => $q2->where('categories.id', $cid));
                        }
                    });
                } else {
                    $qs->whereHas('categories', fn($qc) => $qc->whereIn('categories.id', $categoryIds));
                }
            })
            ->select('products.id', 'products.name', 'products.price', 'products.store_id', 'products.image', 'products.details','products.status')
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

        // بناء النتيجة (نفس منطقك الحالي) مع image_url
        $result = [];

        foreach ($storesByName as $s) {
            $result[$s->id] = [
                'id' => $s->id,
                'name' => $s->name,
                'area_id' => $s->area_id,
                'is_open_now' => (bool) $s->is_open_now,
                'note' => $s->note,
                'open_hour' => $s->open_hour,
                'close_hour' => $s->close_hour,
                'image' => $s->image_url,
                'products' => [],
                '_matched_by_store_name' => true,
            ];
        }

        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                $result[$s->id] = [
                    'id' => $s->id,
                    'name' => $s->name,
                    'area_id' => $s->area_id,
                    'is_open_now' => (bool) $s->is_open_now,
                    'note' => $s->note ?? null,
                    'open_hour' => $s->open_hour ?? null,
                    'close_hour' => $s->close_hour ?? null,
                    'image' => $s->image_url,
                    'products' => [],
                ];
            }

            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $productArr = [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float)$p->price,
                    'isAvilable' => (bool) $p->status,
                    'image' => $p->image_url,
                    'details' => $p->details,
                    '_matched' => true,
                ];
                if ($p->activeDiscount?->new_price !== null) {
                    $productArr['new_price'] = (float)$p->activeDiscount->new_price;
                }
                $result[$s->id]['products'][] = $productArr;
            }
        }

        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->with(['activeDiscount' => function ($q2) {
                    $q2->select('discounts.id', 'discounts.product_id', 'discounts.new_price', 'discounts.start_date', 'discounts.end_date', 'discounts.status');
                }])
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id', 'name', 'price', 'store_id', 'image', 'details')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                $existing = collect($result[$sid]['products'])->keyBy('id');

                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;

                    $productArr = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'price' => (float)$p->price,
                        'isAvilable' => (bool) $p->status,
                        'image' => $p->image_url,
                        'details' => $p->details,
                    ];
                    if ($p->activeDiscount?->new_price !== null) {
                        $productArr['new_price'] = (float)$p->activeDiscount->new_price;
                    }

                    $result[$sid]['products'][] = $productArr;

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

        $result = array_values($result);
        usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }
/////////////////////////////////////////subadmin////////////////////////////////

    /**
     * جلب المتاجر حسب رقم المنطقة للأدمن.
     */
    public function getStoresByAreaForAdmin(int $areaId, int $perPage = 20)
    {
        $paginator = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->select(
                'id',
                'name',
                'phone',
                'open_hour',
                'close_hour'
            )
            ->with(['categories:id,name']) // فقط id + name
            ->orderBy('name')
            ->paginate($perPage);

        // هون أهم خطوة: نرجع "Array" نظيف بدل موديل
        $paginator->getCollection()->transform(function ($store) {

            $workHours = null;

            if (!empty($store->open_hour) && !empty($store->close_hour)) {
                $from = Carbon::parse($store->open_hour)->format('H:i');
                $to = Carbon::parse($store->close_hour)->format('H:i');
                $workHours = $from . '-' . $to;
            }

            return [
                'id' => $store->id,
                'name' => $store->name,
                'phone' => $store->phone,
                'is_open_now' => (bool) $store->is_open_now,
                'status' => $store->status,
                'work_hours' => $workHours,
                'categories' => $store->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                    ];
                })->values(),
            ];
        });

        return $paginator;
    }

    /**
     * إنشاء متجر جديد وربطه بالتصنيفات.
     */
    public function createStore(array $data, array $categoryIds = []): User
    {
        /** @var \App\Models\User $store */
        $store = User::create($data);

        if (!empty($categoryIds)) {
            $store->categories()->sync($categoryIds);
        }

        // تحميل التصنيفات للاستخدام في الـ Resource
        $store->load(['categories:id,name', 'area:id,name']);

        return $store;
    }


    /**
     * تعديل متجر موجود مع إمكانية تحديث التصنيفات.
     */
    public function updateStore(User $store, array $data, ?array $categoryIds = null): User
    {
        $store->fill($data);
        $store->save();

        if (!is_null($categoryIds)) {
            $store->categories()->sync($categoryIds);
        }

        // نرجّع نسخة جديدة من الداتا بعد الحفظ
        return $store->fresh(['categories:id,name', 'area:id,name']);
    }

    /**
     * حذف متجر (Soft Delete) ضمن منطقة معيّنة للأدمن.
     */
    public function deleteStoreByIdForAdmin(int $storeId, int $areaId): bool
    {
        $store = User::where('type', 'store')
            ->where('area_id', $areaId)
            ->where('id', $storeId)
            ->first();

        if (! $store) {
            return false;
        }
        $store->delete();

        return true;
    }
}
