<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private WhatsAppService $whatsAppService
    ) {
    }

    /**
     * Send OTP code to phone number.
     * 
     * POST /api/v1/auth/send-otp
     */
    public function send(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');

        // Check rate limit
        if ($this->otpService->isRateLimited($phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Aguarde para solicitar novo código',
                'retryAfter' => $this->otpService->getRetryAfter($phone),
            ], 429);
        }

        // Generate and send OTP
        $otp = $this->otpService->generate($phone, 'login');
        $this->whatsAppService->sendOtp($phone, $otp->code);

        return response()->json([
            'success' => true,
            'expiresIn' => 300,
            'message' => 'Código enviado para seu WhatsApp',
        ]);
    }

    /**
     * Verify OTP code.
     * 
     * POST /api/v1/auth/verify-otp
     */
    public function verify(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $otp = $this->otpService->verify(
            $validated['phone'],
            $validated['code']
        );

        if (!$otp) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido ou expirado',
                'code' => 'INVALID_OTP',
            ], 401);
        }

        // Find existing user
        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            return response()->json([
                'needsRegistration' => true,
                'phone' => $validated['phone'],
            ]);
        }

        // Mark phone as verified
        $user->update([
            'phone_verified' => true,
            'phone_verified_at' => now(),
        ]);

        // Generate tokens
        $token = $user->createToken('app')->plainTextToken;
        $refreshToken = $this->generateRefreshToken($user);

        return response()->json([
            'token' => $token,
            'refreshToken' => $refreshToken,
            'user' => new UserResource($user->load('bairro', 'roles')),
            'expiresIn' => config('sanctum.expiration', 3600),
        ]);
    }

    /**
     * Generate a refresh token for the user.
     */
    protected function generateRefreshToken(User $user): string
    {
        // Create a longer-lived token for refresh
        $token = $user->createToken('refresh', ['refresh'], now()->addDays(30));
        return $token->plainTextToken;
    }
}
