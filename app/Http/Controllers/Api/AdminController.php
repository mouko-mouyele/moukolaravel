<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminController extends Controller
{
    public function archiveStats(): JsonResponse
    {
        return response()->json([
            'active_vehicles' => Vehicle::count(),
            'archived_vehicles' => Vehicle::onlyTrashed()->count(),
            'blockchain' => [
                'enabled' => config('autochain.blockchain.enabled'),
                'contract_address' => config('autochain.blockchain.contract_address'),
                'chain_id' => config('autochain.blockchain.chain_id'),
                'rpc_url' => config('autochain.blockchain.rpc_url'),
            ],
            'ipfs' => [
                'enabled' => config('autochain.ipfs.enabled'),
                'gateway' => config('autochain.ipfs.gateway'),
            ],
        ]);
    }

    public function updateBlockchainConfig(Request $request): JsonResponse
    {
        $request->validate([
            'contract_address' => ['nullable', 'string', 'size:42'],
            'chain_id' => ['nullable', 'integer'],
            'rpc_url' => ['nullable', 'url'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return response()->json(['message' => 'Fichier .env introuvable.'], 422);
        }

        $env = file_get_contents($envPath);
        $map = [
            'BLOCKCHAIN_ENABLED' => $request->has('enabled') ? ($request->boolean('enabled') ? 'true' : 'false') : null,
            'BLOCKCHAIN_CONTRACT_ADDRESS' => $request->contract_address,
            'BLOCKCHAIN_CHAIN_ID' => $request->chain_id,
            'BLOCKCHAIN_RPC_URL' => $request->rpc_url,
        ];

        foreach ($map as $key => $value) {
            if ($value === null) {
                continue;
            }
            $pattern = "/^{$key}=.*/m";
            $line = "{$key}={$value}";
            $env = preg_match($pattern, $env)
                ? preg_replace($pattern, $line, $env)
                : $env.PHP_EOL.$line;
        }

        file_put_contents($envPath, $env);
        Artisan::call('config:clear');

        return response()->json([
            'message' => 'Configuration blockchain mise à jour.',
            'blockchain' => [
                'enabled' => config('autochain.blockchain.enabled'),
                'contract_address' => config('autochain.blockchain.contract_address'),
            ],
        ]);
    }

    public function runAlerts(): JsonResponse
    {
        Artisan::call('autochain:generate-alerts');

        return response()->json([
            'message' => 'Moteur d\'alertes exécuté.',
            'output' => Artisan::output(),
        ]);
    }
}
