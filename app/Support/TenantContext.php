<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

/**
 * Sagt, für welchen Betrieb gerade gearbeitet wird: der angemeldete Benutzer,
 * oder (in Jobs, Befehlen, Seedern) der mit run() gesetzte Betrieb.
 * Ohne Kontext sieht und schreibt man nichts.
 */
class TenantContext
{
    private static ?int $forced = null;

    public static function id(): ?int
    {
        return self::$forced ?? Auth::user()?->tenant_id;
    }

    public static function run(int $tenantId, callable $callback): mixed
    {
        $previous = self::$forced;
        self::$forced = $tenantId;

        try {
            return $callback();
        } finally {
            self::$forced = $previous;
        }
    }
}
