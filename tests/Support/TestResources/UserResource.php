<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Support\TestResources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'full_name' => $this->first_name . ' ' . $this->last_name,
            'is_admin' => $this->is_admin,
            'posts' => PostResource::collection($this->posts),
            'profile' => new ProfileResource($this->profile),
        ];
    }
}
