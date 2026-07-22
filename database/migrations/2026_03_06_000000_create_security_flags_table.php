<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An audit trail of client-side security signals (currently: DevTools detected
 * on a protected page). These are heuristic, so the table is a review queue for
 * an admin — never an automatic ban. It answers "which accounts tripped the
 * detector, when, and from where".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('devtools')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_flags');
    }
};
