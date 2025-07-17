<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OtpVerification;
use Illuminate\Support\Facades\Http;

class TwilioController extends Controller
{
    public function sendOtp(Request $request)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_PHONE_NUMBER');

        $rawPhone = $request->input('phone');

        // Sanitize and format to E.164 (US numbers)
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

        // Get client info
        $clientIp = $request->ip();

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        $message = "Your verification code is: {$otp}";

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $data = [
            'To' => $to,
            'From' => $from,
            'Body' => $message,
        ];

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, $data);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Failed to send OTP',
                'twilio_response' => $response->json()
            ], $response->status());
        }

        // Save OTP, IP, and expiry in DB
        OtpVerification::updateOrCreate(
            ['phone' => $to],
            [
                'otp' => $otp,
                'ip_address' => $clientIp,
                'expires_at' => now()->addMinutes(5),
                'attempts' => 0,
            ]
        );

        return response()->json([
            'status' => 'OTP sent successfully!',
            'to' => $to,
            'twilio_response' => $response->json(),
        ], 201);
    }
}
