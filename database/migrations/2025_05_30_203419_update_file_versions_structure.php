<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('file_versions', function (Blueprint $table) {
            // Supprimer les anciennes colonnes si elles ne sont plus utilisées
            $table->dropColumn(['description', 'size', 'is_current_version', 'is_public']);

            // Ajouter les nouvelles colonnes
            $table->string('path')->after('type');
            $table->bigInteger('file_size')->after('path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null')->after('file_size');
            $table->boolean('is_final')->default(false)->after('uploaded_by');
            $table->boolean('is_active')->default(true)->after('is_final');
        });
    }

    public function down(): void
    {
        Schema::table('file_versions', function (Blueprint $table) {
            // Restaurer les anciennes colonnes
            $table->text('description')->nullable();
            $table->bigInteger('size')->nullable();
            $table->boolean('is_current_version')->default(false);
            $table->boolean('is_public')->default(false);

            // Supprimer les nouvelles colonnes
            $table->dropColumn(['path', 'file_size', 'uploaded_by', 'is_final', 'is_active']);
        });
    }
};
