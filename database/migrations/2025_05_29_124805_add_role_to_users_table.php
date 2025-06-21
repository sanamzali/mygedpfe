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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['facebook_id', 'github_id', 'google_id', 'type']);
            $table->string('role')->default('user')->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('facebook_id')->nullable();
            $table->string('github_id')->nullable();
            $table->string('google_id')->nullable();
            $table->string('type')->nullable();
            $table->dropColumn('role');
        });
    }
};
