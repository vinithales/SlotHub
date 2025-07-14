<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_id' => 'required|exists:businesses,id',
            'config.days' => 'required|array',
            'config.days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'config.valid_from' => 'required|date_format:H:i',
            'config.valid_to' => 'required|date_format:H:i|after:config.valid_from',
            'config.interval' => 'required|integer|min:1'
        ];
    }
}
