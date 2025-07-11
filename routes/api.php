<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwilioController;

Route::post('/sms/send', [TwilioController::class, 'sendSms'])->name('sms.send');