<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuestionCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('question_categories', function (Blueprint $table) {
            $table->id();
              $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->unsignedBigInteger('parent_question_category_id')->nullable();
            $table->foreign('parent_question_category_id')
                  ->references('id')
                  ->on('question_categories')
                  ->onDelete('cascade');
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
        Schema::dropIfExists('question_categories');
    }
}
