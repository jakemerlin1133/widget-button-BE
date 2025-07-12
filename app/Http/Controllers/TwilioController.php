<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TwilioController extends Controller
{
    public function sendSms(Request $request)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $from = env('TWILIO_PHONE_NUMBER');

        // 🟢 Pull data from request
        $carTitle = $request->input('carTitle');
        $carPrice = $request->input('price');
        $firstName = $request->input('firstName');
        $lastName = $request->input('lastName');
        $contactMode = $request->input('contactMode');
        $phone = $request->input('phone');
        $comment = $request->input('comment');

        // 🟢 Format the SMS message
        $body = "Hello {$firstName} {$lastName},\n".
            "Thank you for your interest in the {$carTitle}.\n".
            "The current price of this vehicle is \${$carPrice}.\n".
            "We will contact you shortly via your preferred method ({$contactMode}).";
        // 🟢 Use `phone` from the user as the recipient (or use fixed if needed)
        $to = $phone;

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
            return response()->json(['error' => curl_error($ch)], 500);
        }

         curl_close($ch);

        return response()->json([
        'status' => $httpCode == 201 ? 'Message sent!' : 'Failed',
        'twilio_response' => json_decode($response, true)
        ], $httpCode);

    }
}
