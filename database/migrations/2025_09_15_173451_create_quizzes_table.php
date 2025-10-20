<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuizzesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('time_limit')->nullable(); // store in minutes
            $table->string('time_unit')->nullable()->default('Hours'); // optional: Hours/Minutes
            $table->string('style')->nullable()->default('pagination'); // quiz style
            $table->boolean('is_randomized')->default(false);
            $table->boolean('allow_retake_after_pass')->default(false);
            $table->integer('max_attempts')->nullable(); // e.g., 4
            $table->integer('points_cut_after_retake')->nullable(); // e.g., 20%
            $table->integer('passing_grade')->default(50); // in percent
            $table->integer('question_limit')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quizzes');
    }
}
