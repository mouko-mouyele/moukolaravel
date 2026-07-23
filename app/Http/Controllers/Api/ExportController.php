<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use Illuminate\Http\Response;

class ExportController extends Controller
{
    public function __construct(private ExportService $exports)
    {
    }

    public function vehiclesCsv()
    {
        return $this->exports->vehiclesCsv();
    }

    public function alertsCsv()
    {
        return $this->exports->alertsCsv();
    }

    public function maintenancesCsv()
    {
        return $this->exports->maintenancesCsv();
    }

    public function fleetReportPdf(): Response
    {
        $html = $this->exports->fleetReportHtml();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'inline; filename="rapport-flotte-autochain.html"',
        ]);
    }
}
