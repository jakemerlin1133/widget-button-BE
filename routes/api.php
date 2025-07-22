<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\VerifyOTPTwilioController;

Route::post('/send-otp', [TwilioController::class, 'sendOtp']);

Route::post('/verify-otp', [VerifyOTPTwilioController::class, 'verifyOtp']);
