<?php

namespace App\Support;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\File;

class PdfRenderer
{
    /**
     * Sous IIS, le dossier temporaire système n'est pas accessible en écriture au
     * pool d'applications : Dompdf échoue alors à préparer les polices
     * (« Path must not be empty »). On lui donne un dossier dans storage.
     */
    public static function render(string $html, string $orientation = 'portrait'): string
    {
        $tempDir = storage_path('app/dompdf');
        File::ensureDirectoryExists($tempDir);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->setTempDir($tempDir);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', $orientation);
        $pdf->render();

        return $pdf->output();
    }
}
