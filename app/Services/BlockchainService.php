<?php

namespace App\Services;

use App\Models\BlockchainRecord;
use App\Models\Maintenance;
use App\Models\MileageReading;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlockchainService
{
    public function hashPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload));
    }

    public function registerRecord(
        string $recordType,
        array $payload,
        ?Vehicle $vehicle = null,
        ?Model $reference = null
    ): BlockchainRecord {
        $dataHash = $this->hashPayload($payload);

        return BlockchainRecord::create([
            'vehicle_id' => $vehicle?->id,
            'record_type' => $recordType,
            'reference_type' => $reference ? $reference->getMorphClass() : null,
            'reference_id' => $reference?->getKey(),
            'data_hash' => $dataHash,
            'contract_address' => config('autochain.blockchain.contract_address'),
            'payload' => $payload,
            'status' => config('autochain.blockchain.enabled') ? 'pending' : 'simulated',
            'tx_hash' => config('autochain.blockchain.enabled') ? null : '0x'.Str::random(64),
        ]);
    }

    public function certifyMileage(MileageReading $reading): BlockchainRecord
    {
        $payload = [
            'vehicle_uuid' => $reading->vehicle->uuid,
            'mileage' => $reading->mileage,
            'recorded_at' => $reading->recorded_at->toIso8601String(),
            'recorder_id' => $reading->recorded_by,
        ];

        $record = $this->registerRecord('mileage', $payload, $reading->vehicle, $reading);

        if ($record->tx_hash) {
            $reading->update([
                'blockchain_tx_hash' => $record->tx_hash,
                'certified_on_chain' => true,
            ]);
        }

        return $record;
    }

    public function certifyMaintenance(Maintenance $maintenance): BlockchainRecord
    {
        $payload = [
            'vehicle_uuid' => $maintenance->vehicle->uuid,
            'intervention_type' => $maintenance->intervention_type,
            'mileage' => $maintenance->mileage_at_service,
            'service_date' => $maintenance->service_date->format('Y-m-d'),
            'mechanic_id' => $maintenance->mechanic_id,
            'parts_hash' => hash('sha256', json_encode($maintenance->parts_changed ?? [])),
        ];

        $record = $this->registerRecord('maintenance', $payload, $maintenance->vehicle, $maintenance);

        if ($record->tx_hash) {
            $maintenance->update([
                'blockchain_tx_hash' => $record->tx_hash,
                'blockchain_record_id' => (string) $record->id,
                'certified_on_chain' => true,
            ]);
        }

        return $record;
    }

    public function confirmTransaction(BlockchainRecord $record, string $txHash, ?string $blockNumber = null): BlockchainRecord
    {
        $record->update([
            'tx_hash' => $txHash,
            'block_number' => $blockNumber,
            'status' => 'confirmed',
        ]);

        return $record->fresh();
    }

    public function confirmOnChain(Model $reference, string $txHash, ?string $blockNumber = null): void
    {
        $record = BlockchainRecord::query()
            ->where('reference_type', $reference->getMorphClass())
            ->where('reference_id', $reference->getKey())
            ->latest()
            ->first();

        if ($record) {
            $this->confirmTransaction($record, $txHash, $blockNumber);
        }
    }

    public function getContractConfig(): array
    {
        $path = storage_path('blockchain/AutoChainRegistry.json');

        if (! file_exists($path)) {
            return ['abi' => [], 'contractAddress' => config('autochain.blockchain.contract_address')];
        }

        $data = json_decode(file_get_contents($path), true);

        return [
            'abi' => $data['abi'] ?? [],
            'contractAddress' => $data['contractAddress'] ?? config('autochain.blockchain.contract_address'),
            'chainId' => $data['chainId'] ?? config('autochain.blockchain.chain_id'),
        ];
    }

    public function vehicleUuidHash(Vehicle $vehicle): string
    {
        return hash('sha256', $vehicle->uuid);
    }
}
