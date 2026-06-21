<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UnavailableController extends Controller
{
    // unavailable.blade.phpを表示する
    public function showUnavailable(){
        return view('user.unavailable');
    }
}
