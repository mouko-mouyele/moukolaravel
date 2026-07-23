<?php

namespace App\Http\Controllers\Api;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateSaleRequest;
use App\Models\BlockchainRecord;
use App\Models\PendingSignature;
use App\Models\Vehicle;
use App\Services\BlockchainService;
use App\Services\SaleSignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockchainController extends Controller
{
    public function __construct(
        private BlockchainService $blockchain,
        private SaleSignatureService $saleSignatures
    ) {
    }

    public function records(Request $request): JsonResponse
    {
        $records = BlockchainRecord::query()
            ->with('vehicle:id,uuid,license_plate')
            ->when($request->vehicle_id, fn ($q) => $q->where('vehicle_id', $request->vehicle_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($records);
    }

    public function pendingSales(Request $request): JsonResponse
    {
        $sales = PendingSignature::query()
            ->where('operation_type', 'vehicle_sale')
            ->with(['vehicle:id,uuid,license_plate,brand,model,current_mileage', 'initiatedBy:id,name'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status), fn ($q) => $q->where('status', 'pending'))
            ->when($request->buyer_wallet, fn ($q) => $q->where('buyer_wallet', strtolower($request->buyer_wallet)))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($sales);
    }

    public function showPendingSale(PendingSignature $pendingSignature): JsonResponse
    {
        if ($pendingSignature->operation_type !== 'vehicle_sale') {
            return response()->json(['message' => 'Opération invalide.'], 422);
        }

        $pendingSignature->load(['vehicle', 'initiatedBy:id,name,email']);

        return response()->json([
            'pending_signature' => $pendingSignature,
            'buyer_message' => $this->saleSignatures->buildBuyerMessage($pendingSignature),
            'vehicle_uuid_hash' => $pendingSignature->vehicle
                ? $this->blockchain->vehicleUuidHash($pendingSignature->vehicle)
                : null,
        ]);
    }

    public function prepareSale(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'buyer_wallet' => ['required', 'string', 'size:42', 'regex:/^0x[a-fA-F0-9]{40}$/'],
            'sale_price' => ['required', 'numeric', 'min:0'],
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($vehicle->status === VehicleStatus::Sold) {
            return response()->json(['message' => 'Ce véhicule est déjà vendu.'], 422);
        }

        $payload = $this->saleSignatures->buildPayload(
            $vehicle,
            $request->buyer_wallet,
            (float) $request->sale_price,
            $request->user()->id
        );

        $dataHash = $this->blockchain->hashPayload($payload);

        return response()->json([
            'payload' => $payload,
            'data_hash' => $dataHash,
            'admin_message' => $this->saleSignatures->buildAdminMessage($payload, $dataHash),
            'vehicle_uuid_hash' => $this->blockchain->vehicleUuidHash($vehicle),
        ]);
    }

    public function confirm(Request $request, BlockchainRecord $record): JsonResponse
    {
        $request->validate([
            'tx_hash' => ['required', 'string'],
            'block_number' => ['nullable', 'string'],
        ]);

        $record = $this->blockchain->confirmTransaction(
            $record,
            $request->tx_hash,
            $request->block_number
        );

        return response()->json([
            'message' => 'Transaction blockchain confirmée.',
            'record' => $record,
        ]);
    }

    public function initiateSale(InitiateSaleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        if ($vehicle->status === VehicleStatus::Sold) {
            return response()->json(['message' => 'Ce véhicule est déjà vendu.'], 422);
        }

        $hasPending = PendingSignature::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json(['message' => 'Une vente est déjà en attente pour ce véhicule.'], 422);
        }

        $payload = $this->saleSignatures->buildPayload(
            $vehicle,
            $request->buyer_wallet,
            (float) $request->sale_price,
            $request->user()->id
        );

        $dataHash = $this->blockchain->hashPayload($payload);
        $adminMessage = $this->saleSignatures->buildAdminMessage($payload, $dataHash);
        $adminWallet = $request->user()->wallet_address;

        if (! $request->admin_signature) {
            return response()->json([
                'message' => 'Signature MetaMask administrateur requise.',
                'admin_message' => $adminMessage,
                'data_hash' => $dataHash,
            ], 422);
        }

        if (! $this->saleSignatures->verifySaleSignature($adminMessage, $request->admin_signature)) {
            return response()->json(['message' => 'Signature administrateur invalide.'], 422);
        }

        $pending = PendingSignature::create([
            'operation_type' => 'vehicle_sale',
            'vehicle_id' => $vehicle->id,
            'payload' => $payload,
            'data_hash' => $dataHash,
            'initiated_by' => $request->user()->id,
            'admin_signature' => $request->admin_signature,
            'admin_wallet' => $adminWallet ? strtolower($adminWallet) : null,
            'buyer_wallet' => strtolower($request->buyer_wallet),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'message' => 'Vente initiée. Signature acheteur MetaMask requise.',
            'pending_signature' => $pending->load('vehicle:id,license_plate,brand,model'),
            'buyer_message' => $this->saleSignatures->buildBuyerMessage($pending),
        ], 201);
    }

    public function signSale(Request $request, PendingSignature $pendingSignature): JsonResponse
    {
        $request->validate([
            'buyer_signature' => ['required', 'string'],
            'on_chain_tx_hash' => ['nullable', 'string'],
        ]);

        if ($pendingSignature->operation_type !== 'vehicle_sale') {
            return response()->json(['message' => 'Opération invalide.'], 422);
        }

        if ($pendingSignature->status !== 'pending') {
            return response()->json(['message' => 'Cette opération n\'est plus en attente.'], 422);
        }

        if ($pendingSignature->expires_at && $pendingSignature->expires_at->isPast()) {
            $pendingSignature->update(['status' => 'expired']);

            return response()->json(['message' => 'La demande de signature a expiré.'], 422);
        }

        if (empty($pendingSignature->admin_signature)) {
            return response()->json(['message' => 'Signature administrateur manquante.'], 422);
        }

        $buyerMessage = $this->saleSignatures->buildBuyerMessage($pendingSignature);
        $buyerWallet = $pendingSignature->buyer_wallet;

        if (! $this->saleSignatures->verifySaleSignature($buyerMessage, $request->buyer_signature, $buyerWallet)) {
            return response()->json(['message' => 'Signature acheteur MetaMask invalide.'], 422);
        }

        $userWallet = $request->user()->wallet_address;
        if ($userWallet && strtolower($userWallet) !== strtolower($buyerWallet)) {
            return response()->json(['message' => 'Votre wallet ne correspond pas à l\'acheteur désigné.'], 403);
        }

        $pendingSignature->update([
            'buyer_signature' => $request->buyer_signature,
            'status' => 'completed',
        ]);

        $vehicle = $pendingSignature->vehicle;
        $vehicle?->update(['status' => VehicleStatus::Sold]);

        $record = $this->blockchain->registerRecord('vehicle_sale', $pendingSignature->payload, $vehicle);

        if ($request->on_chain_tx_hash) {
            $this->blockchain->confirmTransaction($record, $request->on_chain_tx_hash);
        }

        return response()->json([
            'message' => 'Vente certifiée avec double signature MetaMask.',
            'pending_signature' => $pendingSignature->fresh()->load('vehicle'),
            'blockchain_record' => $record,
        ]);
    }

    public function config(): JsonResponse
    {
        $contract = $this->blockchain->getContractConfig();

        return response()->json([
            'enabled' => config('autochain.blockchain.enabled'),
            'contract_address' => config('autochain.blockchain.contract_address'),
            'chain_id' => (int) config('autochain.blockchain.chain_id'),
            'contract_abi' => $contract['abi'] ?? [],
        ]);
    }
}
