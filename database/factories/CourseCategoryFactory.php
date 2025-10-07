<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourseCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $courseCategories = [
            (object)[
                'name' => 'Development',
                'description' => 'Courses related to software and web development.',
                'parent_id' => null,
            ],
            (object)[
                'name' => 'Web Development',
                'description' => 'Covers front-end, back-end, and full-stack web technologies.',
                'parent_id' => 1,
            ],
            (object)[
                'name' => 'Frontend Development',
                'description' => 'Learn HTML, CSS, JavaScript, and modern frameworks.',
                'parent_id' => 2,
            ],
            (object)[
                'name' => 'Backend Development',
                'description' => 'Covers server-side programming and databases.',
                'parent_id' => 2,
            ],
            (object)[
                'name' => 'Data Science',
                'description' => 'Learn data analysis, visualization, and machine learning.',
                'parent_id' => 1,
            ],
            (object)[
                'name' => 'Design',
                'description' => 'Covers UI/UX, graphic design, and creative tools.',
                'parent_id' => null,
            ],
            (object)[
                'name' => 'Graphic Design',
                'description' => 'Focus on visual communication and design tools like Photoshop.',
                'parent_id' => 6,
            ],
            (object)[
                'name' => 'UI/UX Design',
                'description' => 'Learn user experience and interface design principles.',
                'parent_id' => 6,
            ],
            (object)[
                'name' => 'Marketing',
                'description' => 'Digital and traditional marketing courses.',
                'parent_id' => null,
            ],
            (object)[
                'name' => 'Digital Marketing',
                'description' => 'SEO, social media, and online advertising strategies.',
                'parent_id' => 9,
            ],
        ];

        return [
            //
        ];
    }
}
