<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletTopUpByPhoneSeeder extends Seeder
{
    /**
     * phone normalization:
     * - يحذف أي محارف غير الأرقام أو +
     * - يحوّل بادئة 00963 إلى +963
     * - يحوّل 009xx إلى +9xx بشكل عام
     * - يزيل أصفار البداية الزائدة
     */
    protected function normalizePhone(string $raw): string
    {
        $p = preg_replace('/[^0-9+]+/', '', trim($raw));

        // 009... → +9...
        if (strpos($p, '009') === 0) {
            $p = '+' . substr($p, 2);
        }

        // 0963 أو 00963 → +963
        if (strpos($p, '0963') === 0) {
            $p = '+963' . substr($p, 4);
        }

        // إذا بدأ بـ 00 مرّة ثانية لأي حالة أخرى
        if (strpos($p, '00') === 0) {
            $p = '+' . substr($p, 2);
        }

        // لو كان بدون + وبـ 0 محلية طوّلها كما هي (نتركها محلية)
        return $p;
    }

    public function run(): void
    {
        // اكتب هنا أرقام الهواتف والمبالغ المراد شحنها
        // المفتاح: رقم الهاتف بأي صيغة عندك، القيمة: المبلغ المطلوب إضافته
        $topUps = [
//            '00963935971524' => '150000.00',
            // أضف غيرها:
            '00963935971524'     => '1000000.00',
            // '+963935971524'  => '5000.00',
        ];

        DB::transaction(function () use ($topUps) {

            foreach ($topUps as $rawPhone => $amount) {
                $shadow = $this->normalizePhone($rawPhone);

                $user = User::where('phone', $rawPhone)
                    ->orWhere('phone', $shadow)
                    ->orWhere('phone_shadow', $shadow)
                    ->first();

                if (!$user) {
                    $this->command->warn("لم يتم العثور على مستخدم برقم: {$rawPhone} (shadow: {$shadow})");
                    continue;
                }

                // تأكد من وجود phone_shadow محدث
                if ($user->phone_shadow !== $shadow) {
                    $user->phone_shadow = $shadow;
                }

                // أضف المبلغ للمحفظة
                // نستخدم bcmul/bcadd لو احتجت دقة أعلى، لكن بما أن الحقل DECIMAL(10,2) فالجمع المباشر يكفي.
                $user->increment('wallet_balance', (float) $amount);

                $this->command->info("تم شحن {$amount} إلى محفظة المستخدم ID={$user->id} ({$user->phone})");
            }
        });
    }
}
