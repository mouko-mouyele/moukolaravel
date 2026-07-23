<?php

namespace App\Services;

use App\Models\PendingSignature;
use App\Models\Vehicle;

class SaleSignatureService
{
    public function __construct(private BlockchainService $blockchain)
    {
    }

    public function buildPayload(Vehicle $vehicle, string $buyerWallet, float $salePrice, int $adminId): array
    {
        return [
            'vehicle_uuid' => $vehicle->uuid,
            'license_plate' => $vehicle->license_plate,
            'sale_price' => $salePrice,
            'buyer_wallet' => strtolower($buyerWallet),
            'seller_admin_id' => $adminId,
        ];
    }

    public function buildAdminMessage(array $payload, string $dataHash): string
    {
        return implode("\n", [
            'AutoChain Emma+ - Vente véhicule (Administrateur)',
            "Véhicule: {$payload['vehicle_uuid']}",
            "Immatriculation: {$payload['license_plate']}",
            "Prix: {$payload['sale_price']} EUR",
            "Acheteur: {$payload['buyer_wallet']}",
            "Hash: {$dataHash}",
            'Auteur: Moïse',
        ]);
    }

    public function buildBuyerMessage(PendingSignature $pending): string
    {
        $payload = $pending->payload;

        return implode("\n", [
            'AutoChain Emma+ - Vente véhicule (Acheteur)',
            "Véhicule: {$payload['vehicle_uuid']}",
            "Immatriculation: ".($payload['license_plate'] ?? 'N/A'),
            "Prix: {$payload['sale_price']} EUR",
            "Acheteur: {$pending->buyer_wallet}",
            "Hash: {$pending->data_hash}",
            "Référence: #{$pending->id}",
            'Auteur: Moïse',
        ]);
    }

    public function verifySaleSignature(string $message, string $signature, ?string $expectedWallet = null): bool
    {
        if (! str_starts_with(strtolower($signature), '0x') || strlen($signature) < 10) {
            return false;
        }

        if (config('autochain.blockchain.strict_signatures')) {
            return false;
        }

        if ($expectedWallet) {
            return str_contains(strtolower($message), strtolower($expectedWallet));
        }

        return true;
    }
}
