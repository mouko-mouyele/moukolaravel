<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_challenge_returns_message(): void
    {
        $wallet = '0x0000000000000000000000000000000000000001';

        $this->postJson('/api/v1/auth/wallet/challenge', [
            'wallet_address' => $wallet,
        ])->assertOk()
            ->assertJsonStructure(['nonce', 'message', 'wallet_address']);
    }

    public function test_wallet_login_with_valid_signature(): void
    {
        $wallet = '0x0000000000000000000000000000000000000001';

        User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'wallet_address' => $wallet,
        ]);

        $nonce = 'test-nonce-12345';
        Cache::put("wallet_nonce:address:{$wallet}", $nonce, now()->addMinutes(10));

        $message = "AutoChain Emma+ - Authentification Web3\nAdresse: {$wallet}\nNonce: {$nonce}\nAuteur: Moïse";

        $this->postJson('/api/v1/auth/wallet/login', [
            'wallet_address' => $wallet,
            'message' => $message,
            'signature' => '0x'.str_repeat('ab', 32),
        ])->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_wallet_login_fails_for_unknown_wallet(): void
    {
        $wallet = '0x0000000000000000000000000000000000000099';
        $nonce = 'unknown-nonce';
        Cache::put("wallet_nonce:address:{$wallet}", $nonce, now()->addMinutes(10));

        $message = "AutoChain Emma+ - Authentification Web3\nAdresse: {$wallet}\nNonce: {$nonce}\nAuteur: Moïse";

        $this->postJson('/api/v1/auth/wallet/login', [
            'wallet_address' => $wallet,
            'message' => $message,
            'signature' => '0x'.str_repeat('cd', 32),
        ])->assertNotFound();
    }
}
