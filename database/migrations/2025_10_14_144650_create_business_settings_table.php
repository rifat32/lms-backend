<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            // General Settings
            $table->string('general__main_color')->default('#227AFF');
            $table->string('general__secondary_color')->default('#000000');
            $table->string('general__accent_color')->default('rgba(34,122,255,1)');
            $table->string('general__danger_color')->default('rgba(255,57,69,1)');
            $table->string('general__warning_color')->default('rgba(255,168,0,1)');
            $table->string('general__success_color')->default('rgba(97,204,47,1)');
            $table->integer('general__featured_courses_count')->default(1);
            $table->string('general__loading_animation')->nullable();

            // Courses Settings
            $table->boolean('courses__import_demo_courses')->default(false);
            $table->string('courses__courses_page')->default('courses');
            $table->string('courses__courses_page_layout')->default('grid');
            $table->integer('courses__courses_per_row')->default(4);
            $table->integer('courses__courses_per_page')->default(9);
            $table->string('courses__load_more_type')->default('button');
            $table->string('courses__course_card_style')->default('default');
            $table->string('courses__course_card_info_position')->default('center');
            $table->string('courses__course_image_size')->nullable();
            $table->boolean('courses__lazy_loading')->default(true);
            $table->string('courses__category_slug')->default('stm_lms_course_category');
            $table->boolean('courses__show_featured_courses_on_top')->default(true);
            $table->integer('courses__featured_courses_count')->default(3);
            $table->boolean('courses__filters_on_archive_page')->default(true);

            // Course Settings
            $table->string('course__page_style')->default('default');
            $table->boolean('course__show_course_reviews')->default(true);
            $table->string('course__default_tab')->default('description');
            $table->boolean('course__use_emoji_in_quizzes')->default(true);
            $table->boolean('course__show_description_tab')->default(true);
            $table->boolean('course__show_curriculum_tab')->default(true);
            $table->boolean('course__show_faq_tab')->default(true);
            $table->boolean('course__show_notice_tab')->default(true);
            $table->json('course__course_levels')->nullable();
            $table->boolean('course__allow_presto_player')->default(true);
            $table->boolean('course__auto_enroll_free_courses')->default(true);
            $table->boolean('course__allow_reviews_non_enrolled')->default(false);
            $table->boolean('course__allow_basic_info_section')->default(true);
            $table->boolean('course__allow_course_requirements_section')->default(true);
            $table->boolean('course__allow_intended_audience_section')->default(true);
            $table->json('course__preferred_video_sources')->nullable();
            $table->json('course__preferred_audio_sources')->nullable();
            $table->boolean('course__bottom_sticky_panel')->default(true);
            $table->boolean('course__show_popular_courses')->default(true);
            $table->boolean('course__show_related_courses')->default(true);
            $table->boolean('course__disable_default_completion_image')->default(false);
            $table->string('course__failed_course_image')->nullable();
            $table->string('course__passed_course_image')->nullable();

            // Certificate Settings
            $table->integer('certificate__threshold')->default(70);
            $table->boolean('certificate__allow_instructor_create')->default(true);
            $table->boolean('certificate__use_current_student_name')->default(true);
            $table->json('certificate__builder_data')->nullable();

            // timestamps if missing

        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn([
                'general__main_color',
                'general__secondary_color',
                'general__accent_color',
                'general__danger_color',
                'general__warning_color',
                'general__success_color',
                'general__featured_courses_count',
                'general__loading_animation',
                'courses__import_demo_courses',
                'courses__courses_page',
                'courses__courses_page_layout',
                'courses__courses_per_row',
                'courses__courses_per_page',
                'courses__load_more_type',
                'courses__course_card_style',
                'courses__course_card_info_position',
                'courses__course_image_size',
                'courses__lazy_loading',
                'courses__category_slug',
                'courses__show_featured_courses_on_top',
                'courses__featured_courses_count',
                'courses__filters_on_archive_page',
                'course__page_style',
                'course__show_course_reviews',
                'course__default_tab',
                'course__use_emoji_in_quizzes',
                'course__show_description_tab',
                'course__show_curriculum_tab',
                'course__show_faq_tab',
                'course__show_notice_tab',
                'course__course_levels',
                'course__allow_presto_player',
                'course__auto_enroll_free_courses',
                'course__allow_reviews_non_enrolled',
                'course__allow_basic_info_section',
                'course__allow_course_requirements_section',
                'course__allow_intended_audience_section',
                'course__preferred_video_sources',
                'course__preferred_audio_sources',
                'course__bottom_sticky_panel',
                'course__show_popular_courses',
                'course__show_related_courses',
                'course__disable_default_completion_image',
                'course__failed_course_image',
                'course__passed_course_image',
                'certificate__threshold',
                'certificate__allow_instructor_create',
                'certificate__use_current_student_name',
                'certificate__builder_data',
            ]);
        });
    }
};
