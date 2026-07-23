<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\WalletChallengeRequest;
use App\Http\Requests\Auth\WalletLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\WalletVerifyRequest;
use App\Models\User;
use App\Services\Web3AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private Web3AuthService $web3Auth)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'driver',
            'phone' => $request->phone,
            'wallet_address' => $request->wallet_address ? strtolower($request->wallet_address) : null,
        ]);

        $token = $user->createToken('autochain-api')->plainTextToken;

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        $token = $user->createToken('autochain-api')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function walletChallenge(WalletChallengeRequest $request): JsonResponse
    {
        $wallet = strtolower($request->wallet_address);
        $nonce = $this->web3Auth->generateChallengeNonce($wallet);

        return response()->json([
            'nonce' => $nonce,
            'message' => $this->web3Auth->buildLoginMessage($wallet, $nonce),
            'wallet_address' => $wallet,
        ]);
    }

    public function walletLogin(WalletLoginRequest $request): JsonResponse
    {
        $wallet = strtolower($request->wallet_address);

        if (! $this->web3Auth->verifyChallenge($wallet, $request->message, $request->signature)) {
            return response()->json(['message' => 'Signature MetaMask invalide ou expirée.'], 422);
        }

        $user = $this->web3Auth->findUserByWallet($wallet);

        if (! $user) {
            return response()->json([
                'message' => 'Aucun compte associé à ce wallet. Connectez-vous par email puis liez MetaMask.',
            ], 404);
        }

        $token = $user->createToken('autochain-api')->plainTextToken;

        return response()->json([
            'message' => 'Connexion Web3 réussie.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function walletNonce(Request $request): JsonResponse
    {
        $user = $request->user();
        $nonce = $this->web3Auth->generateNonce($user);

        $address = $request->query('wallet_address')
            ? strtolower($request->query('wallet_address'))
            : ($user->wallet_address ?? 'non-lié');

        return response()->json([
            'nonce' => $nonce,
            'message' => $this->web3Auth->buildLoginMessage($address, $nonce),
        ]);
    }

    public function linkWallet(WalletVerifyRequest $request): JsonResponse
    {
        $user = $request->user();
        $wallet = strtolower($request->wallet_address);

        if (! $this->web3Auth->verifySignature($wallet, $request->message, $request->signature)) {
            return response()->json(['message' => 'Signature wallet invalide.'], 422);
        }

        $exists = User::where('wallet_address', $wallet)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            return response()->json(['message' => 'Ce wallet est déjà associé à un autre compte.'], 422);
        }

        $user = $this->web3Auth->linkWallet($user, $wallet);

        return response()->json([
            'message' => 'Wallet lié avec succès.',
            'user' => $user,
        ]);
    }
}
