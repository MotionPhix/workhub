<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-invitations');
    }

    public function rules(): array
    {
        return [
            'invitations' => 'required|array|min:1|max:50',
            'invitations.*.email' => [
                'required',
                'email',
                'max:255',
                'distinct:strict', // No duplicates in the array
            ],
            'invitations.*.name' => 'required|string|max:255',
            'invitations.*.department_uuid' => 'nullable|exists:departments,uuid',
            'invitations.*.manager_email' => [
                'nullable',
                'email',
                'exists:users,email',
            ],
            'invitations.*.job_title' => 'nullable|string|max:255',
            'invitations.*.role_name' => 'nullable|string|exists:roles,name',

            // Global settings applied to all invitations
            'global_expires_in_days' => 'nullable|integer|min:1|max:30',
            'global_role_name' => 'nullable|string|exists:roles,name',
            'global_department_uuid' => 'nullable|exists:departments,uuid',
            'global_welcome_message' => 'nullable|string|max:500',
            'send_immediately' => 'nullable|boolean',
            'skip_existing' => 'nullable|boolean', // Skip emails that already exist
        ];
    }

    public function messages(): array
    {
        return [
            'invitations.max' => 'Cannot send more than 50 invitations at once.',
            'invitations.*.email.distinct' => 'Duplicate email addresses are not allowed.',
            'invitations.*.manager_email.exists' => 'The manager email must exist in the system.',
            'global_expires_in_days.max' => 'Invitations cannot be valid for more than 30 days.',
        ];
    }

    public function attributes(): array
    {
        return [
            'invitations.*.email' => 'email address',
            'invitations.*.name' => 'name',
            'invitations.*.department_uuid' => 'department',
            'invitations.*.manager_email' => 'manager',
            'invitations.*.job_title' => 'job title',
            'invitations.*.role_name' => 'role',
            'global_expires_in_days' => 'expiry days',
            'global_role_name' => 'default role',
            'global_department_uuid' => 'default department',
        ];
    }

    protected function prepareForValidation(): void
    {
        $invitations = $this->invitations ?? [];

        // Normalize and clean up invitation data
        $normalized = collect($invitations)->map(function ($invitation) {
            return [
                'email' => isset($invitation['email']) ? strtolower(trim($invitation['email'])) : null,
                'name' => isset($invitation['name']) ? trim($invitation['name']) : null,
                'department_uuid' => $invitation['department_uuid'] ?? null,
                'manager_email' => isset($invitation['manager_email']) ? strtolower(trim($invitation['manager_email'])) : null,
                'job_title' => isset($invitation['job_title']) ? trim($invitation['job_title']) : null,
                'role_name' => $invitation['role_name'] ?? null,
            ];
        })->toArray();

        $this->merge([
            'invitations' => $normalized,
            'global_expires_in_days' => $this->global_expires_in_days ?? 7,
            'global_role_name' => $this->global_role_name ?? 'employee',
            'send_immediately' => $this->send_immediately ?? true,
            'skip_existing' => $this->skip_existing ?? true,
        ]);
    }

    /**
     * Get validated invitations with global defaults applied
     */
    public function getProcessedInvitations(): array
    {
        $validated = $this->validated();
        $invitations = $validated['invitations'];

        return collect($invitations)->map(function ($invitation) use ($validated) {
            return [
                'email' => $invitation['email'],
                'name' => $invitation['name'],
                'department_uuid' => $invitation['department_uuid'] ?? $validated['global_department_uuid'] ?? null,
                'manager_email' => $invitation['manager_email'] ?? null,
                'job_title' => $invitation['job_title'] ?? null,
                'role_name' => $invitation['role_name'] ?? $validated['global_role_name'],
                'expires_in_days' => $validated['global_expires_in_days'],
                'welcome_message' => $validated['global_welcome_message'] ?? null,
            ];
        })->toArray();
    }
}
