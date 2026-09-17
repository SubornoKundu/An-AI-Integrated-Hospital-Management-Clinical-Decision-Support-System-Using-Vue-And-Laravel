<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('role', 20); // patient | doctor | admin
            $table->string('code'); // hashed 6-digit code
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->boolean('verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['email', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
