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
    Schema::create('edges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained()->cascadeOnDelete();
            $table->string('source');        // إيش النود يلي بدأ منه الخط
            $table->string('target');        // إيش النود يلي بيخلص فيه الخط
            $table->string('source_handle')->nullable(); // من أي باب طلع؟ (مهم لـ Condition: true/false)
            $table->string('target_handle')->nullable(); // بأي باب دخل؟
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edges');
    }
};
