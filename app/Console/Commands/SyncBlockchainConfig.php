<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SyncBlockchainConfig extends Command
{
    protected $signature = 'autochain:sync-blockchain {--enable : Activer BLOCKCHAIN_ENABLED dans .env}';

    protected $description = 'Synchronise l\'adresse du contrat et l\'ABI depuis storage/blockchain/AutoChainRegistry.json';

    public function handle(): int
    {
        $path = storage_path('blockchain/AutoChainRegistry.json');

        if (! file_exists($path)) {
            $this->error('Fichier introuvable : storage/blockchain/AutoChainRegistry.json');
            $this->line('Exécutez : cd blockchain && npm run deploy:local');

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);
        $address = $data['contractAddress'] ?? null;
        $chainId = $data['chainId'] ?? '31337';

        if (! $address) {
            $this->error('contractAddress manquant dans le JSON de déploiement.');

            return self::FAILURE;
        }

        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            $this->error('.env introuvable. Copiez .env.example vers .env');

            return self::FAILURE;
        }

        $env = file_get_contents($envPath);
        $updates = [
            'BLOCKCHAIN_CONTRACT_ADDRESS' => $address,
            'BLOCKCHAIN_CHAIN_ID' => $chainId,
            'BLOCKCHAIN_RPC_URL' => 'http://127.0.0.1:8545',
        ];

        if ($this->option('enable')) {
            $updates['BLOCKCHAIN_ENABLED'] = 'true';
        }

        foreach ($updates as $key => $value) {
            $env = $this->setEnvValue($env, $key, $value);
        }

        file_put_contents($envPath, $env);
        Artisan::call('config:clear');

        $this->info('Configuration blockchain synchronisée.');
        $this->table(['Clé', 'Valeur'], collect($updates)->map(fn ($v, $k) => [$k, $v])->values()->all());
        $this->line("Contrat : {$address}");
        $this->line('ABI : storage/blockchain/AutoChainRegistry.json');

        if (! $this->option('enable')) {
            $this->warn('BLOCKCHAIN_ENABLED non modifié. Ajoutez --enable pour activer.');
        }

        return self::SUCCESS;
    }

    private function setEnvValue(string $env, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*/m";
        $line = "{$key}={$value}";

        return preg_match($pattern, $env)
            ? preg_replace($pattern, $line, $env)
            : $env.PHP_EOL.$line;
    }
}
