<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Carbon\Carbon;

class StoreRepository implements StoreRepositoryInterface
{
    /**
     * توحيد شكل إخراج المتجر في كل المسارات
     * - open_hour / close_hour دائماً بصيغة H:i
     * - is_open_now محسوب من accessor في User
     * - category_ids موجود دائماً (قد يكون [] إذا لم تُحمّل التصنيفات بعد)
     */
    /**
     * @param \App\Models\User $s
     */
    private function storePayload(User $s, array $extra = []): array
    {
        return array_merge([
            'id'          => $s->id,
            'name'        => $s->name,
            'area_id'     => $s->area_id,
            'status'      => (bool) $s->status,
            'is_open_now' => (bool) $s->is_open_now,

            'open_hour'   => $s->open_hour_formatted,
            'close_hour'  => $s->close_hour_formatted,

            'note'        => $s->note,

            // دعم المفتاحين دائمًا
            'image'       => $s->image_url,
            'image_url'   => $s->image_url,
        ], $extra);
    }

    /**
     * تعبئة category_ids للمتاجر التي جائت من relation store داخل Product
     * (غالباً لن تكون categories محمّلة هناك)
     */
    private function hydrateMissingCategoryIds(array &$resultByStoreId): void
    {
        $missingIds = [];

        foreach ($resultByStoreId as $sid => $row) {
            // إذا لم تُملأ category_ids أو كانت null
            if (!array_key_exists('category_ids', $row) || $row['category_ids'] === null) {
                $missingIds[] = (int) $sid;
                continue;
            }

            // إذا فاضية: قد تكون فعلاً بدون تصنيفات، لكن غالباً السبب أنها غير محمّلة
            if (is_array($row['category_ids']) && count($row['category_ids']) === 0) {
                $missingIds[] = (int) $sid;
            }
        }

        $missingIds = array_values(array_unique($missingIds));
        if (empty($missingIds)) {
            return;
        }

        $stores = User::query()
            ->whereIn('id', $missingIds)
            ->with(['categories:id'])
            ->select('id')
            ->get();

        $map = [];
        foreach ($stores as $s) {
            $map[$s->id] = $s->categories->pluck('id')->values()->all();
        }

        foreach ($missingIds as $sid) {
            if (!isset($resultByStoreId[$sid])) continue;
            $resultByStoreId[$sid]['category_ids'] = $map[$sid] ?? [];
        }
    }

    public function getStoresByArea(int $areaId, int $perPage = 20)
    {
        $paginator = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->select('id', 'area_id', 'name', 'image', 'note', 'open_hour', 'close_hour', 'status')
            ->with(['categories:id'])
            ->orderBy('name')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (User $store) {
            return $this->storePayload($store);
        });

