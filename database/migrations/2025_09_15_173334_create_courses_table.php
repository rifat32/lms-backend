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
            $table->boolean('is_free')->default(false);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('url')->nullable();
            $table->string('level')->nullable();
            $table->string('cover')->nullable();
            $table->string('preview_video')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('video_duration')->nullable();
            $table->string('course_preview_description')->nullable();
            $table->string('course_status')->nullable();
            $table->string('status_start_date')->nullable();
            $table->string('status_end_date')->nullable();
            $table->integer('number_of_students')->nullable();
            $table->string('course_view')->nullable();
            $table->boolean('is_featured')->nullable();
            $table->boolean('is_lock_lessons_in_order')->nullable();
            $table->string('access_duration')->nullable();
            $table->string('access_device_type')->nullable();
            $table->string('certificate_info')->nullable();
            $table->enum('pricing', ['is_one_time_purchase', 'price', 'sale_price', 'sale_end_date', 'enterprise_price', 'is_included_membership', 'is_affiliatable', 'point_of_a_course', 'price_info']);
            $table->foreignId('category_id')
                ->constrained('course_categories')
                ->cascadeOnDelete();
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
