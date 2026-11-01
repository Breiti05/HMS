<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Site;
use App\Models\StorageLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        return view('baustellen.index', [
            'baustellen' => Site::with('tasks', 'materials')->orderByRaw("status = 'active' desc")->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'number' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:200'],
        ], [], ['number' => 'Baustellennummer', 'name' => 'Bezeichnung']);

        if (Site::withTrashed()->where('number', $data['number'])->exists()) {
            return back()->withErrors(['number' => 'Diese Baustellennummer gibt es schon.'])->withInput();
        }

        $site = Site::create($data);

        return redirect()->route('sites.show', $site);
    }

    public function show(Site $site)
    {
        $site->load(['tasks' => fn ($q) => $q->orderBy('id'), 'materials.article', 'materials.location']);

        return view('baustellen.show', [
            'site' => $site,
            'artikel' => Article::orderBy('number')->get(),
            'plaetze' => StorageLocation::orderBy('code')->get(),
        ]);
    }
}
