<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /** QR-Code als SVG (ohne XML-Kopf, direkt in HTML einbettbar). */
    public static function svg(string $content, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));
        $svg = $writer->writeString($content);

        return trim(preg_replace('/^<\?xml[^>]*\?>\s*/', '', $svg));
    }
}
