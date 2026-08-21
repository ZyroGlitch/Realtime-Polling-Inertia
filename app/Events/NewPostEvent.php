<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post;

    /**
     * Create a new event instance.
     */
    public function __construct($posts)
    {
        $this->post = $posts;
        // dd($this->post);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */

    // Register new Channel
    public function broadcastOn()
    {
        return new Channel('post-created');
    }

    // Broadcast Realtime Data
    public function broadcastWith(): array
    {
        return [
            'broadcast_name' => 'Laravel 12 Reverb',
            'title' => $this->post->post_title,
            'content' => $this->post->post_content,
        ];
    }

    // Aliasis the Event Name
    public function broadcastAs(): string
    {
        return 'realtime-post-created';
    }
}
