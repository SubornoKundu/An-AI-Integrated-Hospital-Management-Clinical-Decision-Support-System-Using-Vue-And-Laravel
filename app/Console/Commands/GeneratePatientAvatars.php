<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Support\AvatarGenerator;
use Illuminate\Console\Command;

class GeneratePatientAvatars extends Command
{
    protected $signature = 'patients:generate-avatars {--force : Regenerate even for patients who already have a photo}';

    protected $description = 'Generate a gender-styled placeholder avatar for every patient without a profile photo';

    public function handle(): int
    {
        $query = Patient::query();

        if (! $this->option('force')) {
            $query->whereNull('photo');
        }

        $patients = $query->get();

        if ($patients->isEmpty()) {
            $this->info('Every patient already has a photo — nothing to do. Use --force to regenerate anyway.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($patients->count());
        $bar->start();

        foreach ($patients as $patient) {
            $path = AvatarGenerator::saveFor($patient->full_name, 'patients', $patient->id, $patient->gender);
            $patient->update(['photo' => $path]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Generated avatars for {$patients->count()} patient(s).");

        return self::SUCCESS;
    }
}
