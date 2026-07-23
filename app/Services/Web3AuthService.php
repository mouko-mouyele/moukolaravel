<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Web3AuthService
{
    public function generateNonce(User $user): string
    {
        $nonce = Str::random(32);
        Cache::put("wallet_nonce:user:{$user->id}", $nonce, now()->addMinutes(10));

        return $nonce;
    }

    public function generateChallengeNonce(string $walletAddress): string
    {
        $wallet = strtolower($walletAddress);
        $nonce = Str::random(32);
        Cache::put("wallet_nonce:address:{$wallet}", $nonce, now()->addMinutes(10));

        return $nonce;
    }

    public function buildSignMessage(User $user, string $nonce): string
    {
        $address = $user->wallet_address ?? 'non-lié';

        return $this->buildLoginMessage($address, $nonce);
    }

    public function buildLoginMessage(string $walletAddress, string $nonce): string
    {
        return "AutoChain Emma+ - Authentification Web3\nAdresse: {$walletAddress}\nNonce: {$nonce}\nAuteur: Moïse";
    }

    public function verifySignature(string $walletAddress, string $message, string $signature): bool
    {
        if (config('autochain.blockchain.strict_signatures')) {
            // Production : installer web3p/ethereum-util pour recovery ECDSA
            return false;
        }

        return $this->isValidDevSignature($walletAddress, $message, $signature);
    }

    public function verifyChallenge(string $walletAddress, string $message, string $signature): bool
    {
        $wallet = strtolower($walletAddress);
        $cachedNonce = Cache::get("wallet_nonce:address:{$wallet}");

        if (! $cachedNonce || ! str_contains($message, $cachedNonce)) {
            return false;
        }

        if (! $this->verifySignature($wallet, $message, $signature)) {
            return false;
        }

        Cache::forget("wallet_nonce:address:{$wallet}");

        return true;
    }

    public function linkWallet(User $user, string $walletAddress): User
    {
        $user->update([
            'wallet_address' => strtolower($walletAddress),
        ]);

        return $user->fresh();
    }

    public function findUserByWallet(string $walletAddress): ?User
    {
        return User::query()
            ->where('wallet_address', strtolower($walletAddress))
            ->where('is_active', true)
            ->first();
    }

    private function isValidDevSignature(string $walletAddress, string $message, string $signature): bool
    {
        if (! str_starts_with(strtolower($signature), '0x') || strlen($signature) < 10) {
            return false;
        }

        return str_contains($message, strtolower($walletAddress))
            || str_contains($message, $walletAddress);
    }
}
