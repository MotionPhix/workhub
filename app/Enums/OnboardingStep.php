<?php

namespace App\Enums;

enum OnboardingStep: int
{
  case PROFILE = 1;
  case DEPARTMENT = 2;
  case SYSTEM_ACCESS = 3;
  case TRAINING = 4;

  /**
   * Get all step details
   */
  public static function getStepDetails(): array
  {
    return [
      self::PROFILE->value => [
        'title' => 'Complete Profile',
        'description' => 'Fill in your personal and professional information',
        'action' => 'Update Profile',
        'route' => 'profile.edit'
      ],
      self::DEPARTMENT->value => [
        'title' => 'Department Assignment',
        'description' => 'Confirm your department assignment and role',
        'action' => 'Review Assignment',
        'route' => 'department.assignment'
      ],
      self::SYSTEM_ACCESS->value => [
        'title' => 'System Access',
        'description' => 'Set up your system credentials and permissions',
        'action' => 'Setup Access',
        'route' => 'system.access'
      ],
      self::TRAINING->value => [
        'title' => 'Training Materials',
        'description' => 'Review required training materials and documentation',
        'action' => 'Start Training',
        'route' => 'training.materials'
      ]
    ];
  }

  /**
   * Get total number of steps
   */
  public static function count(): int
  {
    return count(self::cases());
  }

  /**
   * Get step details by ID
   */
  public static function getById(int $id): ?array
  {
    $details = self::getStepDetails();
    return $details[$id] ?? null;
  }
}
