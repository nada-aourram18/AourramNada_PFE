<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class AirtableDiagnoseCommand extends Command
{
    protected $signature = 'airtable:diagnose';

    protected $description = 'Teste la connexion Airtable et affiche la réponse complète (pour comprendre un 403).';

    public function handle(): int
    {
        $key = (string) config('services.airtable.api_key');
        $base = (string) config('services.airtable.base_id');
        $verify = (bool) config('services.airtable.http_verify', true);

        if ($key === '' || $base === '') {
            $this->error('AIRTABLE_API_KEY et AIRTABLE_BASE_ID sont requis dans .env');

            return self::FAILURE;
        }

        $prefix = strlen($key) > 12 ? substr($key, 0, 8).'…'.substr($key, -4) : '***';
        $this->info('Jeton (aperçu) : '.$prefix);
        $this->info('Base ID      : '.$base);
        $this->info('Verify SSL   : '.($verify ? 'oui' : 'non'));
        $this->newLine();

        $opts = [];
        if (! $verify) {
            $opts['verify'] = false;
        }

        $http = Http::withToken($key)
            ->withOptions($opts)
            ->connectTimeout(5)
            ->timeout(12);

        // 1) Test “meta” : liste des tables (nécessite souvent le scope schema.bases:read)
        $metaUrl = 'https://api.airtable.com/v0/meta/bases/'.$base.'/tables';
        $meta = $http->get($metaUrl);
        $this->comment('→ GET meta/bases/…/tables (noms officiels des tables)');
        $this->line('   HTTP '.$meta->status());
        if ($meta->failed()) {
            $this->warn('   Corps : '.$meta->body());
            $this->newLine();
            $this->comment('   Si 403 ici : le jeton n’a peut‑être pas le scope « schema.bases:read » — ce n’est pas grave pour la suite.');
        } else {
            $tables = $meta->json('tables') ?? [];
            $this->info('   Tables vues par l’API meta ('.count($tables).') :');
            foreach ($tables as $t) {
                $name = $t['name'] ?? '?';
                $id = $t['id'] ?? '';
                $this->line('   - '.$name.' (id: '.$id.')');
            }
            $usersName = (string) config('services.airtable.tables.users', 'Doctors');
            foreach ($tables as $t) {
                if (($t['name'] ?? '') !== $usersName) {
                    continue;
                }
                $this->newLine();
                $this->comment('   Champs de la table « '.$usersName.' » (connexion / inscription) :');
                foreach ($t['fields'] ?? [] as $f) {
                    $fn = $f['name'] ?? '?';
                    $ft = $f['type'] ?? '?';
                    $this->line('      - '.$fn.' ('.$ft.')');
                }
                $pwd = (string) config('services.airtable.user_columns.password', 'password');
                if (! collect($t['fields'] ?? [])->contains(fn ($f) => ($f['name'] ?? '') === $pwd)) {
                    $this->warn('      ⚠ Colonne « '.$pwd.' » absente — ajoutez-la (texte long) pour login/inscription.');
                }
            }

            $appointmentsName = (string) config('services.airtable.tables.appointments', 'Appointments');
            foreach ($tables as $t) {
                if (($t['name'] ?? '') !== $appointmentsName) {
                    continue;
                }
                $this->newLine();
                $this->comment('   Champs de la table « '.$appointmentsName.' » (pour AIRTABLE_FIELD_APPOINTMENT_PATIENT) :');
                foreach ($t['fields'] ?? [] as $f) {
                    $fn = $f['name'] ?? '?';
                    $ft = $f['type'] ?? '?';
                    $link = $f['options']['linkedTableId'] ?? $f['options']['LinkedTableId'] ?? null;
                    $extra = $link ? ' → lié à table id '.$link : '';

                    $this->line('      - '.$fn.' ('.$ft.')'.$extra);
                    if (in_array($ft, ['singleSelect', 'multipleSelects'], true)) {
                        $names = [];
                        foreach ($f['options']['choices'] ?? [] as $ch) {
                            $names[] = (string) ($ch['name'] ?? '');
                        }
                        $names = array_values(array_filter($names));
                        if ($names !== []) {
                            $this->line('        options : '.implode(' | ', $names));
                        }
                    }
                }
            }
        }

        $this->newLine();

        // 2) Test lecture sur chaque table configurée dans .env
        $tablesConfig = config('services.airtable.tables', []);

        foreach ($tablesConfig as $logical => $name) {
            if ($name === null || $name === '') {
                $this->comment('→ Table « '.$logical.' » : (vide, ignorée)');

                continue;
            }

            $url = 'https://api.airtable.com/v0/'.$base.'/'.rawurlencode($name).'?maxRecords=1';
            $r = $http->get($url);

            $this->comment('→ GET records table « '.$name.' » (clé config: '.$logical.')');
            $this->line('   HTTP '.$r->status());

            if ($r->failed()) {
                $this->error('   Corps complet :');
                $this->line('   '.$r->body());
            } else {
                $this->info('   OK — lecture autorisée.');
            }
            $this->newLine();
        }

        $this->info('Interprétation rapide :');
        $this->line('- 401 / INVALID_PERMISSIONS → jeton faux, expiré, ou révoqué.');
        $this->line('- 403 + « model was not found » → Base ID faux OU nom de table faux (respecter majuscules / API doc).');
        $this->line('- 403 sur meta seulement → ajoute le scope « schema.bases:read » au jeton (optionnel).');
        $this->line('- 200 sur une ligne → cette table est bonne ; corrige les autres noms dans .env.');

        return self::SUCCESS;
    }
}
