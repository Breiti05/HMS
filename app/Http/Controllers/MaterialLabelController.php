<?php

namespace App\Http\Controllers;

use App\Models\SiteMaterial;
use App\Services\QrCode;

class MaterialLabelController extends Controller
{
    public function show(SiteMaterial $material)
    {
        $material->load(['site', 'article']);

        return view('material.label', [
            'material' => $material,
            'qr' => QrCode::svg($material->qrPayload(), 260),
        ]);
    }
}
