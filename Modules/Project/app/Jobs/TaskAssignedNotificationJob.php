<?php

declare(strict_types=1);

namespace Modules\Project\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Acl\Models\User;
use Modules\Project\Models\Task;

class TaskAssignedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Task $task,
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info(sprintf(
            "Task '%s' (ID: %d) assigned to user '%s' (%s).",
            $this->task->title,
            $this->task->id,
            $this->user->name,
            $this->user->email
        ));
    }
}
