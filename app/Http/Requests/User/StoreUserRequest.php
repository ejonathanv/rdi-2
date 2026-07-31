<?php

namespace App\Http\Requests\User;

use App\Concerns\PasswordValidationRules;
use App\Enums\AreaRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => $this->passwordRules(),
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
