<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Request $request,
        public int $stepNumber
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = env('FRONTEND_URL', 'http://localhost:3000') . '/approvals/inbox';

        return (new MailMessage)
            ->subject('Approval Required: ' . $this->request->title)
            ->greeting('Hello,')
            ->line('A request requires your approval.')
            ->line('**Requester:** ' . $this->request->user->name)
            ->line('**Title:** ' . $this->request->title)
            ->line('**Category:** ' . $this->request->category)
            ->line('**Amount:** ¥' . number_format($this->request->amount))
            ->line('**Description:** ' . $this->request->description)
            ->line('**Approval Step:** ' . $this->stepNumber)
            ->action('Review Request', $url)
            ->line('Please review and take action on this request.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'title' => $this->request->title,
            'step_number' => $this->stepNumber,
        ];
    }
}
