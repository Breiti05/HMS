<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStock;
use App\Exceptions\InvalidScan;
use App\Services\SiteMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function store(Request $request, SiteMaterialService $service): JsonResponse
    {
        $data = $request->validate(['payload' => ['required', 'string', 'max:300']]);

        try {
            $material = $service->scan($data['payload']);
        } catch (InvalidScan|InsufficientStock $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Verladen.',
            'site' => $material->site->name,
            'site_number' => $material->site->number,
            'article' => $material->article->name,
            'quantity' => $material->quantityLabel(),
            'unit' => $material->article->unit,
        ]);
    }
}
