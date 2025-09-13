<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-invitations');
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'unique:user_invites,email,NULL,id,status,pending',
            ],
            'name' => 'required|string|max:255',
            'department_uuid' => 'nullable|exists:departments,uuid',
            'manager_email' => [
                'nullable',
                'email',
                'exists:users,email',
                'different:email', // Can't be their own manager
            ],
            'job_title' => 'nullable|string|max:255',
            'role_name' => 'nullable|string|exists:roles,name',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
            'welcome_message' => 'nullable|string|max:500',
            'additional_permissions' => 'nullable|array',
            'additional_permissions.*' => 'string|exists:permissions,name',
            'initial_schedule_settings' => 'nullable|array',
            'initial_schedule_settings.timezone' => 'nullable|string|max:50',
            'initial_schedule_settings.notifications' => 'nullable|array',
            'send_immediately' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email already exists or has a pending invitation.',
            'manager_email.different' => 'A user cannot be their own manager.',
            'expires_in_days.max' => 'Invitation cannot be valid for more than 30 days.',
            'role_name.exists' => 'The selected role does not exist.',
        ];
    }

    public function attributes(): array
    {
        return [
            'department_uuid' => 'department',
            'manager_email' => 'manager',
            'job_title' => 'job title',
            'role_name' => 'role',
            'expires_in_days' => 'expiry days',
            'welcome_message' => 'welcome message',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize email to lowercase
        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        // Set default values
        $this->merge([
            'role_name' => $this->role_name ?? 'employee',
            'expires_in_days' => $this->expires_in_days ?? 7,
            'send_immediately' => $this->send_immediately ?? true,
        ]);
    }
}
