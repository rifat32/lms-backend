<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $reset_url;

    /**
     * Create a new message instance.
     *
     * @param  object|null  $user
     * @return void
     */
    public function __construct($user = null)
    {
        $this->user = $user;

        $front_end_url = env('FRONT_END_URL');
        $base   = rtrim($front_end_url, '/') . '/auth/reset-password';
        $params = array_filter([
            'token' => $user->resetPasswordToken ?? null,
            'email' => $user->email ?? null,
        ], fn($v) => $v !== null && $v !== '');

        $this->reset_url = $base . (str_contains($base, '?') ? '&' : '?') . http_build_query($params);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "Reset Password Mail from " . ($this->user->business->name ?? config('app.name'));

        return $this->subject($subject)
            ->view('emails.reset_password_mail')
            ->with([
                'user' => $this->user,
                'url' => $this->reset_url
            ]);
    }
}
