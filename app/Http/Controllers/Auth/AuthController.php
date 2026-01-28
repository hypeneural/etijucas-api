<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Register a new user after OTP verification.
     * 
     * POST /api/v1/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Verify that the phone was recently verified via OTP
        $recentOtp = OtpCode::forPhone($validated['phone'])
            ->whereNotNull('verified_at')
            ->where('verified_at', '>', now()->subMinutes(10))
            ->first();

        if (!$recentOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Verifique seu telefone primeiro via OTP',
                'code' => 'OTP_NOT_VERIFIED',
            ], 400);
        }

        // Create user
        $user = User::create([
            'phone' => $validated['phone'],
            'password' => $validated['password'], // Cast will hash this
            'nome' => $validated['nome'],
            'email' => $validated['email'] ?? null,
            'bairro_id' => $validated['bairro_id'] ?? null,
            'address' => $validated['address'] ?? null,
            'phone_verified' => true,
            'phone_verified_at' => now(),
        ]);

        // Assign default role
        $user->assignRole('user');

        // Generate tokens
        $token = $user->createToken('app')->plainTextToken;
        $refreshToken = $user->createToken('refresh', ['refresh'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => new UserResource($user->load('bairro', 'roles')),
            'expiresIn' => config('sanctum.expiration', 3600),
        ], 201);
    }

    /**
     * Login with phone and password.
     * 
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('phone', $request->phone)->first();

        // Check if user exists and password is correct
        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciais inválidas',
                'code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        // Generate tokens
        $token = $user->createToken('app')->plainTextToken;
        $refreshToken = $user->createToken('refresh', ['refresh'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => new UserResource($user->load('bairro', 'roles')),
            'expiresIn' => config('sanctum.expiration', 3600),
        ]);
    }

    /**
     * Refresh the access token.
     * 
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refreshToken' => ['required', 'string'],
        ]);

        // Parse the refresh token
        $tokenParts = explode('|', $request->refreshToken);
        if (count($tokenParts) !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token inválido',
                'code' => 'INVALID_REFRESH_TOKEN',
            ], 401);
        }

        $tokenId = $tokenParts[0];
        $plainToken = $tokenParts[1];

        // Find the token
        $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->refreshToken);

        if (!$token || !$token->can('refresh') || $token->expires_at?->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh token inválido ou expirado',
                'code' => 'INVALID_REFRESH_TOKEN',
            ], 401);
        }

        $user = $token->tokenable;

        // Revoke old refresh token
        $token->delete();

        // Generate new tokens
        $newToken = $user->createToken('app')->plainTextToken;
        $newRefreshToken = $user->createToken('refresh', ['refresh'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'token' => $newToken,
            'refreshToken' => $newRefreshToken,
            'expiresIn' => config('sanctum.expiration', 3600),
        ]);
    }

    /**
     * Logout the user.
     * 
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $allDevices = $request->boolean('allDevices', false);

        if ($allDevices) {
            // Revoke all tokens for this user
            $request->user()->tokens()->delete();
        } else {
            // Revoke only the current token
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout realizado com sucesso',
        ]);
    }

    /**
     * Get the authenticated user.
     * 
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('bairro', 'roles')),
        ]);
    }
}
