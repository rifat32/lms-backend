<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CourseCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $course;
    public $business;

    public function __construct($user, $course)
    {
        $this->user = $user;
        $this->course = $course;
        // Fetch the business of the user
        $this->business = $user->business;
    }

    public function build()
    {
        return $this
            ->from(config('mail.from.address'), $this->business->name ?? config('mail.from.name'))
            ->subject('🎉 Congratulations! You completed ' . $this->course->title)
            ->view('emails.course_completed') // ✅ Use normal Blade
            ->with([
                'user' => $this->user,
                'course' => $this->course,
            ]);
    }
}
