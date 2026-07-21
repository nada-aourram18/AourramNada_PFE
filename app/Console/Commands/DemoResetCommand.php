<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    protected $signature = 'demo:reset {--force : Confirmer sans invite}';

    protected $description = 'Les données sont stockées dans Airtable : utilisez l’interface Airtable pour vider ou réimporter des enregistrements.';

    public function handle(): int
    {
        $this->warn('Les patients, rendez-vous et conversations ne sont plus dans une base SQL locale.');
        $this->line('Pour réinitialiser des données de démo, supprimez ou modifiez les enregistrements directement dans votre base Airtable.');

        return self::SUCCESS;
    }
}
