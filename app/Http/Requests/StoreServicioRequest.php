<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
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
            'precio' => 'required|numeric',
            'precio2' => 'nullable|numeric',
            'precio3' => 'nullable|numeric',
            'diasVencimiento' => 'required|integer|min:1',
            'tiempo' => 'required',
        ];
    }
}
