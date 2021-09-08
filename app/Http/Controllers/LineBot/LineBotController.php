<?php

namespace App\Http\Controllers\LineBot;

use App\Http\Controllers\Controller;

class LineBotController extends Controller
{
    public function echo()
    {
        \Log::info(['data' => request()->all()]);

        return 'OK';
    }
}