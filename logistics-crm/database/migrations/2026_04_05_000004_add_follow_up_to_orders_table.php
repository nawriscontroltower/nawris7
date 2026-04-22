<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تتبع المتابعة التشغيلية للطلبات المتعثرة (ملاحظات + آخر اتصال).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->text('follow_up_notes')->nullable()->after('notes');
            $table->timestamp('last_follow_up_at')->nullable()->after('follow_up_notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['follow_up_notes', 'last_follow_up_at']);
        });
    }
};
