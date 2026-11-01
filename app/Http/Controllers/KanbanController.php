<?php

namespace App\Http\Controllers;

use App\Models\KanbanCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KanbanController extends Controller
{
    public function index()
    {
        $karten = KanbanCard::with('article')->latest('id')->get()->groupBy('status');

        return view('kanban.index', ['karten' => $karten]);
    }

    public function status(Request $request, KanbanCard $card): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(KanbanCard::STATUSES)]]);

        $card->update($data);

        return redirect('/kanban');
    }
}
