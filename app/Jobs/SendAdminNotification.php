<?php

namespace App\Jobs;

use App\Mail\AdminNotificationMail;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAdminNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public AdminNotification $notification,
    ) {}

    public function handle(): void
    {
        $this->notification->update(['status' => 'sending']);

        $query = User::where('role', '!=', 'admin');

        match ($this->notification->audience) {
            'active' => $query->where('subscription_expires_at', '>', now()),
            'expired' => $query->where(function ($q) {
                $q->whereNull('subscription_expires_at')
                  ->orWhere('subscription_expires_at', '<=', now());
            })->where(function ($q) {
                $q->whereNull('trial_ends_at')
                  ->orWhere('trial_ends_at', '<=', now());
            }),
            default => null, // 'all' — no extra filter
        };

        $sentCount = 0;

        $query->chunk(50, function ($users) use (&$sentCount) {
            foreach ($users as $user) {
                Mail::to($user->email)->send(
                    new AdminNotificationMail(
                        $this->notification->subject,
                        $this->notification->body,
                    )
                );
                $sentCount++;
            }
        });

        $this->notification->update([
            'status' => 'sent',
            'sent_count' => $sentCount,
            'sent_at' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->notification->update(['status' => 'failed']);
    }
}
