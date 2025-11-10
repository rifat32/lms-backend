<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourseEnrollmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $course;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Course $course)
    {
        $this->user = $user;
        $this->course = $course;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Successfully Enrolled in ' . $this->course->title)
            ->view('emails.course-enrollment');
    }
}
