<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's channels.
     *
     * @return array|string
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $verificationCode = $this->generateVerificationCode($notifiable);

        return (new MailMessage)
            ->subject('Verify Email Address')
            ->line('Please use the following verification code in your app:')
            ->line($verificationCode)
            ->line('This code will expire in 60 minutes.')
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Generate a verification code for the user.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    protected function generateVerificationCode($notifiable)
    {
        // Generate a 6-digit verification code
        $code = sprintf('%06d', mt_rand(0, 999999));

        // Store the code in the user's remember_token field (or you could create a new field)
        $notifiable->forceFill([
            'remember_token' => hash('sha256', $code),
            'email_verification_code_expires_at' => Carbon::now()->addMinutes(60)
        ])->save();

        return $code;
    }
}
