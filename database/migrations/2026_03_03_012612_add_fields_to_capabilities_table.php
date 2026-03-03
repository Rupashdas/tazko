<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('capabilities', function (Blueprint $table) {
            if (!Schema::hasColumn('capabilities', 'module')) {
                $table->string('module')->after('label')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('capabilities', function (Blueprint $table) {
            if (Schema::hasColumn('capabilities', 'module')) {
                $table->dropColumn('module');
            }
        });
    }
};
