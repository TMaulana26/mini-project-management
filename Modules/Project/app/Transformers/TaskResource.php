<?php

declare(strict_types=1);

namespace Modules\Project\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Acl\Transformers\UserResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'company_id' => $this->company_id,
            'assigned_to_user_id' => $this->assigned_to_user_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'assigned_to' => new UserResource($this->whenLoaded('assignedTo')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
