<?php

use App\Enums\ContactStatus;
use App\Models\Contact;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('guests are redirected away from the prospects index', function () {
    auth()->logout();

    $response = $this->get(route('prospects.index'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can view their prospects', function () {
    Contact::factory()->count(3)->create(['user_id' => $this->user->id]);
    // A different user's contact must not appear.
    Contact::factory()->create(['user_id' => User::factory()->create()->id]);

    $response = $this->get(route('prospects.index'));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('prospects/index')
        ->has('contacts.data', 3));
});

test('prospects can be filtered by status', function () {
    Contact::factory()->create([
        'user_id' => $this->user->id,
        'status' => ContactStatus::Interested,
    ]);
    Contact::factory()->create([
        'user_id' => $this->user->id,
        'status' => ContactStatus::New,
    ]);

    $response = $this->get(route('prospects.index', ['status' => 'interested']));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('contacts.data', 1)
        ->where('filters.status', 'interested'));
});

test('a prospect can be created via the store endpoint', function () {
    $payload = [
        'first_name' => 'Tiyamike',
        'last_name' => 'Banda',
        'email' => 'tiyamike.banda@example.mw',
        'status' => 'new',
    ];

    $response = $this->post(route('prospects.store'), $payload);
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('contacts', [
        'user_id' => $this->user->id,
        'first_name' => 'Tiyamike',
        'email' => 'tiyamike.banda@example.mw',
    ]);
});

test('store validates required fields', function () {
    $response = $this->post(route('prospects.store'), []);
    $response->assertSessionHasErrors('first_name');
});

test('an authenticated user can view a single prospect', function () {
    $contact = Contact::factory()->create(['user_id' => $this->user->id]);

    $response = $this->get(route('prospects.show', $contact));
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('prospects/show')
        ->where('contact.id', $contact->id));
});
