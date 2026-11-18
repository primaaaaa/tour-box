<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tour_views', function (Blueprint $table) {
            $table->string('view_type')->default('cubemap')->after('custom_id');
            $table->longText('panels_data')->nullable()->after('path_nz');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_views', function (Blueprint $table) {
            $table->dropColumn(['view_type', 'panels_data']);
        });
    }
};
