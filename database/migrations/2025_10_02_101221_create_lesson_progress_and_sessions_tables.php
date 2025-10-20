<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonProgressAndSessionsTables extends Migration
{

    public function up()
    {
        Schema::create('lesson_progresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->integer('total_time_spent')->default(0); // seconds
            $table->boolean('is_completed')->default(false);
            $table->timestamp('last_accessed')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id',"course_id"]);
        });

        Schema::create('lesson_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->timestamps();

            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_sessions');
        Schema::dropIfExists('lesson_progresses');
    }
}
