<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;

class TermsController extends Controller
{
    public function index()
    {
        return view('user.precautions', [
            'showAgree' => false,  // 利用規約は同意UIなし
            'next'      => null,
            'startStep' => 1,
        ]);
    }
}
