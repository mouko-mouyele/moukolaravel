<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RgpdController extends Controller
{
    public function exportMyData(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'exported_at' => now()->toIso8601String(),
            'project' => config('autochain.name'),
            'personal_data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'wallet_address' => $user->wallet_address,
                'created_at' => $user->created_at,
            ],
            'note' => 'Les données véhicules on-chain ne contiennent que UUID et hash (pas de données nominatives).',
        ]);
    }

    public function deleteMyAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::SuperAdmin) {
            return response()->json(['message' => 'Le compte super admin ne peut pas être supprimé via cette route.'], 422);
        }

        $user->tokens()->delete();
        $user->update([
            'is_active' => false,
            'email' => 'deleted_'.$user->id.'@anon.local',
            'wallet_address' => null,
        ]);

        return response()->json(['message' => 'Compte désactivé et données personnelles anonymisées (RGPD).']);
    }

    public function privacyPolicy(): JsonResponse
    {
        return response()->json([
            'project' => config('autochain.name'),
            'author' => config('autochain.author'),
            'policy' => [
                'collecte' => 'Nom, email, rôle, wallet (optionnel). Véhicules : VIN, immatriculation, documents.',
                'blockchain' => 'Seuls UUID véhicule et hash SHA-256 sont ancrés on-chain. Aucune donnée nominative on-chain.',
                'documents' => 'Stockage privé chiffré par hash d\'intégrité. Certificats publics optionnels via IPFS.',
                'droits' => 'Export JSON via GET /rgpd/export. Suppression via DELETE /rgpd/account.',
                'conservation' => 'Données conservées tant que le véhicule est actif. Archivage soft-delete véhicules.',
                'contact' => config('autochain.author'),
            ],
        ]);
    }
}
