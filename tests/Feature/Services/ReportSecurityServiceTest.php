<?php

use App\Models\Report;
use App\Models\User;
use App\Services\Security\ReportSecurityService;

describe('ReportSecurityService', function () {
    beforeEach(function () {
        // Create roles first
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'manager']);

        $this->securityService = new ReportSecurityService;
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create()->assignRole('admin');
        $this->manager = User::factory()->create()->assignRole('manager');
    });

    describe('encryptSensitiveData', function () {
        it('encrypts sensitive fields', function () {
            $data = [
                'revenue_generated' => 50000.00,
                'pipeline_value' => 100000.00,
                'regular_field' => 'not sensitive',
            ];

            $result = $this->securityService->encryptSensitiveData($data);

            expect($result['revenue_generated'])->not->toBe(50000.00)
                ->and($result['_encrypted_revenue_generated'])->toBe(true)
                ->and($result['regular_field'])->toBe('not sensitive');
        });
    });

    describe('decryptSensitiveData', function () {
        it('decrypts previously encrypted fields', function () {
            $originalData = [
                'revenue_generated' => 50000.00,
                'pipeline_value' => 100000.00,
            ];

            $encrypted = $this->securityService->encryptSensitiveData($originalData);
            $decrypted = $this->securityService->decryptSensitiveData($encrypted);

            expect($decrypted['revenue_generated'])->toBe(50000.00)
                ->and($decrypted['pipeline_value'])->toBe(100000.00)
                ->and($decrypted)->not->toHaveKey('_encrypted_revenue_generated');
        });
    });

    describe('validateReportAccess', function () {
        it('allows owner to view their own report', function () {
            $report = Report::factory()->create(['user_id' => $this->user->id]);

            $hasAccess = $this->securityService->validateReportAccess($report, $this->user, 'view');

            expect($hasAccess)->toBe(true);
        });

        it('allows admin to view any report', function () {
            $report = Report::factory()->create(['user_id' => $this->user->id]);

            $hasAccess = $this->securityService->validateReportAccess($report, $this->admin, 'view');

            expect($hasAccess)->toBe(true);
        });

        it('allows manager to view team member reports', function () {
            $teamMember = User::factory()->create(['manager_email' => $this->manager->email]);
            $report = Report::factory()->create(['user_id' => $teamMember->id]);

            $hasAccess = $this->securityService->validateReportAccess($report, $this->manager, 'view');

            expect($hasAccess)->toBe(true);
        });

        it('denies access to unrelated user', function () {
            $otherUser = User::factory()->create();
            $report = Report::factory()->create(['user_id' => $this->user->id]);

            $hasAccess = $this->securityService->validateReportAccess($report, $otherUser, 'view');

            expect($hasAccess)->toBe(false);
        });

        it('prevents user from approving their own report', function () {
            $report = Report::factory()->create([
                'user_id' => $this->user->id,
                'status' => 'pending',
            ]);

            $hasAccess = $this->securityService->validateReportAccess($report, $this->user, 'approve');

            expect($hasAccess)->toBe(false);
        });
    });

    describe('data integrity', function () {
        it('generates consistent hash for same data', function () {
            $report = Report::factory()->create();

            $hash1 = $this->securityService->generateDataIntegrityHash($report);
            $hash2 = $this->securityService->generateDataIntegrityHash($report);

            expect($hash1)->toBe($hash2);
        });

        it('generates different hash for different data', function () {
            $report1 = Report::factory()->create(['title' => 'Report 1']);
            $report2 = Report::factory()->create(['title' => 'Report 2']);

            $hash1 = $this->securityService->generateDataIntegrityHash($report1);
            $hash2 = $this->securityService->generateDataIntegrityHash($report2);

            expect($hash1)->not->toBe($hash2);
        });

        it('verifies data integrity correctly', function () {
            $report = Report::factory()->create();
            $hash = $this->securityService->generateDataIntegrityHash($report);

            $isValid = $this->securityService->verifyDataIntegrity($report, $hash);

            expect($isValid)->toBe(true);
        });
    });

    describe('maskSensitiveDataForDisplay', function () {
        it('does not mask data for admins', function () {
            $data = ['revenue_generated' => 50000.00];

            $masked = $this->securityService->maskSensitiveDataForDisplay($data, $this->admin);

            expect($masked['revenue_generated'])->toBe(50000.00);
        });

        it('masks sensitive data for regular users', function () {
            $regularUser = User::factory()->create();
            $data = ['revenue_generated' => 27543.50];

            $masked = $this->securityService->maskSensitiveDataForDisplay($data, $regularUser);

            expect($masked['revenue_generated'])->toBeString()
                ->and($masked['revenue_generated'])->toContain('$')
                ->and($masked['revenue_generated'])->not->toContain('27543');
        });
    });
});
