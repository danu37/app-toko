<?php

namespace App\Http\Requests\Pengguna;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('pengguna');

        return $target instanceof User && (bool) $this->user()?->can('update', $target);
    }

    public function rules(): array
    {
        $target = $this->route('pengguna');
        $id = is_object($target) ? $target->getKey() : $target;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::in(User::ROLES)],
            'id_cabang' => ['nullable', 'integer', 'exists:tbl_cabang,id_cabang'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $target = $this->route('pengguna');

                if (! $target instanceof User || $this->user()?->id !== $target->id) {
                    return;
                }

                if ($this->input('role') !== User::ROLE_ADMIN) {
                    $validator->errors()->add('role', 'Anda tidak dapat mengubah role akun Anda sendiri.');
                }

                if ($this->has('is_active') && ! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
                }
            },
        ];
    }
}
