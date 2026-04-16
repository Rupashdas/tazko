<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            // Tenancy anchor. Every attachment belongs to a project — this is
            // what the Files tab aggregates on and what access checks use.
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Morph parent — nullable because an upload happens BEFORE the
            // parent (task / comment / project description) is saved. The
            // AttachmentSyncService links these on parent save.
            $table->nullableMorphs('attachable');

            $table->string('name');         // original filename shown in UI
            $table->string('path');         // disk-relative storage path
            // Which disk the file lives on. Default 'local' (private) today;
            // future S3 migration becomes a per-row flag, not a schema change.
            $table->string('disk', 32)->default('local');
            $table->string('mime_type')->nullable();
            // Cached from mime; powers Files-tab filtering without parsing
            // strings on every read.
            $table->enum('file_type', ['image', 'video', 'audio', 'file'])
                ->default('file');
            $table->unsignedBigInteger('size')->default(0);

            // Flag that distinguishes "orphan waiting to be linked" from
            // "committed part of saved content". Reaper deletes orphans where
            // this is NULL and created_at is older than the configured TTL.
            $table->timestamp('committed_at')->nullable();

            $table->timestamps();

            // Files-tab query: WHERE project_id=? AND committed_at IS NOT NULL.
            // Reaper query: WHERE committed_at IS NULL AND created_at < ?.
            $table->index(['project_id', 'committed_at']);
            $table->index('committed_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('attachments');
    }
};
