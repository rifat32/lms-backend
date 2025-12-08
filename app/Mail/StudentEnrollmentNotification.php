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
    public $business;

    /**
     * Create a new message instance.
     */
    public function __construct(User $student, Course $course, User $owner)
    {
        $this->student = $student;
        $this->course = $course;
        $this->owner = $owner;
        // Fetch the business of the user
        $this->business = $student->business;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->from(config('mail.from.address'), $this->business->name ?? config('mail.from.name'))
            ->subject('New Student Enrollment: ' . $this->course->title)
            ->view('emails.student-enrollment-notification');
    }
}
