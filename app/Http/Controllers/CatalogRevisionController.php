<?php

namespace App\Http\Controllers;

use App\Services\CatalogRevision;

class CatalogRevisionController extends Controller
{
    public function __invoke()
    {
        return response()->json(['version' => CatalogRevision::current()])->header('Cache-Control', 'no-store');
    }
}
