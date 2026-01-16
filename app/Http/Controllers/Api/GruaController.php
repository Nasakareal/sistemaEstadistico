<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grua;
use Illuminate\Http\Request;

class GruaController extends Controller
{
    public function index(Request $request)
    {
        $gruas = Grua::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $gruas
        ]);
    }
}
