<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->string('share_uid')->unique();
            $table->foreignId('shared_with')->constrained('users')->onDelete('cascade');
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->json('permissions');
            $table->date('shared_on');
            $table->string('type'); // e.g., 'user', 'team', 'public'
            $table->string('status')->default('active'); // e.g., 'active', 'expired', 'revoked'
            $table->date('expiration_date')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('share_uid');
            $table->index(['file_id', 'shared_with']);
            $table->index('status');
            $table->index('expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fileShares');
    }
};
