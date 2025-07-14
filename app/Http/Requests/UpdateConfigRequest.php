<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'config' => 'required|array',
            'config.days' => 'required|array|min:1',
            'config.days.*' => 'string|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'config.valid_from' => 'required|date_format:H:i',
            'config.valid_to' => 'required|date_format:H:i|after:config.valid_from',
            'config.interval' => 'required|integer|min:5',
            'config.resource_type' => 'nullable|string',
            'config.resource_id' => 'nullable|integer',
        ];
    }
}
