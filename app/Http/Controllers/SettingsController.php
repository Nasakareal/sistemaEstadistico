<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Muestra el listado de configuraciones.
     */
    public function index()
    {
        $settings = [];
        return view('admin.settings.index', compact('settings'));
    }
}
