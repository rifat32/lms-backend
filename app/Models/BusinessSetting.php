<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;


    protected $fillable = [
        'STRIPE_KEY',
        "STRIPE_SECRET",
        'stripe_enabled',
          // General Settings
        'general__main_color',
        'general__secondary_color',
        'general__accent_color',
        'general__danger_color',
        'general__warning_color',
        'general__success_color',
        'general__featured_courses_count',
        'general__loading_animation',

        // Courses Settings
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

        // Course Settings
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

        // Certificate Settings
        'certificate__threshold',
        'certificate__allow_instructor_create',
        'certificate__use_current_student_name',
        'certificate__builder_data',
    ];

     protected $casts = [
        // Boolean fields
        'courses__import_demo_courses' => 'boolean',
        'courses__lazy_loading' => 'boolean',
        'courses__show_featured_courses_on_top' => 'boolean',
        'courses__filters_on_archive_page' => 'boolean',
        'course__show_course_reviews' => 'boolean',
        'course__use_emoji_in_quizzes' => 'boolean',
        'course__show_description_tab' => 'boolean',
        'course__show_curriculum_tab' => 'boolean',
        'course__show_faq_tab' => 'boolean',
        'course__show_notice_tab' => 'boolean',
        'course__allow_presto_player' => 'boolean',
        'course__auto_enroll_free_courses' => 'boolean',
        'course__allow_reviews_non_enrolled' => 'boolean',
        'course__allow_basic_info_section' => 'boolean',
        'course__allow_course_requirements_section' => 'boolean',
        'course__allow_intended_audience_section' => 'boolean',
        'course__bottom_sticky_panel' => 'boolean',
        'course__show_popular_courses' => 'boolean',
        'course__show_related_courses' => 'boolean',
        'course__disable_default_completion_image' => 'boolean',
        'certificate__allow_instructor_create' => 'boolean',
        'certificate__use_current_student_name' => 'boolean',

        // JSON fields
        'course__course_levels' => 'array',
        'course__preferred_video_sources' => 'array',
        'course__preferred_audio_sources' => 'array',
        'certificate__builder_data' => 'array',
    ];

    protected $hidden = [
        "STRIPE_SECRET",
        "pivot"
    ];

    public function getCourseFailedCourseImageAttribute($value)
{
    if (empty($value)) return null;
    $folder_path = "business_1/course__failed_course_image";
    return asset("storage-proxy/{$folder_path}/{$value}");
}

public function getCoursePassedCourseImageAttribute($value)
{
    if (empty($value)) return null;
    $folder_path = "business_1/course__passed_course_image";
    return asset("storage-proxy/{$folder_path}/{$value}");
}

public function getGeneralLoadingAnimationAttribute($value)
{
    if (empty($value)) return null;
    $folder_path = "business_1/general__loading_animation";
    return asset("storage-proxy/{$folder_path}/{$value}");
}
}