        return $paginator;
    }

    public function getStoresByAreaAndCategoryPaged(int $areaId, int $categoryId, int $perPage = 20)
    {
        $paginator = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->select('id', 'area_id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour')
            ->with(['categories:id'])
            ->orderBy('name')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (User $store) {
            return $this->storePayload($store);
        });

        return $paginator;
    }

    public function getStoresByAreaAndCategory($areaId, $categoryId)
    {
        $stores = User::query()
            ->where('type', 'store')
            ->where('area_id', (int) $areaId)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', (int) $categoryId))
            ->select('id', 'area_id', 'name', 'image', 'note', 'status', 'open_hour', 'close_hour')
            ->with(['categories:id'])
            ->orderBy('name')
            ->get();

        $stores->transform(function (User $store) {
            return $this->storePayload($store);
        });

        return $stores;
    }

    public function searchStoresAndProductsGroupedInArea(
        int $areaId,
        string $q,
        ?int $productsPerStoreLimit = 10
    ) {
        // 1) تجهيز التوكنز وأنماط REGEXP
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn (string $t): string => preg_quote($t, '/');

        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,
                '(^|[[:space:][:punct:]])(ال)?' . $re,
            ];
        };

        // 2) متاجر تطابق الاسم داخل المنطقة (+ تحميل categories لتجهيز category_ids)
        $storesByName = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->with(['categories:id'])
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
            ->whereHas('store', fn ($qs) => $qs->where('type', 'store')->where('area_id', $areaId))
            ->select('products.id', 'products.name', 'products.price', 'products.store_id', 'products.image', 'products.details', 'products.status')
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

        $result = []; // keyed by store_id

        // (أ) المتاجر التي طابقت بالاسم
        foreach ($storesByName as $s) {
            $result[$s->id] = $this->storePayload($s, [
                'products' => [],
                '_matched_by_store_name' => true,
            ]);
        }

        // (ب) المتاجر الناتجة عن تطابق المنتجات + إدراج المنتج المطابق
        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                // categories ليست محمّلة هنا عادةً → category_ids ستُعبّأ لاحقاً
                $result[$s->id] = $this->storePayload($s, [
                    'products' => [],
                ]);
            }

            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $productArr = [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'price'       => (float) $p->price,
                    'isAvailable' => $p->status === 'available',
                    'image'       => $p->image_url,
                    'details'     => $p->details,
                    '_matched'    => true,
                ];

                if ($p->activeDiscount?->new_price !== null) {
                    $productArr['new_price'] = (float) $p->activeDiscount->new_price;
                }

                $result[$s->id]['products'][] = $productArr;
            }
        }

        // (ج) للمتاجر المطابقة بالاسم: أضف منتجاتها (limit)
        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->with(['activeDiscount' => function ($q2) {
                    $q2->select(
                        'discounts.id',
                        'discounts.product_id',
                        'discounts.new_price',
                        'discounts.start_date',
                        'discounts.end_date',
                        'discounts.status'
                    );
                }])
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id', 'name', 'price', 'store_id', 'image', 'details', 'status')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                $existing = collect($result[$sid]['products'])->keyBy('id');

                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;

                    $productArr = [
                        'id'          => $p->id,
                        'name'        => $p->name,
                        'price'       => (float) $p->price,
                        'isAvailable' => $p->status === 'available',
                        'details'     => $p->details,
                        'image'       => $p->image_url,
                    ];

                    if ($p->activeDiscount?->new_price !== null) {
                        $productArr['new_price'] = (float) $p->activeDiscount->new_price;
                    }

                    $result[$sid]['products'][] = $productArr;

                    if (!is_null($productsPerStoreLimit) && count($result[$sid]['products']) >= $productsPerStoreLimit) {
                        break;
                    }
                }

                if (!is_null($productsPerStoreLimit) && count($result[$sid]['products']) > $productsPerStoreLimit) {
                    $matched = array_values(array_filter($result[$sid]['products'], fn ($x) => !empty($x['_matched'])));
                    $others  = array_values(array_filter($result[$sid]['products'], fn ($x) => empty($x['_matched'])));
                    $result[$sid]['products'] = array_slice(array_merge($matched, $others), 0, $productsPerStoreLimit);
                }

                unset($result[$sid]['_matched_by_store_name']);
                foreach ($result[$sid]['products'] as &$pp) {
                    unset($pp['_matched']);
                }
            }
        }

        // ✅ تعبئة category_ids للمتاجر التي جاءت من Product->store
        $this->hydrateMissingCategoryIds($result);

        // ترتيب المتاجر وإرجاع مصفوفة
        $final = array_values($result);
        usort($final, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $final;
    }


    public function getStoreDetailsWithProductsAndDiscounts($storeId)
    {
        /** @var \App\Models\User|null $store */
        $store = User::query()
            ->where('type', 'store')
            ->whereKey($storeId)
            ->with([
                'categories:id',
                'products' => function ($q) {
                    $q->with([
                        'activeDiscount' => function ($q2) {
                            $q2->select(
                                'discounts.id',
                                'discounts.product_id',
//                                'discounts.title',      // ✅ مهم
                                'discounts.new_price',
                                'discounts.start_date',
                                'discounts.end_date',
                                'discounts.status'
                            );
                        }
                    ])
                        ->select('id', 'store_id', 'name', 'price', 'status', 'unit', 'details', 'image')
                        ->orderBy('name');
                },
            ])
            ->first(['id', 'name', 'image', 'note', 'status', 'open_hour', 'close_hour', 'area_id']);

        if (!$store) {
            return null;
        }

        $productsWithDiscountsFirst = $store->products
            ->sortByDesc(fn ($product) => $product->activeDiscount ? 1 : 0)
            ->values();

        $storeArr = $this->storePayload($store);

        return [
            'store' => $storeArr,
            'categories'   => $store->categories->map(fn ($c) => ['id' => $c->id])->values(),
            'category_ids' => $store->categories->pluck('id')->values(),

            'products' => $productsWithDiscountsFirst->map(function ($product) {
                $discount = $product->activeDiscount;

                return [
                    'id'          => $product->id,
                    'name'        => $product->name,
                    'image'       => $product->image_url,
                    'image_url'   => $product->image_url,
                    'isAvailable' => $product->status === 'available',
                    'unit'        => $product->unit,
                    'details'     => $product->details,
                    'original_price' => (float) $product->price,
                    'new_price'      => $discount?->new_price !== null ? (float) $discount->new_price : null,
                    'discount_title' => $discount?->title ?? null,
                ];
            })->values(),
        ];
    }



    public function searchStoresAndProductsGrouped(
        int $areaId,
        int $categoryId,
        string $q,
        ?int $productsPerStoreLimit = 10
    ) {
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn (string $t): string => preg_quote($t, '/');

        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,
                '(^|[[:space:][:punct:]])(ال)?' . $re,
            ];
        };

        // 1) متاجر تطابق الاسم ضمن المنطقة + التصنيف
        $storesByName = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId))
            ->with(['categories:id'])
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

        // 2) المنتجات المطابقة + خصم فعّال (ضمن نفس شروط المنطقة + التصنيف)
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
                    ->whereHas('categories', fn ($qc) => $qc->where('categories.id', $categoryId));
            })
            ->select('products.id', 'products.name', 'products.price', 'products.store_id', 'products.image', 'products.details', 'products.status')
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

        $result = []; // keyed by store_id

        foreach ($storesByName as $s) {
            $result[$s->id] = $this->storePayload($s, [
                'products' => [],
                '_matched_by_store_name' => true,
            ]);
        }

        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                $result[$s->id] = $this->storePayload($s, [
                    'products' => [],
                ]);
            }

            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $productArr = [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'price'       => (float) $p->price,
                    'image'       => $p->image_url,
                    'isAvailable' => $p->status === 'available',
                    'details'     => $p->details,
                    '_matched'    => true,
                ];

                if ($p->activeDiscount?->new_price !== null) {
                    $productArr['new_price'] = (float) $p->activeDiscount->new_price;
                }

                $result[$s->id]['products'][] = $productArr;
            }
        }

        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->with(['activeDiscount' => function ($q2) {
                    $q2->select(
                        'discounts.id',
                        'discounts.product_id',
                        'discounts.new_price',
                        'discounts.start_date',
                        'discounts.end_date',
                        'discounts.status'
                    );
                }])
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id', 'name', 'price', 'store_id', 'image', 'details', 'status')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                $existing = collect($result[$sid]['products'])->keyBy('id');

                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;

                    $productArr = [
                        'id'          => $p->id,
                        'name'        => $p->name,
                        'price'       => (float) $p->price,
                        'isAvailable' => $p->status === 'available',
                        'details'     => $p->details,
                        'image'       => $p->image_url,
                    ];

                    if ($p->activeDiscount?->new_price !== null) {
                        $productArr['new_price'] = (float) $p->activeDiscount->new_price;
                    }

                    $result[$sid]['products'][] = $productArr;

                    if (!is_null($productsPerStoreLimit) && count($result[$sid]['products']) >= $productsPerStoreLimit) {
                        break;
                    }
                }

                if (!is_null($productsPerStoreLimit) && count($result[$sid]['products']) > $productsPerStoreLimit) {
                    $matched = array_values(array_filter($result[$sid]['products'], fn ($x) => !empty($x['_matched'])));
                    $others  = array_values(array_filter($result[$sid]['products'], fn ($x) => empty($x['_matched'])));
                    $result[$sid]['products'] = array_slice(array_merge($matched, $others), 0, $productsPerStoreLimit);
                }

                unset($result[$sid]['_matched_by_store_name']);
                foreach ($result[$sid]['products'] as &$pp) {
                    unset($pp['_matched']);
                }
            }
        }

        // تعبئة category_ids للمتاجر التي جاءت من Product->store
        $this->hydrateMissingCategoryIds($result);

        $final = array_values($result);
        usort($final, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $final;
    }

    public function getStoresByAreaAndCategoriesPaged(
        int $areaId,
        array $categoryIds,
        int $perPage = 20,
        string $matchMode = 'all'
    ) {
        $q = User::query()
            ->where('type', 'store')
            ->where('area_id', $areaId)
            ->with(['categories:id'])
            ->select('id', 'area_id', 'name', 'image', 'status', 'note', 'open_hour', 'close_hour');

        if ($matchMode === 'all') {
            $q->where(function ($qq) use ($categoryIds) {
                foreach ($categoryIds as $cid) {
                    $qq->whereHas('categories', fn ($q2) => $q2->where('categories.id', $cid));
                }
            });
        } else {
            $q->whereHas('categories', fn ($qq) => $qq->whereIn('categories.id', $categoryIds));
        }

        $paginator = $q->orderBy('name')->paginate($perPage);

        $paginator->getCollection()->transform(function (User $store) {
            return $this->storePayload($store);
        });

        return $paginator;
    }

    public function searchStoresAndProductsGroupedByCategories(
        int $areaId,
        array $categoryIds,
        string $q,
        ?int $productsPerStoreLimit = 10,
        string $matchMode = 'all'
    ) {
        // 1) تجهيز التوكنز
        $tokens = collect(preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t, 'UTF-8') >= 2)
            ->values();

        $escape = fn (string $t): string => preg_quote($t, '/');

        $buildPatterns = function (string $term) use ($escape) {
            $re = $escape($term);
            return [
                '^[[:space:]]*' . $re,
                '(^|[[:space:][:punct:]])(ال)?' . $re,
            ];
        };

        $storesBaseQuery = function ($qStore) use ($areaId, $categoryIds, $matchMode) {
            $qStore->where('type', 'store')->where('area_id', $areaId);

            if ($matchMode === 'all') {
                $qStore->where(function ($qq) use ($categoryIds) {
                    foreach ($categoryIds as $cid) {
                        $qq->whereHas('categories', fn ($q2) => $q2->where('categories.id', $cid));
                    }
                });
            } else {
                $qStore->whereHas('categories', fn ($qq) => $qq->whereIn('categories.id', $categoryIds));
            }
        };

        // 2) متاجر تطابق الاسم
        $storesByName = User::query()
            ->where(function ($qStore) use ($storesBaseQuery) {
                $storesBaseQuery($qStore);
            })
            ->with(['categories:id'])
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

        // 3) المنتجات المطابقة ضمن المنطقة + التصنيفات
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
            ->whereHas('store', function ($qs) use ($storesBaseQuery) {
                $storesBaseQuery($qs);
            })
            ->select('products.id', 'products.name', 'products.price', 'products.store_id', 'products.image', 'products.details', 'products.status')
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

        $result = []; // keyed by store_id

        // (أ) المتاجر التي طابقت بالاسم
        foreach ($storesByName as $s) {
            $result[$s->id] = $this->storePayload($s, [
                'products' => [],
                '_matched_by_store_name' => true,
            ]);
        }

        // (ب) المتاجر الناتجة عن تطابق المنتجات + إدراج المنتج المطابق
        foreach ($productsMatched as $p) {
            $s = $p->store;
            if (!$s) continue;

            if (!isset($result[$s->id])) {
                $result[$s->id] = $this->storePayload($s, [
                    'products' => [],
                ]);
            }

            $alreadyIds = array_column($result[$s->id]['products'], 'id');
            if (!in_array($p->id, $alreadyIds, true)) {
                $productArr = [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'price'       => (float) $p->price,
                    'isAvailable' => $p->status === 'available',
                    'image'       => $p->image_url,
                    'details'     => $p->details,
                    '_matched'    => true,
                ];

                if ($p->activeDiscount?->new_price !== null) {
                    $productArr['new_price'] = (float) $p->activeDiscount->new_price;
                }

                $result[$s->id]['products'][] = $productArr;
            }
        }

        // (ج) للمتاجر المطابقة بالاسم: أضف منتجاتها واحترم limit
        if (!empty($storeNameMatchedIds)) {
            $allProducts = Product::query()
                ->with(['activeDiscount' => function ($q2) {
                    $q2->select(
                        'discounts.id',
                        'discounts.product_id',
                        'discounts.new_price',
                        'discounts.start_date',
                        'discounts.end_date',
                        'discounts.status'
                    );
                }])
                ->whereIn('store_id', $storeNameMatchedIds)
                ->select('id', 'name', 'price', 'store_id', 'image', 'details', 'status')
                ->orderBy('name')
                ->get()
                ->groupBy('store_id');

            foreach ($storeNameMatchedIds as $sid) {
                if (!isset($result[$sid])) continue;

                $existing = collect($result[$sid]['products'])->keyBy('id');

                foreach ($allProducts->get($sid, collect()) as $p) {
                    if ($existing->has($p->id)) continue;

                    $productArr = [
                        'id'          => $p->id,
                        'name'        => $p->name,
                        'price'       => (float) $p->price,
                        'isAvailable' => $p->status === 'available',
                        'image'       => $p->image_url,
                        'details'     => $p->details,
                    ];

                    if ($p->activeDiscount?->new_price !== null) {
                        $productArr['new_price'] = (float) $p->activeDiscount->new_price;
                    }

                    $result[$sid]['products'][] = $productArr;

                    if (!is_null($productsPerStoreLimit) && count($result[$sid]['products']) >= $productsPerStoreLimit) {
                        break;
                    }
                }

                if (!is_null($productsPerStoreLimit) && count($result[$sid]['products']) > $productsPerStoreLimit) {
                    $matched = array_values(array_filter($result[$sid]['products'], fn ($x) => !empty($x['_matched'])));
                    $others  = array_values(array_filter($result[$sid]['products'], fn ($x) => empty($x['_matched'])));
                    $result[$sid]['products'] = array_slice(array_merge($matched, $others), 0, $productsPerStoreLimit);
                }

                unset($result[$sid]['_matched_by_store_name']);
                foreach ($result[$sid]['products'] as &$pp) {
                    unset($pp['_matched']);
                }
            }
        }

        // تعبئة category_ids للمتاجر التي جاءت من Product->store
        $this->hydrateMissingCategoryIds($result);

        // ترتيب وإرجاع
        $final = array_values($result);
        usort($final, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $final;
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
                'close_hour',
                'status'
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
                'phone' => $store->phone_display,
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

    public function findStoreDetailsForAdmin(int $storeId, ?int $adminAreaId = null): User
    {
        $query = User::query()
            ->where('type', 'store')
            ->whereKey($storeId)
            ->select([
                'id',
                'name',
                'user_name',
                'phone',
                'status',
                'open_hour',
                'close_hour',
                'image',
                'area_id',
                'note',
            ])
            ->with([
                'area' => fn ($q) => $q->withTrashed()->select('id', 'name'),
                'categories' => fn ($q) => $q->select('categories.id', 'categories.name'),

                // ✅ منتجات المتجر + الخصم النشط
                'products' => function ($q) {
                    $q->select([
                        'id',
                        'store_id',
                        'name',
                        'price',
                        'status',
                        'quantity',
                        'unit',
                        'details',
                        'image',
                    ])
                        ->with([
                            'activeDiscount' => function ($q) {
                                $q->select([
                                    'discounts.id',
                                    'discounts.product_id',
                                    'discounts.new_price',
                                    'discounts.start_date',
                                    'discounts.end_date',
                                    'discounts.status',
                                    'discounts.created_at',
                                    'discounts.updated_at',
                                ]);
                            }
                        ])
                        ->orderByRaw("CASE WHEN status = 'available' THEN 0 WHEN status = 'not_available' THEN 1 ELSE 2 END")
                        ->orderByDesc('created_at');
                },
            ]);

        if (!is_null($adminAreaId)) {
            $query->where('area_id', $adminAreaId);
        }

        /** @var \App\Models\User $store */
        $store = $query->firstOrFail();
        $store
            ->append(['phone_display', 'image_url'])
            ->makeHidden(['phone', 'image']);

        return $store;
    }

    public function findForUpdate(int $id): ?Product
    {
        /** @var Product|null $product */
        $product = Product::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        return $product;
    }
    public function updateStatus(Product $product, string $status): bool
    {
        $product->status = $status;
        return (bool) $product->save();
    }


}
