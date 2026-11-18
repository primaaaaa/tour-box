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
        Schema::create('tour_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('custom_id')->nullable();
            $table->string('path_px')->nullable(); // Right (+X)
            $table->string('path_nx')->nullable(); // Left (-X)
            $table->string('path_py')->nullable(); // Top (+Y)
            $table->string('path_ny')->nullable(); // Bottom (-Y / Nadir)
            $table->string('path_pz')->nullable(); // Front (+Z)
            $table->string('path_nz')->nullable(); // Back (-Z)
            $table->boolean('has_nadir_censor')->default(false);
            $table->string('nadir_censor_type')->default('blur');
            $table->float('default_yaw')->default(0);
            $table->float('default_pitch')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'custom_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_views');
    }
};
