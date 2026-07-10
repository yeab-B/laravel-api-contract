<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Support\TestControllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController
{
    public function index(): JsonResponse
    {
        return response()->json([]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        return response()->json([]);
    }

    public function destroy(int $id): bool
    {
        return true;
    }

    private function privateMethod(): void
    {
    }
}
