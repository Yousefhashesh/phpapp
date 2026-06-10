<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('application');
    }
}
