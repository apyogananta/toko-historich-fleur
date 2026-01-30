<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SiteUserResetPasswordNotification extends Notification
{
    use Queueable;

    protected $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
         return (new MailMessage)
                    ->subject('Notifikasi Reset Password Akun Historich Fleur')
                    ->line('Anda menerima email ini karena kami menerima permintaan reset password untuk akun Anda.')
                    ->action('Reset Password', $this->url)
                    ->line('Link reset password ini akan kedaluwarsa dalam ' . config('auth.passwords.users.expire', 60) . ' menit.')
                    ->line('Jika Anda tidak meminta reset password, abaikan email ini.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
