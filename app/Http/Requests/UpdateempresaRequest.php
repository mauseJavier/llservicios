<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateempresaRequest extends FormRequest
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
            'nombre' => 'required|string|max:255',
            'cuit' => 'nullable|numeric|unique:empresas,cuit,' . $this->route('empresa')->id,
            'correo' => 'nullable|email|unique:empresas,correo,' . $this->route('empresa')->id,
            'logo' => 'nullable|url|max:255',
            'MP_ACCESS_TOKEN' => 'nullable|string|max:255',
            'MP_PUBLIC_KEY' => 'nullable|string|max:255',
            'MP_USER_ID' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255',
        ];
    }
}
