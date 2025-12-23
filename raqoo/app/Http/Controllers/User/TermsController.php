<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class TermsController extends Controller
{
    public function index()
    {
        return view('user.precautions', [
            'showAgree' => false,
            'next' => null,
            'startStep' => 1,
        ]);
    }
}
