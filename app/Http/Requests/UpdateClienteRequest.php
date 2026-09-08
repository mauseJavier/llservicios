<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClienteRequest extends FormRequest
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
            //
            'nombre' => 'required',
            'dni' => ['required', Rule::unique('clientes', 'dni')->ignore($this->route('Cliente'))],
            'aplicar_recargos' => 'nullable|boolean',
            'condicion_iva_id' => 'nullable|integer|in:1,6,13,16,4,5,7,8,9,10,15',
        ];
    }
}
