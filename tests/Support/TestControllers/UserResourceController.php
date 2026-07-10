<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Support\TestControllers;

use Yab\LaravelApiContract\Tests\Support\TestResources\UserResource;

class UserResourceController
{
    public function index()
    {
        return UserResource::collection([]);
    }

    public function show(int $id)
    {
        return UserResource::make([]);
    }

    public function store()
    {
        return new UserResource([]);
    }

    public function noResource(): array
    {
        return [];
    }
}
