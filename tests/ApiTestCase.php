<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function actingAsRole(UserRole $role): User
    {
        $user = User::factory()->role($role)->create();
        Sanctum::actingAs($user);

        return $user;
    }
}
