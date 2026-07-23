<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = url('/login?token='.$this->token.'&email='.urlencode($notifiable->getEmailForPasswordReset()));

        return (new MailMessage)
            ->subject('Réinitialisation mot de passe — AutoChain Emma+')
            ->line('Vous recevez cet email car une réinitialisation de mot de passe a été demandée.')
            ->action('Réinitialiser mon mot de passe', $url)
            ->line('Ce lien expire dans '.config('auth.passwords.users.expire').' minutes.')
            ->line('Si vous n\'avez pas demandé cette réinitialisation, ignorez cet email.');
    }
}
