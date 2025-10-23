<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreempresaRequest extends FormRequest
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
            'cuit' => 'nullable|numeric|unique:empresas,cuit',
            'correo' => 'nullable|email|unique:empresas,correo',
            'logo' => 'nullable|url|max:255',
            'MP_ACCESS_TOKEN' => 'nullable|string|max:255',
            'MP_PUBLIC_KEY' => 'nullable|string|max:255',
            'client_secret' => 'nullable|string|max:255',
            'client_id' => 'nullable|string|max:255',
        ];
    }
}
