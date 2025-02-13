<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactoController extends Controller
{
    public function index()
    {
        return view('contacto.index');
    }

    public function enviarMensaje(Request $request)
    {
        $validated = $request->validate([
            'nombre'  => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'mensaje' => 'required|string|max:1000',
        ]);

        return back()->with('success', 'Tu mensaje ha sido enviado correctamente.');
    }
}
