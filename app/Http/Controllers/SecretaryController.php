<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class SecretaryController extends Controller
{
    public function index()
    {
        return view('secretary.dashboard');
    }
}
