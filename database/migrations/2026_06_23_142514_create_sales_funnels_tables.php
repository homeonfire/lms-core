<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Воронки продаж
        Schema::create('funnels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Этапы воронки
        Schema::create('funnel_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funnel_id')->constrained('funnels')->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('regular'); // regular, won, lost
            $table->integer('sort_order')->default(0);
            $table->string('color')->nullable(); // Цвет этапа
            $table->timestamps();
        });

        // 3. Добавляем этап воронки в таблицу заказов
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('funnel_stage_id')->nullable()->constrained('funnel_stages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['funnel_stage_id']);
            $table->dropColumn('funnel_stage_id');
        });

        Schema::dropIfExists('funnel_stages');
        Schema::dropIfExists('funnels');
    }
};
