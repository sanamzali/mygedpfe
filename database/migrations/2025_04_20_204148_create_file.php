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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('space_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('storage_path');
            $table->boolean('is_indexed')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->string('password')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->json('metadata')->nullable();
            $table->foreignId('folder_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Optional indexes
            $table->index(['project_id', 'space_id']);
            $table->index('folder_id');
            $table->index('is_indexed');
            $table->index('is_encrypted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
};
