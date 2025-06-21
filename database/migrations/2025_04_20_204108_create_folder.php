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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('folders')->onDelete('cascade');
            $table->foreignId('space_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('origin')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->boolean('is_downloadable')->default(true);
            $table->string('icon')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('parent_id');
            $table->index('space_id');
            $table->index('project_id');
            $table->index('status');
            $table->index('updated_by');
            
            // For hierarchical queries
            $table->index(['space_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('folders');
    }
};
