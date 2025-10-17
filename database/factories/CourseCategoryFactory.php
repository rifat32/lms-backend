<?php

namespace Database\Factories;

use App\Models\CourseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseCategoryFactory extends Factory
{
    protected $model = CourseCategory::class;

    public function definition()
    {
        $course_categories = [
            ['name' => 'Development', 'description' => 'Software and web development', 'parent_id' => null],
            ['name' => 'Data Science', 'description' => 'Data analysis and ML', 'parent_id' => null],
            ['name' => 'Design', 'description' => 'UI/UX and creative tools', 'parent_id' => null],
            ['name' => 'Marketing', 'description' => 'Digital marketing and SEO', 'parent_id' => null],
        ];

        $category = $this->faker->randomElement($course_categories);

        return [
            'name' => $category['name'],
            'description' => $category['description'],
            'parent_id' => $category['parent_id'],
        ];
    }
}
