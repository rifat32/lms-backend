<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $business;

    /**
     * Create a new message instance.
     */
    public function __construct($user)
    {
        $this->user = $user;

        // Fetch the business of the user
        $this->business = $user->business; // assuming 'business' relationship exists in User model
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->from($this->business->email ?? config('mail.from.address'), $this->business->name ?? config('mail.from.name'))
            ->subject('Welcome to ' . $this->business->name . ' 🎓')
            ->view('emails.students.welcome');
    }
}
