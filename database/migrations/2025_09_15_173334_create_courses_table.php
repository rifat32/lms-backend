<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->date("price_start_date")->nullable();
            $table->date("price_end_date")->nullable();

            $table->boolean('is_free')->default(false);

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->date('status_start_date')->nullable();
            $table->date('status_end_date')->nullable();

            $table->string('url')->nullable();
            $table->string('level')->nullable();
            $table->string('cover')->nullable();



            $table->string('preview_video_source_type')->nullable(); // HTML, YouTube, Vimeo, External Link, Embed
            $table->string('preview_video_url')->nullable();         // YouTube, Vimeo, External Link, or HTML video file
            $table->string('preview_video_poster')->nullable();      // Poster image for the video preview
            $table->text('preview_video_embed')->nullable();         // Embed iframe code for "Embed" type



            $table->string('duration')->nullable();
            $table->string('video_duration')->nullable();
            $table->string('course_preview_description')->nullable();


            $table->boolean('is_featured')->nullable();
            $table->boolean('is_lock_lessons_in_order')->nullable();

          


            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
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
        Schema::dropIfExists('courses');
    }
}
