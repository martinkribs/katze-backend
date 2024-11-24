<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Notifications\ResetPasswordNotification;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends BaseController
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'verifyOtp', 'resetPassword']]);
    }

    /**
     * Register a new user.
     *
     * @throws Exception
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            /** @var User $user */
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            event(new Registered($user));

            try {
                /** @var string|null $token */
                $token = Auth::guard('api')->login($user);

                if ($token === null) {
                    throw new JWTException('Failed to create token');
                }

                return $this->respondWithToken($token);
            } catch (JWTException $e) {
                // If token creation fails, we still created the user, so return success with a message
                return response()->json([
                    'message' => 'User registered successfully but could not create token. Please try logging in.',
                    'error' => $e->getMessage()
                ], 201);
            }
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to register user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @throws Exception
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $token = Auth::guard('api')->attempt($request->getCredentials());

            if ($token === false) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            return $this->respondWithToken($token);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
    }

    /**
     * Get the authenticated User.
     */
    public function user(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($user);
    }

    /**
     * Delete the authenticated user.
     */
    public function delete(): JsonResponse
    {
        try {
            /** @var User|null $user */
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Remove user from all games
            $user->games()->detach();

            // Delete the user
            $user->delete();

            // Logout after deletion
            Auth::guard('api')->logout();

            return response()->json(['message' => 'User deleted successfully']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send password reset OTP.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = User::where('email', $validated['email'])->first();

            if(!$user){
                return response()->json(['message' => 'No user found with this email'], 400);
            }

            // Generate 6-digit OTP
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP in password_reset_tokens table
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($otp),
                    'created_at' => now()
                ]
            );

            // Send OTP via email
            $user->notify(new ResetPasswordNotification($otp));

            return response()->json(['message' => 'Password reset code sent to your email']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to send reset code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify OTP and return reset token.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Get reset record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->first();

            if (!$resetRecord) {
                return response()->json(['message' => 'No reset code requested'], 400);
            }

            // Check if OTP has expired (10 minutes)
            if (Carbon::parse($resetRecord->created_at)->addMinutes(10)->isPast()) {
                // Delete expired record
                DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
                return response()->json(['message' => 'Reset code has expired'], 400);
            }

            // Verify OTP
            if (!Hash::check($validated['otp'], $resetRecord->token)) {
                return response()->json(['message' => 'Invalid reset code'], 400);
            }

            // Generate reset token
            $resetToken = Str::random(60);

            // Update token in database
            DB::table('password_reset_tokens')->where('email', $validated['email'])->update([
                'token' => Hash::make($resetToken),
                'created_at' => now() // Reset timer for password reset
            ]);

            return response()->json([
                'message' => 'OTP verified successfully',
                'reset_token' => $resetToken
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to verify OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password using reset token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $user = User::where('email', $validated['email'])->first();

            // Get reset record
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->first();

            if (!$resetRecord) {
                return response()->json(['message' => 'Invalid reset request'], 400);
            }

            // Check if reset token has expired (10 minutes)
            if (Carbon::parse($resetRecord->created_at)->addMinutes(10)->isPast()) {
                DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
                return response()->json(['message' => 'Reset token has expired'], 400);
            }

            // Verify reset token
            if (!Hash::check($validated['reset_token'], $resetRecord->token)) {
                return response()->json(['message' => 'Invalid reset token'], 400);
            }

            // Reset password
            $user->password = Hash::make($validated['password']);
            $user->save();

            // Delete used reset record
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return response()->json(['message' => 'Password has been reset']);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(): JsonResponse
    {
        try {
            Auth::guard('api')->logout();
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not invalidate token'], 500);
        }
    }

    /**
     * Refresh a token.
     *
     * @throws Exception
     */
    public function refresh(): JsonResponse
    {
        try {
            /** @var string $token */
            $token = JWTAuth::parseToken()->refresh();
            return $this->respondWithToken($token);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not refresh token'], 401);
        }
    }

    /**
     * Resend email verification notification.
     */
    public function sendVerificationEmail(): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification code sent to your email']);
    }

    /**
     * Verify email address using verification code.
     *
     * @throws Exception
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        // Check if verification code has expired
        if ($user->email_verification_code_expires_at &&
            Carbon::parse((string)$user->email_verification_code_expires_at)->isPast()) {
            return response()->json(['error' => 'Verification code has expired'], 400);
        }

        // Verify the code
        if (!hash_equals((string)$user->remember_token, hash('sha256', $request->getVerificationCode()))) {
            return response()->json(['error' => 'Invalid verification code'], 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            // Clear the verification code and expiration
            $user->forceFill([
                'remember_token' => null,
                'email_verification_code_expires_at' => null
            ])->save();
        }

        return response()->json(['message' => 'Email has been verified']);
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     * @return JsonResponse
     * @throws JWTException
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::guard('api')->user();

        if (!$user) {
            throw new JWTException('User not found after token creation');
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at
            ]
        ]);
    }
}
