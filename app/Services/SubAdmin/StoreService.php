<?php

namespace App\Services\SubAdmin;

use App\Models\User;
use App\Repositories\Contracts\StoreRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StoreService
{
    public function __construct(
        protected StoreRepositoryInterface $storeRepository
    ) {
    }

    /**
     * جلب المتاجر في نفس منطقة الأدمن الحالي.
     */
    public function getStoresForCurrentAdminArea(Request $request,int $perPage = 20)
    {

        $areaId = (int) $request->area_id;

        return $this->storeRepository->getStoresByAreaForAdmin($areaId, $perPage);
    }

    /**
     * إنشاء متجر جديد مع تصنيفات.
     */
    public function createStore(array $payload): User
    {
        return DB::transaction(function () use ($payload) {

            $canonicalPhone = $this->normalizeCanonical00963($payload['phone']);
            if (User::where('phone', $canonicalPhone)->exists()) {
                throw ValidationException::withMessages([
                    'phone' => 'هذا الرقم مستخدم من قبل.',
                ]);
            }


            $categoryIds = $payload['category_ids'] ?? [];
            unset($payload['category_ids']);

            $storeData = [
                'name'           => $payload['name'],
                'user_name'      => $payload['user_name'] ?? null,
                'phone'          => $canonicalPhone,
                'area_id'        => $payload['area_id'] ?? null,
                'open_hour'      => $payload['open_hour'] ?? null,
                'close_hour'     => $payload['close_hour'] ?? null,
                'status'         => isset($payload['status']) ? (bool)$payload['status'] : true,
                'image'          => $payload['image'] ?? null,
                'type'           => 'store',
                'password'       => Hash::make($payload['password']),
                'note'           => $payload['note'] ?? null,
            ];

            $storeData['phone_shadow'] = $canonicalPhone;

            $store = $this->storeRepository->createStore($storeData, $categoryIds);

            return $store;
        });
    }


    /**
     * تعديل متجر موجود.
     *
     * - يمكن تعديل أي حقل مرسَل فقط.
     */
    public function updateStore(User $store, array $payload): User
    {
        return DB::transaction(function () use ($store, $payload) {

            // نبدأ بكل الحقول المرسلة كما هي
            $updateData  = $payload;
            $categoryIds = null;

            /** ========= الهاتف الأساسي ========= */
            if (isset($payload['phone'])) {
                // لو فاضية، ما منطبعها، فقط لو فيها قيمة
                if ($payload['phone'] !== null && $payload['phone'] !== '') {
                    $canonicalPhone = $this->normalizeCanonical00963($payload['phone']);

                    $exists = User::where('phone', $canonicalPhone)
                        ->where('id', '!=', $store->id)
                        ->exists();

                    if ($exists) {
                        throw ValidationException::withMessages([
                            'phone' => 'هذا الرقم مستخدم من قبل.',
                        ]);
                    }

                    $updateData['phone']        = $canonicalPhone;
                    $updateData['phone_shadow'] = $canonicalPhone;
                } else {
                    unset($updateData['phone']);
                }
            }

            /** ========= كلمة المرور ========= */
            if (!empty($payload['password'] ?? null)) {
                $updateData['password'] = Hash::make($payload['password']);
            } else {
                unset($updateData['password']);
            }

            /** ========= التصنيفات ========= */
            if (array_key_exists('category_ids', $payload)) {
                $categoryIds = $payload['category_ids'] ?? [];
                unset($updateData['category_ids']);
            }

            // باقي الحقول (name, user_name, area_id, open_hour, close_hour, status, note, image, ...)
            // كلها موجودة بالفعل في $updateData إن كانت مرسلة من الطلب.

            $updatedStore = $this->storeRepository->updateStore($store, $updateData, $categoryIds);

            return $updatedStore;
        });
    }


    /**
     * حذف متجر معيّن (Soft Delete) ضمن منطقة معيّنة للأدمن.
     */
    public function deleteStoreForAdmin(Request $request, int $storeId): bool
    {
        $areaId = (int) $request->area_id;

        return $this->storeRepository->deleteStoreByIdForAdmin($storeId, $areaId);
    }


    /** ======================
     *  Helpers – تطبيع الأرقام
     *  ====================== */

    /**
     * الصيغة الداخلية القياسية للتخزين/المقارنة: 00963xxxxxxxxx
     * تقبل: 09xxxxxxxx
     */
    private function normalizeCanonical00963(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone); // أرقام فقط

        // نقبل فقط 09xxxxxxxx في التسجيل (القواعد أعلاه تضمن هذا)
        if (preg_match('/^09\d{8}$/', $digits)) {
            return '00963' . substr($digits, 1); // حذف الـ 0 واستبدالها بـ 00963
        }

        // إن وصل بصيغة مخزّنة مسبقًا
        if (preg_match('/^00963\d{9}$/', $digits)) {
            return $digits;
        }

        // أي صيغة أخرى نرفضها (لأننا اشترطنا 09 في التسجيل)
        throw new \InvalidArgumentException('صيغة رقم غير مسموح بها.');
    }



}
