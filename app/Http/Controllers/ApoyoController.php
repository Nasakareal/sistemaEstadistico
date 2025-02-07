<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApoyoController extends Controller
{
    public function index()
    {
        return view('apoyo.index');
    }
}
