<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 0, 100),
            'sale_price' => $this->faker->randomFloat(2, 0, 100),
            'price_start_date' => now(),
            'price_end_date' => now()->addMonths(1),
            'is_free' => false,
            'status' => Course::STATUS['DRAFT'],
            'status_start_date' => now(),
            'status_end_date' => now()->addMonths(1),
            'url' => $this->faker->slug,
            'level' => 'Beginner',
            'cover' => null,
            'preview_video_source_type' => Course::PREVIEW_VIDEO_SOURCE_TYPE['YOUTUBE'],
            'preview_video_url' => null,
            'preview_video_poster' => null,
            'preview_video_embed' => null,
            'duration' => $this->faker->numberBetween(1, 10),
            'video_duration' => $this->faker->numberBetween(1, 10),
            'course_preview_description' => $this->faker->sentence,
            'is_featured' => false,
            'is_lock_lessons_in_order' => false,
            'created_by' => User::factory(),
        ];
    }
}
