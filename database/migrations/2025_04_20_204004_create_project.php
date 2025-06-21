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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('storage')->default('local'); // e.g., 'local', 's3', 'google_drive'
            $table->string('path');
            $table->boolean('is_archived')->default(false);
            $table->foreignId('space_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Indexes
            $table->index('name');
            $table->index('space_id');
            $table->index('is_archived');
            $table->index(['space_id', 'is_archived']);
            
            // Unique constraint to ensure project names are unique within a space
            $table->unique(['space_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
};
