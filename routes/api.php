<?php

use App\Http\Controllers\Api\ScalevWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/scalev', ScalevWebhookController::class);
