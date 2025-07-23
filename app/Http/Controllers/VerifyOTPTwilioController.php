<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class VerifyOTPTwilioController extends Controller
{
    public function verifyOtp(Request $request)
    {
        try {
            $rawPhone = $request->input('phone');
            $enteredOtp = $request->input('otp');
            $clientIp = $request->ip();

            // Sanitize phone number
            $digitsOnly = preg_replace('/\D+/', '', $rawPhone);

            if (strlen($digitsOnly) === 10) {
                $to = '+1' . $digitsOnly;
            } elseif (strlen($digitsOnly) === 11 && str_starts_with($digitsOnly, '1')) {
                $to = '+' . $digitsOnly;
            } else {
                return response()->json(['error' => 'Invalid phone number.'], 422);
            }

            $otpRecord = OtpVerification::where('phone', $to)->first();

            if (!$otpRecord) {
                return response()->json(['error' => 'OTP not found.'], 404);
            }

            // Ensure expires_at is cast properly
            if (!($otpRecord->expires_at instanceof \Carbon\Carbon)) {
                $otpRecord->expires_at = \Carbon\Carbon::parse($otpRecord->expires_at);
            }

            if ($otpRecord->expires_at->isPast()) {
                return response()->json(['error' => 'OTP has expired.'], 410);
            }

            // Log only in non-production environments
            if (!App::environment('production')) {
                Log::info('OTP verification attempt', [
                    'stored_ip' => $otpRecord->ip_address,
                    'request_ip' => $clientIp,
                    'stored_otp' => $otpRecord->otp,
                    'entered_otp' => $enteredOtp,
                    'attempts' => $otpRecord->attempts,
                ]);
            }

            // Limit to 5 attempts
            if ($otpRecord->attempts >= 5) {
                $otpRecord->delete(); // Clean up OTP after too many failures
                return response()->json([
                    'error' => 'Too many failed attempts. The OTP has been invalidated. Please request a new one.'
                ], 429);
            }

            // Check OTP
            if ($otpRecord->otp !== $enteredOtp) {
                $otpRecord->increment('attempts');
                return response()->json(['error' => 'Incorrect OTP.'], 401);
            }

            // Check IP
            if ($otpRecord->ip_address !== $clientIp) {
                $otpRecord->increment('attempts');
                return response()->json(['error' => 'IP address mismatch.'], 403);
            }

            // OTP verified
            $otpRecord->delete(); // Clean up after success

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully!'
            ], 200);

        } catch (\Throwable $e) {
            Log::error('OTP verification error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
