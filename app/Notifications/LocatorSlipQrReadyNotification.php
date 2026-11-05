<?php

namespace App\Notifications;

use App\Models\LocatorSlip;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LocatorSlipQrReadyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly LocatorSlip $locatorSlip,
        private readonly ?string $qrSvg,
        private readonly ?string $qrPng = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $employeeName = trim(collect([
            $this->locatorSlip->employee?->firstname,
            $this->locatorSlip->employee?->lastname,
        ])->filter()->implode(' ')) ?: 'Employee';

        $message = (new MailMessage)
            ->subject('Approved Locator Slip QR Code')
            ->greeting("Hello {$employeeName},")
            ->line('Your locator slip has been approved.')
            ->line('The QR code for time OUT/IN scanning is attached to this email as a PNG image.')
            ->line('Please present this QR code when recording your locator slip movement.')
            ->action('View Locator Slip', route('locator-slips.show', $this->locatorSlip))
            ->line('Thank you.');

        if ($this->qrPng) {
            $message->attachData(
                $this->qrPng,
                'locator-slip-qr-'.$this->locatorSlip->id.'.png',
                ['mime' => 'image/png']
            );
        } elseif ($this->qrSvg) {
            $message->attachData(
                $this->qrSvg,
                'locator-slip-qr-'.$this->locatorSlip->id.'.svg',
                ['mime' => 'image/svg+xml']
            );
        }

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'locator_slip_id' => $this->locatorSlip->id,
            'title' => 'Locator Slip QR Ready',
            'message' => 'Your approved locator slip QR code has been sent to your email.',
        ];
    }
}
