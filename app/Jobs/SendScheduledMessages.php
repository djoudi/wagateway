<?php

namespace App\Jobs;

use App\Models\ScheduledMessage;
use App\Services\MessageDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendScheduledMessages implements ShouldQueue
{
    use Queueable;

    public function handle(MessageDispatcher $dispatcher): void
    {
        ScheduledMessage::pending()
            ->with(['user','device'])
            ->chunk(50, function ($scheduled) use ($dispatcher) {
                foreach ($scheduled as $item) {
                    try {
                        $message = $dispatcher->send(
                            $item->user,
                            $item->device,
                            array_merge($item->message_data, ['to' => $item->to_number])
                        );
                        $item->update(['status' => 'sent', 'message_id' => $message->id]);
                    } catch (\Exception $e) {
                        $item->update(['status' => 'failed']);
                    }
                }
            });
    }
}
