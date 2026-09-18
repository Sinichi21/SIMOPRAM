<?php

use App\Models\ActivityRegistration;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
});

test('expired access form recovers without granting access until a fresh form is submitted', function () {
    $token = str_repeat('b', 64);
    $member = ActivityRegistration::factory()->create(['token_hash' => hash('sha256', $token)]);
    $this->get(route('activity-access.open', $token))->assertOk();

    $response = $this->post(route('activity-access.enter', $token), ['_token' => 'expired-token'])
        ->assertStatus(303)
        ->assertSessionMissing('activity_participant')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertRedirect(route('activity-access.open', ['token' => $token, 'session_refreshed' => 1], absolute: false));

    $page = $this->get($response->headers->get('Location'))
        ->assertOk()->assertSee('Sesi formulir telah diperbarui.');
    preg_match('/name="_token" value="([^"]+)"/', $page->getContent(), $matches);
    $this->post(route('activity-access.enter', $token), ['_token' => $matches[1]])
        ->assertRedirect(route('activity-access.portal', $member));
    $this->get(route('activity-access.portal', $member))->assertOk();
});

test('access form stays on the current origin even when a different root URL is configured', function () {
    $token = str_repeat('c', 64);
    ActivityRegistration::factory()->create(['token_hash' => hash('sha256', $token)]);
    URL::forceRootUrl('https://other.example.test');

    $this->get('/akses-kegiatan/'.$token)->assertOk()
        ->assertSee('action="/akses-kegiatan/'.$token.'"', false);
});

test('CSRF recovery does not bypass revoked links or protect other actions less strictly', function () {
    $token = str_repeat('d', 64);
    $member = ActivityRegistration::factory()->create(['token_hash' => hash('sha256', $token)]);
    $this->get(route('activity-access.open', $token))->assertOk();
    $member->forceFill(['token_hash' => null])->save();

    $response = $this->post(route('activity-access.enter', $token))
        ->assertStatus(303)->assertSessionMissing('activity_participant');
    $this->get($response->headers->get('Location'))->assertNotFound();
    $this->post(route('activity-access.check-in', $member))->assertStatus(419);
    $this->postJson(route('activity-access.enter', $token))->assertStatus(419);
    expect($member->fresh()->checked_in_at)->toBeNull();
});

test('temporary access form submits a real CSRF token and opens a participant session', function () {
    $token = str_repeat('a', 64);
    $member = ActivityRegistration::factory()->create(['token_hash' => hash('sha256', $token)]);
    $response = $this->get(route('activity-access.open', $token))->assertOk();
    $html = $response->getContent();
    expect($html)->not->toContain('@csrf');
    preg_match('/name="_token" value="([^"]+)"/', $html, $matches);
    expect($matches)->toHaveCount(2);
    $this->post(route('activity-access.enter', $token), ['_token' => $matches[1]])
        ->assertRedirect(route('activity-access.portal', $member));
    $this->get(route('activity-access.portal', $member))->assertOk();
});
