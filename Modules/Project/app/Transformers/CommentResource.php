<?php

declare(strict_types=1);

namespace Modules\Project\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Acl\Transformers\UserResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_id' => $this->task_id,
            'company_id' => $this->company_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'user' => new UserResource($this->whenLoaded('user')),
            'task' => new TaskResource($this->whenLoaded('task')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
