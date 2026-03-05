<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE products
            MODIFY unit ENUM(
                'غرام',
                'كيلوغرام',
                'ميلي غرام',
                'لتر',
                'ميلي ليتر',
                'مل',
                'مغ',
                'صندوق',
                'علبة',
                'حبة',
                'قطعة'
            ) DEFAULT 'غرام'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE products
            MODIFY unit ENUM(
                'غرام',
                'كيلوغرام',
                'لتر',
                'قطعة'
            ) DEFAULT 'غرام'
        ");
    }
};
