<?php

namespace App\Console\Commands;

use App\Support\DemoImporter;
use Illuminate\Console\Command;

class DemoData extends Command
{
    protected $signature = 'lms:demo-data
        {--path= : Folder with the JSON files (default: database/demo)}
        {--only= : Comma separated sections to import, e.g. books,students,marks (see --list)}
        {--list : Show the available sections / files and exit}
        {--fresh : Delete existing library + school data first (users and set-up lists are kept)}
        {--force : Do not ask for confirmation (and allow running in production)}';

    protected $description = 'Load demo data (books, loans, students, teachers, marks, attendance, fees ...) from JSON files';

    public function handle(): int
    {
        $path = $this->option('path') ?: database_path('demo');

        if ($this->option('list')) {
            $this->table(['Section', 'File'], collect(DemoImporter::FILES)->map(fn ($file, $section) => [$section, $file])->values()->all());
            $this->line("Folder: {$path}");
            return self::SUCCESS;
        }

        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to load demo data in production. Use --force if you really want to.');
            return self::FAILURE;
        }
        if (! is_dir($path)) {
            $this->error("Folder not found: {$path}");
            return self::FAILURE;
        }

        $only = array_values(array_filter(array_map('trim', explode(',', (string) $this->option('only')))));
        if ($unknown = array_diff($only, array_keys(DemoImporter::FILES))) {
            $this->error('Unknown section(s): ' . implode(', ', $unknown) . '. Run with --list to see the valid names.');
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            if (! $this->option('force') && ! $this->confirm('This DELETES all books, loans, members, students, teachers, classes, marks, attendance and fees. Continue?')) {
                return self::FAILURE;
            }
            DemoImporter::wipe();
            $this->warn('Existing library + school data deleted.');
        }

        try {
            $stats = (new DemoImporter($path))->run($only, fn ($section) => $this->line("  → {$section}"));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $rows = [];
        foreach ($stats as $section => $counts) {
            $rows[] = [$section, collect($counts)->map(fn ($n, $k) => "{$n} {$k}")->implode(', ')];
        }
        $this->newLine();
        $this->table(['Section', 'Result'], $rows ?: [['—', 'nothing to import']]);
        $this->info('Demo data loaded. Demo logins use the password "password" (e.g. abel.student@demo.test, tigist.teacher@demo.test).');

        return self::SUCCESS;
    }
}
