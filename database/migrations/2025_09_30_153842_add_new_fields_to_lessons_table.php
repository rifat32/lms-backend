<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewFieldsToLessonsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->integer('duration')->nullable()->after('title'); // duration in minutes
            $table->boolean('is_preview')->default(false)->after('duration');
            $table->boolean('is_time_locked')->default(false)->after('is_preview');
            $table->date('start_date')->nullable()->after('is_time_locked');
            $table->time('start_time')->nullable()->after('start_date');
            $table->integer('unlock_day_after_purchase')->nullable()->after('start_time');
            $table->text('description')->nullable()->after('unlock_day_after_purchase');
            $table->longText('content')->nullable()->after('description');
            $table->json('files')->nullable()->after('content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'duration',
                'is_preview',
                'is_time_locked',
                'start_date',
                'start_time',
                'unlock_day_after_purchase',
                'description',
                'content',
                'files'
            ]);
        });
    }
}
