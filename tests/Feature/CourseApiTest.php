<?php





namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class CourseApiTest extends TestCase
{
    use DatabaseMigrations;

    protected $admin_user;
    protected $student_user;
    protected $course;

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

        // Create a course for testing
        $this->course = Course::factory()->create([
            'created_by' => $this->admin_user->id,
            'status' => 'published',
        ]);
        $this->log_message('Course created (ID: ' . $this->course->id . ')');

        $this->log_message('===== setUp() END =====');
    }

    /** @test */
    public function create_course()
    {
        $this->log_message('>>> create_course() START');

        $category = CourseCategory::factory()->create();
        
        $payload = [
            'title' => 'Intro to PHP',
            'description' => 'Learn PHP the fun way!',
            'category_ids' => [$category->id],
            'cover' => '', // optional
            'status' => 'draft', // must be draft|published|archived
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

        $this->log_message('>>> create_course() END');
    }

    /** @test */
    public function update_course()
    {
        $this->log_message('>>> update_course() START');

        $category = CourseCategory::factory()->create();
        
        $payload = [
            'id' => $this->course->id,
            'title' => 'Updated Course Title',
            'description' => 'Updated description',
            'status' => 'published',
            'category_ids' => [$category->id],
            'cover' => '', // optional for update
        ];

        $response = $this->actingAs($this->admin_user, 'api')
                         ->putJson('/api/v1.0/courses', $payload);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course updated successfully',
                 ]);

        $this->assertDatabaseHas('courses', [
            'id' => $this->course->id,
            'title' => 'Updated Course Title',
        ]);

        $this->log_message('>>> update_course() END');
    }

    /** @test */
    public function partial_update_course()
    {
        $this->log_message('>>> partial_update_course() START');

        $category = CourseCategory::factory()->create();
        
        $payload = [
            'id' => $this->course->id,
            'title' => 'Partially Updated Course',
            'category_ids' => [$category->id],
        ];

        $response = $this->actingAs($this->admin_user, 'api')
                         ->putJson('/api/v1.0/courses/partial', $payload);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course updated successfully',
                 ]);

        $this->assertDatabaseHas('courses', [
            'id' => $this->course->id,
            'title' => 'Partially Updated Course',
        ]);

        $this->log_message('>>> partial_update_course() END');
    }

    /** @test */
    public function get_courses_admin()
    {
        $this->log_message('>>> get_courses_admin() START');

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

        $this->log_message('>>> get_courses_admin() END');
    }

    /** @test */
    public function get_course_by_id_admin()
    {
        $this->log_message('>>> get_course_by_id_admin() START');

        $response = $this->actingAs($this->admin_user, 'api')
                         ->getJson('/api/v1.0/courses/' . $this->course->id);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course retrieved successfully',
                 ]);

        $this->log_message('>>> get_course_by_id_admin() END');
    }

    /** @test */
    public function get_courses_client()
    {
        $this->log_message('>>> get_courses_client() START');

        Course::factory()->count(2)->create(['status' => 'published']);
        $this->log_message('2 published courses created for client listing test');

        $response = $this->getJson('/api/v1.0/courses/client');

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ]);

        $this->log_message('>>> get_courses_client() END');
    }

    /** @test */
    public function get_course_by_id_client()
    {
        $this->log_message('>>> get_course_by_id_client() START');

        $publishedCourse = Course::factory()->create(['status' => 'published']);

        $response = $this->getJson('/api/v1.0/courses/client/' . $publishedCourse->id);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course retrieved successfully',
                 ]);

        $this->log_message('>>> get_course_by_id_client() END');
    }

    /** @test */
    public function get_courses_client_secure()
    {
        $this->log_message('>>> get_courses_client_secure() START');

        // Create enrollment for the student
        Enrollment::factory()->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->student_user, 'api')
                         ->getJson('/api/v1.0/courses/client/secure');

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Courses retrieved successfully',
                 ]);

        $this->log_message('>>> get_courses_client_secure() END');
    }

    /** @test */
    public function get_course_by_id_secure_client()
    {
        $this->log_message('>>> get_course_by_id_secure_client() START');

        // Create enrollment for the student
        Enrollment::factory()->create([
            'user_id' => $this->student_user->id,
            'course_id' => $this->course->id,
        ]);

        $response = $this->actingAs($this->student_user, 'api')
                         ->getJson('/api/v1.0/courses/client/secure/' . $this->course->id);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course retrieved successfully',
                 ]);

        $this->log_message('>>> get_course_by_id_secure_client() END');
    }

    /** @test */
    public function delete_course()
    {
        $this->log_message('>>> delete_course() START');

        $courseToDelete = Course::factory()->create();

        $response = $this->actingAs($this->admin_user, 'api')
                         ->deleteJson('/api/v1.0/courses/' . $courseToDelete->id);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course deleted successfully',
                 ]);

        $this->assertDatabaseMissing('courses', [
            'id' => $courseToDelete->id,
        ]);

        $this->log_message('>>> delete_course() END');
    }

    /** @test */
    public function delete_multiple_courses()
    {
        $this->log_message('>>> delete_multiple_courses() START');

        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();
        $courseIds = $course1->id . ',' . $course2->id;

        $response = $this->actingAs($this->admin_user, 'api')
                         ->deleteJson('/api/v1.0/courses/' . $courseIds);

        $this->log_message('Response status: ' . $response->status());
        $this->log_message('Response content: ' . $response->getContent());

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Course deleted successfully',
                 ]);

        $this->assertDatabaseMissing('courses', ['id' => $course1->id]);
        $this->assertDatabaseMissing('courses', ['id' => $course2->id]);

        $this->log_message('>>> delete_multiple_courses() END');
    }
}