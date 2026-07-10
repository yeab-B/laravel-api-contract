<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Support\TestResources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'bio' => $this->bio,
            'avatar' => $this->avatar,
        ];
    }
}
