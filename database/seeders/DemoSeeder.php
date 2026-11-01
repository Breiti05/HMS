<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Site;
use App\Models\StorageLocation;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SiteMaterialService;
use App\Services\StockService;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;

/** Demodaten für die Entwicklung: Betrieb "Müller Haustechnik", Login demo@hms.test / demo-passwort. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create(['name' => 'Müller Haustechnik']);
        $user = User::create([
            'tenant_id' => $tenant->id, 'name' => 'Hans Müller', 'email' => 'demo@hms.test',
            'password' => 'demo-passwort', 'role' => 'owner',
        ]);

        TenantContext::run($tenant->id, function () use ($user) {
            auth()->setUser($user);
            $stock = app(StockService::class);

            $haupt = Warehouse::create(['name' => 'Hauptlager']);
            $van = Warehouse::create(['name' => 'Transporter 1', 'type' => 'vehicle']);

            $plaetze = [];
            foreach (['A', 'B', 'C'] as $r => $reihe) {
                foreach (range(1, 4) as $c) {
                    $plaetze["$reihe-0$c"] = StorageLocation::create([
                        'warehouse_id' => $haupt->id, 'code' => "$reihe-0$c", 'row' => $r + 1, 'col' => $c,
                    ]);
                }
            }
            StorageLocation::create(['warehouse_id' => $van->id, 'code' => 'T1-01', 'row' => 1, 'col' => 1]);
            StorageLocation::create(['warehouse_id' => $van->id, 'code' => 'T1-02', 'row' => 1, 'col' => 2]);

            $artikel = [
                ['R-100', 'Kupferrohr 15 mm', 'm', 40, 'A-01', 120],
                ['R-110', 'Kupferrohr 22 mm', 'm', 30, 'A-02', 18],
                ['F-200', 'Pressfitting 15 mm', 'Stk', 100, 'A-03', 450],
                ['H-300', 'Heizkörper Typ 22, 600×1000', 'Stk', 4, 'B-01', 9],
                ['H-310', 'Thermostatkopf', 'Stk', 10, 'B-02', 6],
                ['K-400', 'Kabel NYM-J 3×1,5', 'm', 200, 'C-01', 800],
                ['S-500', 'Schalter, Unterputz', 'Stk', 25, 'C-02', 60],
                ['D-600', 'Dichtband 12 m', 'Stk', 12, 'B-03', 40],
            ];
            $art = [];
            foreach ($artikel as [$nr, $name, $einheit, $min, $platz, $menge]) {
                $art[$nr] = Article::create(['number' => $nr, 'name' => $name, 'unit' => $einheit, 'min_stock' => $min]);
                $stock->receive($art[$nr], $plaetze[$platz], $menge, 'Anfangsbestand');
            }
            $stock->issue($art['H-310'], $plaetze['B-02'], 1, 'Entnahme'); // 5 < 10 -> Kanban-Karte

            $schmidt = Site::create(['number' => 'B-2026-014', 'name' => 'Neubau Familie Schmidt', 'address' => 'Dorfstraße 3, 48366 Laer']);
            foreach (['Rohbauinstallation Heizung', 'Heizkörper montieren', 'Elektro-Unterverteilung', 'Druckprobe', 'Abnahme'] as $i => $titel) {
                $t = $schmidt->tasks()->create(['title' => $titel]);
                if ($i < 2) {
                    $t->complete($user);
                }
            }
            $baeckerei = Site::create(['number' => 'B-2026-019', 'name' => 'Sanierung Bäckerei Hoffmann', 'address' => 'Marktplatz 1']);
            foreach (['Demontage Altanlage', 'Neue Leitungen verlegen', 'Inbetriebnahme'] as $titel) {
                $baeckerei->tasks()->create(['title' => $titel]);
            }

            $material = app(SiteMaterialService::class);
            $verladen = $material->assign($schmidt, $art['R-100'], $plaetze['A-01'], 35);
            $material->scan($verladen->qrPayload());
            $material->assign($schmidt, $art['H-300'], $plaetze['B-01'], 3);
            $material->assign($baeckerei, $art['K-400'], $plaetze['C-01'], 120);
        });
    }
}
