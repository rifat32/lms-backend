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
            $table->string('source_type')->nullable();
            $table->longText('html')->nullable();
            $table->longText('thumbnail')->nullable();
            $table->longText('video')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('video_width')->nullable();
            $table->string('required_progress')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('vimeo_url')->nullable();
            $table->string('external_link_url')->nullable();
            $table->longText('embed_iframe')->nullable();
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
                'source_type',
                "html",
                "thumbnail",
                "video",
                "subtitle",
                "video_width",
                "required_progress",
                "youtube_url",
                "vimeo_url",
                "external_link_url",
                "embed_iframe",
            ]);
        });
    }
}
