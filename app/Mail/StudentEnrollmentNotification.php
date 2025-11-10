<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentEnrollmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $student;
    public $course;
    public $owner;

    /**
     * Create a new message instance.
     */
    public function __construct(User $student, Course $course, User $owner)
    {
        $this->student = $student;
        $this->course = $course;
        $this->owner = $owner;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Student Enrollment: ' . $this->course->title)
            ->view('emails.student-enrollment-notification');
    }
}
