<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VerifyOTPTwilioController extends Controller
{
    public function verifyOtp(Request $request)
    {
        $rawPhone = $request->input('phone');
        $userOtp = $request->input('otp');

        $digitsOnly = preg_replace('/\D+/', '', $rawPhone);

        if (strlen($digitsOnly) === 10) {
            $to = '+1' . $digitsOnly;
        } elseif (strlen($digitsOnly) === 11 && str_starts_with($digitsOnly, '1')) {
            $to = '+' . $digitsOnly;
        } else {
            return response()->json([
                'error' => 'Invalid phone number. Must be a valid US number.'
            ], 422);
        }

        $cachedOtp = Cache::get('otp_' . $to);

        if (!$cachedOtp) {
            return response()->json(['error' => 'OTP expired or not found.'], 404);
        }

        if ($userOtp == $cachedOtp) {
            Cache::forget('otp_' . $to);
            return response()->json(['status' => 'OTP verified successfully!']);
        } else {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }
    }
}
