<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingRequest extends FormRequest
{
      /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [

       // General Settings
            'general__main_color' => 'sometimes|string|nullable',
            'general__secondary_color' => 'sometimes|string|nullable',
            'general__accent_color' => 'sometimes|string|nullable',
            'general__danger_color' => 'sometimes|string|nullable',
            'general__warning_color' => 'sometimes|string|nullable',
            'general__success_color' => 'sometimes|string|nullable',
            'general__featured_courses_count' => 'sometimes|integer|min:0',
            'general__popular_courses_count' => 'sometimes|integer|min:0',
            



            'general__loading_animation' => 'sometimes|nullable',

            // Courses Settings
            'courses__import_demo_courses' => 'sometimes|boolean',
            'courses__courses_page' => 'sometimes|string|nullable',
            'courses__courses_page_layout' => 'sometimes|string|in:grid,list,masonry|nullable',
            'courses__courses_per_row' => 'sometimes|integer|min:1',
            'courses__courses_per_page' => 'sometimes|integer|min:1',
            'courses__load_more_type' => 'sometimes|string|in:button,infinite_scroll|nullable',
            'courses__course_card_style' => 'sometimes|string|in:default,price_on_hover,scale_on_hover|nullable',
            'courses__course_card_info_position' => 'sometimes|string|in:left,center,right|nullable',
            'courses__course_image_size' => 'sometimes|string|nullable',
            'courses__lazy_loading' => 'sometimes|boolean',
            'courses__category_slug' => 'sometimes|string|nullable',
            'courses__show_featured_courses_on_top' => 'sometimes|boolean',
            'courses__featured_courses_count' => 'sometimes|integer|min:0',
            'courses__filters_on_archive_page' => 'sometimes|boolean',

            // Course Settings
            'course__page_style' => 'sometimes|string|nullable',
            'course__show_course_reviews' => 'sometimes|boolean',
            'course__default_tab' => 'sometimes|string|nullable',
            'course__use_emoji_in_quizzes' => 'sometimes|boolean',
            'course__show_description_tab' => 'sometimes|boolean',
            'course__show_curriculum_tab' => 'sometimes|boolean',
            'course__show_faq_tab' => 'sometimes|boolean',
            'course__show_notice_tab' => 'sometimes|boolean',
            'course__course_levels' => 'sometimes|array|nullable',
            'course__allow_presto_player' => 'sometimes|boolean',
            'course__auto_enroll_free_courses' => 'sometimes|boolean',
            'course__allow_reviews_non_enrolled' => 'sometimes|boolean',
            'course__allow_basic_info_section' => 'sometimes|boolean',
            'course__allow_course_requirements_section' => 'sometimes|boolean',
            'course__allow_intended_audience_section' => 'sometimes|boolean',
            'course__preferred_video_sources' => 'sometimes|array|nullable',
            'course__preferred_audio_sources' => 'sometimes|array|nullable',
            'course__bottom_sticky_panel' => 'sometimes|boolean',
            'course__show_popular_courses' => 'sometimes|boolean',
            'course__show_related_courses' => 'sometimes|boolean',
            'course__disable_default_completion_image' => 'sometimes|boolean',
            'course__failed_course_image' => 'sometimes|nullable',
            'course__passed_course_image' => 'sometimes|nullable',

            // Certificate Settings
            'certificate__threshold' => 'sometimes|integer|min:0|max:100',
            'certificate__allow_instructor_create' => 'sometimes|boolean',
            'certificate__use_current_student_name' => 'sometimes|boolean',
            'certificate__builder_data' => 'sometimes|array|nullable',

            // Stripe (if still used)
            'stripe_enabled' => 'sometimes|boolean',
            'STRIPE_KEY' => 'sometimes|string|nullable|required_if:stripe_enabled,true',
            'STRIPE_SECRET' => 'sometimes|string|nullable|required_if:stripe_enabled,true',


        ];

    }

          public function messages()
    {
        return [
            'courses__courses_page_layout.in' => 'The selected courses page layout is invalid. Valid options: grid, list, masonry.',
            'courses__courses_per_row.min' => 'The courses per row must be at least 1.',
            'courses__courses_per_page.min' => 'The courses per page must be at least 1.',
            'courses__load_more_type.in' => 'The selected courses load more type is invalid. Valid options: button, infinite_scroll.',
            'courses__course_card_style.in' => 'The selected courses course card style is invalid. Valid options: default, price_on_hover, scale_on_hover.',
            'courses__course_card_info_position.in' => 'The selected courses course card info position is invalid. Valid options:left, center, right.',
        ];
    }


}
