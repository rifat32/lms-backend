<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('content_type');
            $table->string('content_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('duration')->nullable(); // duration in minutes
            $table->boolean('is_preview')->default(false);
            $table->boolean('is_time_locked')->default(false);
            $table->date('start_date')->nullable();
            $table->time('start_time')->nullable();
            $table->integer('unlock_day_after_purchase')->nullable();
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->json('files')->nullable();
            $table->json('materials')->nullable();
            $table->string('video_width')->nullable();
            $table->string('required_progress')->nullable();
            $table->string('preview_video_source_type')->nullable(); // HTML, YouTube, Vimeo, External Link, Embed
            $table->string('preview_video_url')->nullable();         // YouTube, Vimeo, External Link, or HTML video file
            $table->string('preview_video_poster')->nullable();      // Poster image for the video preview
            $table->text('preview_video_embed')->nullable();         // Embed iframe code for "Embed" type
            $table->boolean('pdf_read_completion_required')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
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
        Schema::dropIfExists('lessons');
    }
}
