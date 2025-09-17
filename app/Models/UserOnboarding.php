<?php

namespace App\Models;

use App\Traits\BootableUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOnboarding extends Model
{
    use BootableUuid;

    // Define the table associated with the model
    protected $table = 'user_onboardings';

    // Specify the attributes that are mass assignable
    protected $fillable = [
        'user_uuid',
        'completed_steps',
        'is_completed',
        'completed_at',
        'current_step',
        'completion_percentage',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'current_step' => 'integer',
        'completion_percentage' => 'float',
    ];

    /**
     * Get the user that owns the onboarding.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    // Check if the onboarding is complete
    /**
     * Check if the onboarding is complete.
     */
    public function isComplete(): bool
    {
        return $this->is_completed && $this->complete_at !== null;
    }

    /**
     * Mark a step as completed and update progress.
     */
    public function completeStep(int $stepId): void
    {
        $completedSteps = $this->completed_steps ?? [];

        if (! in_array($stepId, $completedSteps)) {
            $completedSteps[] = $stepId;
            $this->completed_steps = $completedSteps;

            // Update progress
            $this->updateProgress();
        }
    }

    /**
     * Update onboarding progress and completion status.
     */
    public function updateProgress(): void
    {
        $totalSteps = 4; // Total number of onboarding steps
        $completedCount = count($this->completed_steps ?? []);

        $this->completion_percentage = ($completedCount / $totalSteps) * 100;
        $this->current_step = min($completedCount + 1, $totalSteps);

        // Check if all steps are completed
        if ($completedCount >= $totalSteps) {
            $this->markAsComplete();
        }

        $this->save();
    }

    /**
     * Mark the onboarding as complete.
     */
    public function markAsComplete(): void
    {
        $this->is_completed = true;
        $this->completed_at = now();
        $this->completion_percentage = 100;
    }

    /**
     * Get the next incomplete step.
     */
    public function getNextStep(): ?int
    {
        $completedSteps = $this->completed_steps ?? [];
        $allSteps = range(1, 4);

        $incompleteSteps = array_diff($allSteps, $completedSteps);

        return ! empty($incompleteSteps) ? min($incompleteSteps) : null;
    }

    /**
     * Reset onboarding progress.
     */
    public function reset(): void
    {
        $this->update([
            'completed_steps' => [],
            'is_completed' => false,
            'completed_at' => null,
            'current_step' => 1,
            'completion_percentage' => 0,
        ]);
    }
}
