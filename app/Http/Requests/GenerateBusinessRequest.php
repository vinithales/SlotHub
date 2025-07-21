<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class GenerateBusinessRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'timezone' => 'required|string|max:80',
            'schedule' => 'required|array',
            'schedule.*.days' => 'required|array',
            'schedule.*.days.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'schedule.*.valid_from' => 'required|date_format:H:i',
            'schedule.*.valid_to' => 'required|date_format:H:i|after:schedule.*.valid_from',
            'schedule.*.interval' => 'required|integer|min:5|max:480',
            'slug' => 'prohibited'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do negócio é obrigatório.',
            'name.string' => 'O nome do negócio deve ser uma string.',
            'name.max' => 'O nome do negócio não pode ter mais que 255 caracteres.',

            'timezone.required' => 'O timezone é obrigatório.',
            'timezone.string' => 'O timezone deve ser uma string.',
            'timezone.max' => 'O timezone não pode ter mais que 80 caracteres.',

            'schedule.required' => 'A configuração de agenda é obrigatória.',
            'schedule.array' => 'A agenda deve ser um array válido.',

            'schedule.*.days.required' => 'O campo "days" é obrigatório para cada item da agenda.',
            'schedule.*.days.array' => 'O campo "days" deve ser um array de dias da semana.',
            'schedule.*.days.*.in' => 'Um ou mais valores de "days" são inválidos. Use: sunday, monday, tuesday, wednesday, thursday, friday, saturday.',

            'schedule.*.valid_from.required' => 'O campo "valid_from" é obrigatório.',
            'schedule.*.valid_from.date_format' => 'O campo "valid_from" deve estar no formato HH:MM (ex: 09:00).',

            'schedule.*.valid_to.required' => 'O campo "valid_to" é obrigatório.',
            'schedule.*.valid_to.date_format' => 'O campo "valid_to" deve estar no formato HH:MM (ex: 17:00).',
            'schedule.*.valid_to.after' => 'O horário "valid_to" deve ser posterior ao "valid_from".',

            'schedule.*.interval.required' => 'O campo "interval" é obrigatório.',
            'schedule.*.interval.integer' => 'O campo "interval" deve ser um número inteiro.',
            'schedule.*.interval.min' => 'O intervalo mínimo permitido é de 5 minutos.',
            'schedule.*.interval.max' => 'O intervalo máximo permitido é de 480 minutos.',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Erro de validação',
            'errors' => $validator->errors()
        ], 422));
    }
}
