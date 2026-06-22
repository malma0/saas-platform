<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'branch_id'           => 'required|integer|exists:branches,id',
            'service_offering_id' => 'required|integer|exists:service_offerings,id',
            'resource_ids'        => 'required|array|min:1',
            'resource_ids.*'      => 'integer|exists:resources,id',
            'start_at'            => 'required|date|after:now',
            'end_at'              => 'required|date|after:start_at',
            'notes'               => 'nullable|string|max:500',
            'hold_token'          => 'nullable|string|max:64',
        ];
    }
}
