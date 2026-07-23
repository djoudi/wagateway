<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int  $daysRemaining,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->daysRemaining <= 1 => 'اشتراكك ينتهي غداً — جدّده الآن لتفادي انقطاع الخدمة',
            $this->daysRemaining <= 3 => "اشتراكك ينتهي خلال {$this->daysRemaining} أيام",
            default                   => "تذكير: اشتراكك ينتهي خلال أسبوع",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.subscription-expiring',
            with: ['daysRemaining' => $this->daysRemaining],
        );
    }
}
