<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class AuthController extends BaseController
{
    /**
     * Create a new AuthController instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
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

            if ($token === null) {
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
