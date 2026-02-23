<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Booking $booking
    ) {
    }

    /**
     * Determine which channels the notification should be sent on.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $this->booking->load('ticket.event');

        $event = $this->booking->ticket->event;
        $ticket = $this->booking->ticket;
        $amount = number_format($ticket->price * $this->booking->quantity, 2);

        return (new MailMessage)
            ->subject('Booking Confirmed — ' . $event->title)
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your booking has been confirmed successfully.')
            ->line("**Event:** {$event->title}")
            ->line("**Date:** {$event->date->format('M d, Y h:i A')}")
            ->line("**Location:** {$event->location}")
            ->line("**Ticket:** {$ticket->type} × {$this->booking->quantity}")
            ->line("**Total Paid:** \${$amount}")
            ->line("**Booking ID:** #{$this->booking->id}")
            ->action('View Your Bookings', url('/api/bookings'))
            ->line('Thank you for your purchase!');
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->booking->load('ticket.event');

        return [
            'booking_id' => $this->booking->id,
            'event_id' => $this->booking->ticket->event->id,
            'event_title' => $this->booking->ticket->event->title,
            'ticket_type' => $this->booking->ticket->type,
            'quantity' => $this->booking->quantity,
            'amount' => $this->booking->ticket->price * $this->booking->quantity,
            'message' => "Your booking for \"{$this->booking->ticket->event->title}\" has been confirmed.",
        ];
    }
}
