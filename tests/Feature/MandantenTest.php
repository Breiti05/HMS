<?php

use App\Models\Article;
use App\Models\Tenant;
use App\Models\User;

it('legt bei der Registrierung Betrieb und Inhaber an und meldet an', function () {
    $this->post('/registrieren', [
        'company' => 'Müller Haustechnik',
        'name' => 'Hans Müller',
        'email' => 'hans@mueller-haustechnik.de',
        'password' => 'geheim-1234',
        'password_confirmation' => 'geheim-1234',
    ])->assertRedirect('/');

    $user = User::where('email', 'hans@mueller-haustechnik.de')->firstOrFail();
    expect($user->role)->toBe('owner')
        ->and($user->tenant->name)->toBe('Müller Haustechnik');
    $this->assertAuthenticatedAs($user);
});

it('lehnt eine Registrierung ohne Betriebsnamen ab', function () {
    $this->post('/registrieren', [
        'name' => 'Hans Müller',
        'email' => 'hans@example.de',
        'password' => 'geheim-1234',
        'password_confirmation' => 'geheim-1234',
    ])->assertSessionHasErrors('company');
});

it('lässt Gäste nicht auf das Dashboard', function () {
    $this->get('/')->assertRedirect('/anmelden');
});

it('meldet mit richtigen Zugangsdaten an und mit falschen nicht', function () {
    [, $user] = betrieb('Müller Haustechnik');

    $this->post('/anmelden', ['email' => $user->email, 'password' => 'falsch'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post('/anmelden', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

it('trennt Artikel strikt nach Betrieb', function () {
    [$a, $userA] = betrieb('Betrieb A');
    [$b, $userB] = betrieb('Betrieb B');

    $this->actingAs($userA);
    Article::create(['number' => 'R-100', 'name' => 'Rohr 20mm', 'unit' => 'm']);

    $this->actingAs($userB);
    Article::create(['number' => 'R-100', 'name' => 'Rohr 20mm', 'unit' => 'm']);
    Article::create(['number' => 'K-200', 'name' => 'Kabel', 'unit' => 'm']);

    expect(Article::count())->toBe(2);

    $this->actingAs($userA);
    expect(Article::count())->toBe(1)
        ->and(Article::first()->tenant_id)->toBe($a->id);
});

it('setzt die Mandanten-ID beim Anlegen automatisch', function () {
    [$tenant, $user] = betrieb('Betrieb A');
    $this->actingAs($user);

    $article = Article::create(['number' => 'R-100', 'name' => 'Rohr', 'unit' => 'm']);

    expect($article->tenant_id)->toBe($tenant->id);
});

it('findet fremde Artikel nicht über die ID', function () {
    [, $userA] = betrieb('Betrieb A');
    [, $userB] = betrieb('Betrieb B');

    $this->actingAs($userA);
    $fremd = Article::create(['number' => 'R-100', 'name' => 'Rohr', 'unit' => 'm']);

    $this->actingAs($userB);
    expect(Article::find($fremd->id))->toBeNull();
});

it('erlaubt dieselbe Artikelnummer in verschiedenen Betrieben, aber nicht doppelt im selben', function () {
    [, $userA] = betrieb('Betrieb A');
    $this->actingAs($userA);

    Article::create(['number' => 'R-100', 'name' => 'Rohr', 'unit' => 'm']);

    expect(fn () => Article::create(['number' => 'R-100', 'name' => 'Rohr 2', 'unit' => 'm']))
        ->toThrow(Illuminate\Database\QueryException::class);
});
