<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the user's email address as verified.
     */
    public function verify(Request $request, $id): JsonResponse|RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Invalid verification link'
                ], 403);
            }
            return redirect()->route('login')->with('error', 'Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Email already verified'
                ], 200);
            }
            return redirect()->route('dashboard')->with('status', 'Email already verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Email has been verified'
            ], 200);
        }

        return redirect()->route('login')->with('status', 'Email has been verified');
    }
}
