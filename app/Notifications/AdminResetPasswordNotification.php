<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

// Laravel's built-in ResetPassword notification links to a generic
// "password.reset" route. We have three separate portals, so this overrides
// the mail content to point at the admin-specific reset URL instead.
class AdminResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $schoolName = $notifiable->school->name ?? 'AxisPro School Management System';

        return (new MailMessage)
            ->subject("Reset Your Admin Password — {$schoolName}")
            ->line('You are receiving this email because we received a password reset request for your admin account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in '.config('auth.passwords.admins.expire').' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
