<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SiteTaskController extends Controller
{
    public function store(Request $request, Site $site): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:200']], [], ['title' => 'Aufgabe']);

        $site->tasks()->create($data);

        return redirect()->route('sites.show', $site);
    }

    public function toggle(Request $request, SiteTask $task): RedirectResponse
    {
        $task->done_at ? $task->reopen() : $task->complete($request->user());

        return redirect()->route('sites.show', $task->site_id);
    }
}
