<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('comments', function (Blueprint $table) {
            // Drop the old task_id FK
            $table->dropForeign(['task_id']);
            $table->dropColumn('task_id');

            // Add polymorphic columns + project context
            $table->foreignId('project_id')->after('id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('commentable'); // commentable_type + commentable_id
            $table->boolean('is_edited')->default(false)->after('body');
        });
    }

    public function down(): void {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropMorphs('commentable');
            $table->dropForeign(['project_id']);
            $table->dropColumn(['project_id', 'is_edited']);
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
        });
    }
};
