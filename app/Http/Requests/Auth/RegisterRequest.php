<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'phone' => [
                'required',
                'string',
                'regex:/^[0-9]{11}$/',
                Rule::unique('users', 'phone'),
            ],
            'nome' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                \Illuminate\Validation\Rules\Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->uncompromised(), // Optional: checks HAVEIBEENPWNED
                'confirmed',
            ],
            'bairroId' => [
                'nullable',
                'uuid',
                Rule::exists('bairros', 'id'),
            ],
            'address' => [
                'nullable',
                'array',
            ],
            'address.cep' => [
                'nullable',
                'string',
                'regex:/^[0-9]{8}$/',
            ],
            'address.logradouro' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address.numero' => [
                'nullable',
                'string',
                'max:20',
            ],
            'address.complemento' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address.bairro' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address.cidade' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address.estado' => [
                'nullable',
                'string',
                'size:2',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'O telefone é obrigatório.',
            'phone.regex' => 'O telefone deve ter 11 dígitos (DDD + número).',
            'phone.unique' => 'Este telefone já está cadastrado.',
            'nome.required' => 'O nome é obrigatório.',
            'nome.min' => 'O nome deve ter pelo menos 2 caracteres.',
            'nome.max' => 'O nome deve ter no máximo 100 caracteres.',
            'email.email' => 'O email deve ser um endereço válido.',
            'email.unique' => 'Este email já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'bairroId.exists' => 'O bairro selecionado não existe.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert camelCase to snake_case for bairroId
        if ($this->has('bairroId')) {
            $this->merge([
                'bairro_id' => $this->input('bairroId'),
            ]);

            // Precedence: If bairroId exists, remove address.bairro to avoid ambiguity
            if ($this->has('address')) {
                $address = $this->input('address');
                if (isset($address['bairro'])) {
                    unset($address['bairro']);
                    $this->merge(['address' => $address]);
                }
            }
        }
    }
}
