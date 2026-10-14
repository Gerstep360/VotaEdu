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
        Schema::table('users', function (Blueprint $table) {
            $table->string('ci', 20)->nullable()->unique()->after('username');
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('paternal_surname', 80)->nullable()->after('first_name');
            $table->string('maternal_surname', 80)->nullable()->after('paternal_surname');
            $table->boolean('must_change_password')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'ci',
                'first_name',
                'paternal_surname',
                'maternal_surname',
                'must_change_password',
            ]);
        });
    }
};
