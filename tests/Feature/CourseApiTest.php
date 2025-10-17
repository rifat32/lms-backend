<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class CourseApiTest extends TestCase
{
    use DatabaseMigrations;

    protected $admin_user;
    protected $student_user;

    /**
     * Robust logging helper
     */
    public function log_message(mixed $message): void
    {
        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $timestamp = now()->format('Y-m-d H:i:s');
        Log::channel('single')->debug("[{$timestamp}] {$message}");
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->log_message('===== setUp() START =====');

      

        // Seed roles
        // Role::firstOrCreate(['name' => 'admin']);
        // Role::firstOrCreate(['name' => 'student']);
        // $this->log_message('Roles created or exist');

        // Passport install
        Artisan::call('passport:install', ['--force' => true]);
        $this->log_message('Passport installed');

        // Seed other necessary data
        Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
        $this->log_message('Database seeded');

        // Create admin user
        $this->admin_user = User::factory()->create([
            'password' => bcrypt('12345678@We'),
        ]);
        $this->admin_user->assignRole('admin');
        $this->log_message('Admin user created (ID: ' . $this->admin_user->id . ')');

        // Create student user
        $this->student_user = User::factory()->create([
            'password' => bcrypt('12345678@We'),
        ]);
        $this->student_user->assignRole('student');
        $this->log_message('Student user created (ID: ' . $this->student_user->id . ')');

        $this->log_message('===== setUp() END =====');
    }

    /** @test */
    public function admin_can_create_course()
    {
        $this->log_message('>>> admin_can_create_course() START');

        $payload = [
            'title' => 'Intro to PHP',
            'description' => 'Learn PHP the fun way!',
            'category_ids' => [],   // required
            'cover' => '',          // optional
            'status' => 'draft',    // must be draft|published|archived
        ];

        $this->log_message('Payload prepared');

        $response = $this->actingAs($this->admin_user, 'api')
                         ->postJson('/api/v1.0/courses', $payload);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course created successfully',
                 ]);

        $this->assertDatabaseHas('courses', [
            'title' => 'Intro to PHP',
        ]);

        $this->log_message('>>> admin_can_create_course() END');
    }

    /** @test */
    public function admin_can_update_course()
    {
        $this->log_message('>>> admin_can_update_course() START');

        $course = Course::factory()->create([
            'created_by' => $this->admin_user->id,
        ]);

        $this->log_message('Existing course created (ID: ' . $course->id . ')');

        $payload = [
            'id' => $course->id,
            'title' => 'Updated Course Title',
            'status' => 'draft',           // required
            'category_ids' => [],          // required
            'cover' => UploadedFile::fake()->image('new_cover.png'),
        ];

        $response = $this->actingAs($this->admin_user, 'api')
                         ->putJson('/api/v1.0/courses', $payload);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Course updated successfully',
                 ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'Updated Course Title',
        ]);

        $this->log_message('>>> admin_can_update_course() END');
    }

    /** @test */
    public function admin_can_view_courses_list()
    {
        $this->log_message('>>> admin_can_view_courses_list() START');

        Course::factory()->count(3)->create();
        $this->log_message('3 courses created for listing test');

        $response = $this->actingAs($this->admin_user, 'api')
                         ->getJson('/api/v1.0/courses');

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Courses retrieved successfully',
                 ]);

        $this->log_message('>>> admin_can_view_courses_list() END');
    }
}
