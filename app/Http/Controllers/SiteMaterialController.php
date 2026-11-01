<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Site;
use App\Models\StorageLocation;
use App\Services\SiteMaterialService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteMaterialController extends Controller
{
    public function store(Request $request, Site $site, SiteMaterialService $service): RedirectResponse
    {
        $tenant = TenantContext::id();

        $data = $request->validate([
            'article_id' => ['required', Rule::exists('articles', 'id')->where('tenant_id', $tenant)->whereNull('deleted_at')],
            'storage_location_id' => ['required', Rule::exists('storage_locations', 'id')->where('tenant_id', $tenant)->whereNull('deleted_at')],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ], [], ['article_id' => 'Artikel', 'storage_location_id' => 'Lagerplatz', 'quantity' => 'Menge']);

        $service->assign(
            $site,
            Article::findOrFail($data['article_id']),
            StorageLocation::findOrFail($data['storage_location_id']),
            (float) $data['quantity'],
        );

        return redirect()->route('sites.show', $site);
    }
}
