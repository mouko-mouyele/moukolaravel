<?php

namespace App\Console\Commands;

use App\Services\AlertEngineService;
use Illuminate\Console\Command;

class GenerateAlertsCommand extends Command
{
    protected $signature = 'autochain:generate-alerts';

    protected $description = 'Génère les alertes automatiques pour la flotte (entretiens, assurances, CT)';

    public function handle(AlertEngineService $alertEngine): int
    {
        $created = $alertEngine->generateForFleet()->count();

        $this->info("{$created} nouvelle(s) alerte(s) générée(s).");

        return self::SUCCESS;
    }
}
