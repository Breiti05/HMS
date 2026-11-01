<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStock;
use App\Models\Article;
use App\Models\StorageLocation;
use App\Services\StockService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function store(Request $request, StockService $stock): RedirectResponse
    {
        $tenant = TenantContext::id();

        $data = $request->validate([
            'article_id' => ['required', Rule::exists('articles', 'id')->where('tenant_id', $tenant)->whereNull('deleted_at')],
            'storage_location_id' => ['required', Rule::exists('storage_locations', 'id')->where('tenant_id', $tenant)->whereNull('deleted_at')],
            'type' => ['required', Rule::in(['receipt', 'issue'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [], [
            'article_id' => 'Artikel',
            'storage_location_id' => 'Lagerplatz',
            'quantity' => 'Menge',
        ]);

        $article = Article::findOrFail($data['article_id']);
        $location = StorageLocation::findOrFail($data['storage_location_id']);

        try {
            $data['type'] === 'receipt'
                ? $stock->receive($article, $location, (float) $data['quantity'], $data['note'] ?? null)
                : $stock->issue($article, $location, (float) $data['quantity'], $data['note'] ?? null);
        } catch (InsufficientStock $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }

        return redirect('/lager')->with('status', 'Gebucht.');
    }
}
