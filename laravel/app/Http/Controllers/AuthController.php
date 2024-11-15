<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Carbon;

class AuthController extends BaseController
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Register a new user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json($user);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = Auth::guard('api')->refresh();
            return $this->respondWithToken($token);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not refresh token'], 401);
        }
    }

    /**
     * Resend email verification notification.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerificationEmail(Request $request): JsonResponse
    {
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::guard('api')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified']);
        }

        // Check if verification code has expired
        if ($user->email_verification_code_expires_at && 
            Carbon::parse($user->email_verification_code_expires_at)->isPast()) {
            return response()->json(['error' => 'Verification code has expired'], 400);
        }

        // Verify the code
        if (!hash_equals($user->remember_token, hash('sha256', $request->code))) {
            return response()->json(['error' => 'Invalid verification code'], 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
            
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
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token): JsonResponse
    {
        $user = Auth::guard('api')->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
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
