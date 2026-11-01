<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');
pest()->extend(TestCase::class)->in('Unit');
pest()->beforeEach(fn () => $this->withoutVite())->in('Feature');

/** Legt einen Betrieb mit Inhaber an. */
function betrieb(string $name = 'Müller Haustechnik'): array
{
    $tenant = Tenant::create(['name' => $name]);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner']);

    return [$tenant, $user];
}
