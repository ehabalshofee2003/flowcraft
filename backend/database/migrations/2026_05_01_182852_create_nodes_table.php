<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete(); // لو حذفنا الـ Workflow، تنحذف النودات
            $table->string('node_id'); // الـ ID يلي بتجيبها من React Flow (مثلاً node_123456)
            $table->string('type');     // نوع النود (log, color, condition)
            $table->json('data')->nullable(); // البيانات الداخلية (النص، اللون، إلخ)
            $table->json('position')->nullable(); // موقع النود على الشاشة {x: 100, y: 200}
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
