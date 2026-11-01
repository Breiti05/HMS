<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\KanbanCard;
use App\Models\Site;
use App\Models\StockMovement;
use App\Services\StockService;

class DashboardController extends Controller
{
    public function __invoke(StockService $stock)
    {
        return view('dashboard', [
            'artikel' => Article::count(),
            'knapp' => $stock->belowMinimum()->count(),
            'baustellen' => Site::where('status', 'active')->count(),
            'kanban' => KanbanCard::where('status', 'open')->count(),
            'aktiveBaustellen' => Site::where('status', 'active')->with('tasks')->latest()->limit(4)->get(),
            'bewegungen' => StockMovement::with(['article', 'location'])->latest('id')->limit(8)->get(),
        ]);
    }
}
