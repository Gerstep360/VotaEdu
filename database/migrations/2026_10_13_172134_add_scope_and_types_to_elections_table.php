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
        Schema::table('elections', function (Blueprint $table) {
            $table->string('type', 30)->default('institucional')->after('title'); // curso, institucional, eleccion_estudiantil
            $table->foreignId('academic_year_id')->nullable()->after('type')->constrained('academic_years')->nullOnDelete();
            $table->text('cancellation_reason')->nullable()->after('status');
            $table->timestamp('scheduled_open_at')->nullable()->after('opened_at');
            $table->timestamp('scheduled_close_at')->nullable()->after('closed_at');
            $table->foreignId('parent_election_id')->nullable()->after('created_by')->constrained('elections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('elections', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['parent_election_id']);
            $table->dropColumn([
                'type',
                'academic_year_id',
                'cancellation_reason',
                'scheduled_open_at',
                'scheduled_close_at',
                'parent_election_id',
            ]);
        });
    }
};
