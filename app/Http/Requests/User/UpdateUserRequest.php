<?php

namespace App\Http\Requests\User;

use App\Concerns\PasswordValidationRules;
use App\Enums\AreaRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        /** @var User $user */
        $user = $this->route('user');

        return $this->user()?->can('update', $user) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'is_super_admin' => ['sometimes', 'boolean'],
            'memberships' => ['present', 'array'],
            'memberships.*.area_id' => ['required', 'integer', Rule::exists('areas', 'id')],
            'memberships.*.role' => ['required', Rule::enum(AreaRole::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $actor = $this->user();

            if (! $actor) {
                return;
            }

            if ($this->boolean('is_super_admin') && ! $actor->isSuperAdmin()) {
                $validator->errors()->add('is_super_admin', __('Only super admins can grant super admin access.'));
            }

            $memberships = $this->input('memberships', []);

            if (! $this->boolean('is_super_admin') && count($memberships) < 1) {
                $validator->errors()->add('memberships', __('At least one area membership is required.'));
            }

            if ($actor->isSuperAdmin()) {
                return;
            }

            $allowed = $actor->manageableAreaIds();

            foreach ($memberships as $index => $membership) {
                $areaId = (int) ($membership['area_id'] ?? 0);

                if (! in_array($areaId, $allowed, true)) {
                    $validator->errors()->add(
                        "memberships.{$index}.area_id",
                        __('You cannot assign users to this area.'),
                    );
                }
            }
        });
    }
}
