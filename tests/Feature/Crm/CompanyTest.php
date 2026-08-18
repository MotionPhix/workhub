<?php

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('guests are redirected away from the companies index', function () {
    auth()->logout();

    $response = $this->get(route('companies.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view their companies', function () {
    Company::factory()->count(2)->create(['user_id' => $this->user->id]);
    // A different user's company must not appear.
    Company::factory()->create(['user_id' => User::factory()->create()->id]);

    $response = $this->get(route('companies.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('companies/index')
        ->has('companies.data', 2));
});

test('companies can be filtered by organization type', function () {
    Company::factory()->create([
        'user_id' => $this->user->id,
        'organization_type' => 'ngo',
    ]);
    Company::factory()->create([
        'user_id' => $this->user->id,
        'organization_type' => 'business',
    ]);

    $response = $this->get(route('companies.index', ['organization_type' => 'ngo']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('companies.data', 1)
        ->where('filters.organization_type', 'ngo'));
});

test('a company can be created via the store endpoint', function () {
    $payload = [
        'name' => 'Malawi Red Cross Society',
        'industry' => 'Humanitarian',
        'organization_type' => 'ngo',
    ];

    $response = $this->post(route('companies.store'), $payload);
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('companies', [
        'user_id' => $this->user->id,
        'name' => 'Malawi Red Cross Society',
    ]);
});

test('store validates required fields', function () {
    $response = $this->post(route('companies.store'), []);
    $response->assertSessionHasErrors('name');
});

test('an authenticated user can view a single company with its contacts', function () {
    $company = Company::factory()->create(['user_id' => $this->user->id]);
    Contact::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $company->id,
    ]);

    $response = $this->get(route('companies.show', $company));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('companies/show')
        ->where('company.id', $company->id)
        ->has('company.contacts', 1));
});
