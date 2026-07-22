<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-user exemption from the DevTools guard. False (guard on) for everyone by
 * default; an admin flips it to true to let a trusted account use DevTools.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('devtools_guard_disabled')->default(false)->after('violation_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('devtools_guard_disabled');
        });
    }
};
