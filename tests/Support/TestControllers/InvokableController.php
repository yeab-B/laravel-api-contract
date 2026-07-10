<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Support\TestControllers;

use Illuminate\Http\JsonResponse;

class InvokableController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([]);
    }
}
