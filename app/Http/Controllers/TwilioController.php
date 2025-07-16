<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TwilioController extends Controller
{
    public function sendOtp(Request $request)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_PHONE_NUMBER');

        $rawPhone = $request->input('phone');

        // Sanitize and format phone number to E.164 (assume US)
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

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Message body with OTP
        $body = "Your verification code is: {$otp}";

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $data = http_build_query([
            'To' => $to,
            'From' => $from,
            'Body' => $body,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            curl_close($ch);
            return response()->json(['error' => curl_error($ch)], 500);
        }

        curl_close($ch);

        // Cache the OTP for 5 minutes if message sent successfully
        if ($httpCode == 201) {
            Cache::put('otp_' . $to, $otp, now()->addMinutes(5));
        }

        return response()->json([
            'status' => $httpCode == 201 ? 'OTP sent!' : 'Failed',
            'to' => $to,
            // 'otp' => $otp, // Remove in production for security
            'twilio_response' => json_decode($response, true)
        ], $httpCode);
    }
}
