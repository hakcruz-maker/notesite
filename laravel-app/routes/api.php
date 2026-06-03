<?php

use App\Http\Controllers\ApiAppController;
use Illuminate\Support\Facades\Route;

Route::any('/{action}', [ApiAppController::class, 'handle'])
    ->where('action', '[A-Za-z_]+');
