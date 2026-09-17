<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password', 255);
            $table->foreignId('category_id')->nullable()->constrained('doctor_categories')->nullOnDelete();
            $table->decimal('consultancy_fee', 10, 2)->default(0.00);
            $table->string('bio', 400)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
