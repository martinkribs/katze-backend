<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Check if the user's email is already verified
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['status' => 'already-verified'], 409);
        }

        // Mark the email as verified
        if ($request->user()->markEmailAsVerified()) {
            // Fire the Verified event
            event(new Verified($request->user()));
        }

        // Respond with a success message
        return response()->json(['status' => 'email-verified']);
    }
}
