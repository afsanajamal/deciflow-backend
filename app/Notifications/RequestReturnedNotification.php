<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestReturnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Request $request,
        public ?string $comment = null
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
        $url = env('FRONTEND_URL', 'http://localhost:3000').'/requests/'.$this->request->id.'/edit';

        $message = (new MailMessage)
            ->subject('Request Returned: '.$this->request->title)
            ->greeting('Hello '.$this->request->user->name.',')
            ->line('Your request has been returned and requires modifications.')
            ->line('**Title:** '.$this->request->title)
            ->line('**Category:** '.$this->request->category)
            ->line('**Amount:** ¥'.number_format($this->request->amount));

        if ($this->comment) {
            $message->line('**Feedback:** '.$this->comment);
        }

        return $message
            ->action('Edit Request', $url)
            ->line('Please make the necessary changes and resubmit your request.');
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
            'status' => $this->request->status,
            'comment' => $this->comment,
        ];
    }
}
