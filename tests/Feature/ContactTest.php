<?php

use App\Models\Contact;
use App\Models\User;

test('contacts screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/contacts');

    $response->assertStatus(200);
});

test('users can create contacts', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/contacts', [
        'name' => 'Test Contact',
        'email' => 'test@example.com',
        'address' => '123 Test St',
        'phone' => '1234567890',
    ]);

    $response->assertRedirect('/contacts');
    $this->assertDatabaseHas('contacts', [
        'name' => 'Test Contact',
        'email' => 'test@example.com',
        'user_id' => $user->id,
    ]);
});

test('users can update contacts', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->put("/contacts/{$contact->id}", [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'address' => 'Updated Address',
        'phone' => '0987654321',
    ]);

    $response->assertRedirect('/contacts');
    $this->assertDatabaseHas('contacts', [
        'id' => $contact->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('users can delete contacts', function () {
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete("/contacts/{$contact->id}");

    $response->assertRedirect('/contacts');
    $this->assertDatabaseMissing('contacts', [
        'id' => $contact->id,
    ]);
});

test('users cannot access others contacts', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $contact = Contact::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)->get("/contacts/{$contact->id}/edit");

    $response->assertStatus(403);
});
