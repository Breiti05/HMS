<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\StockService;

class WarehouseController extends Controller
{
    public function index(StockService $stock)
    {
        $knappeArtikel = $stock->belowMinimum()->pluck('id')->all();

        $lager = Warehouse::with(['locations' => fn ($q) => $q->orderBy('row')->orderBy('col'), 'locations.levels.article'])
            ->orderBy('name')
            ->get();

        return view('lager.index', [
            'lager' => $lager,
            'knappeArtikel' => $knappeArtikel,
            'artikel' => Article::orderBy('number')->get(),
            'plaetze' => StorageLocation::with('warehouse')->orderBy('code')->get(),
        ]);
    }
}
