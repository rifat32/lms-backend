<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseIdToLessonProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasColumn('lesson_progresses', 'course_id')) {
            Schema::table('lesson_progresses', function (Blueprint $table) {
                $table->foreignId('course_id')->nullable()->constrained('courses')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('lesson_progresses', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });
    }
}
