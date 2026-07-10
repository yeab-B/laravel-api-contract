<?php

declare(strict_types=1);

namespace Yab\LaravelApiContract\Tests\Support\TestControllers;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'age' => 'nullable|integer',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
