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
    public $business;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Course $course)
    {
        $this->user = $user;
        $this->course = $course;
        // Fetch the business of the user
        $this->business = $user->business;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this
            ->from(config('mail.from.address'), $this->business->name ?? config('mail.from.name'))
            ->subject('Successfully Enrolled in ' . $this->course->title)
            ->view('emails.course-enrollment');
    }
}
