<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CampanaController extends Controller
{
    public function index()
    {
        return view('campana.index');
    }
}
