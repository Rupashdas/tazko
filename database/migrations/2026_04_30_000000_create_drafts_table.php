<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('context_key', 255);
            $table->longText('content');
            $table->json('attachment_ids')->nullable();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['user_id', 'context_key']);
            $table->index('updated_at');
        });
    }

    public function down(): void {
        Schema::dropIfExists('drafts');
    }
};
