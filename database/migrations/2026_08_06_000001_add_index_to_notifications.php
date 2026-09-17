<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every notifications query in the app filters by exactly this pair
        // (Patient/Doctor/Admin DashboardController@notifications, plus the
        // unread-count check that likely runs on most page loads), and unlike
        // appointments/doctors/patients this table has no foreignId()
        // columns, so it never got an implicit FK index either — every one
        // of those queries is currently a full table scan.
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['user_id', 'user_role'], 'idx_notif_user');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notif_user');
        });
    }
};
