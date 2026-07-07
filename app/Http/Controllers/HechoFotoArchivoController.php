<?php

namespace App\Http\Controllers;

use App\Services\Fotos\HechoFotoStorage;

class HechoFotoArchivoController extends Controller
{
    public function showSigned(string $path, HechoFotoStorage $storage)
    {
        return $storage->response($path);
    }
}
