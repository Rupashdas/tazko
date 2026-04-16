<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // External-sharing pathway. Separate from internal access so that
        // enabling, disabling, or expiring a public link never affects
        // project members' ability to view the underlying attachment.
        Schema::create('attachment_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attachment_id')->constrained()->cascadeOnDelete();
            // 48 url-safe chars ≈ 286 bits of entropy. Unguessable.
            $table->string('token', 48)->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            // Kill switch that preserves the row (and its analytics + token
            // history). enabled=false means the link is dead even if
            // expires_at is still in the future.
            $table->boolean('enabled')->default(true);
            // When true, the public page offers a download button and
            // ?download=1 serves with Content-Disposition: attachment.
            $table->boolean('allow_download')->default(true);
            // NULL = no expiry.
            $table->timestamp('expires_at')->nullable();
            // Lightweight trust-signal analytics — not full auditing.
            $table->unsignedInteger('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index('attachment_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('attachment_shares');
    }
};
