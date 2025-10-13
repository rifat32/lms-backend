<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('subtitle')->nullable();
            $table->string('video_width')->nullable();
            $table->string('required_progress')->nullable();

            $table->string('preview_video_source_type')->nullable(); // HTML, YouTube, Vimeo, External Link, Embed
            $table->string('preview_video_url')->nullable();         // YouTube, Vimeo, External Link, or HTML video file
            $table->string('preview_video_poster')->nullable();      // Poster image for the video preview
            $table->text('preview_video_embed')->nullable();         // Embed iframe code for "Embed" type
            $table->boolean('pdf_read_completion_required')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn([
                'subtitle',
                "video_width",
                "required_progress",
                "preview_video_source_type",
                "preview_video_url",
                "preview_video_poster",
                "preview_video_embed",
                "pdf_read_completion_required",
            ]);
        });
    }
}
