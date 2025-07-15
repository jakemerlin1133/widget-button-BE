<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwilioController;

Route::post('/send-otp', [TwilioController::class, 'sendOtp']);
