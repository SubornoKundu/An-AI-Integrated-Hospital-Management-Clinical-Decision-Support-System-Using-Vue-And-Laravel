<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use App\Support\AvatarGenerator;
use Illuminate\Console\Command;

class GenerateDoctorAvatars extends Command
{
    /**
     * php artisan doctors:generate-avatars        -> only fills doctors with no photo
     * php artisan doctors:generate-avatars --force -> regenerates for every doctor,
     *                                                  overwriting any existing photo
     */
    protected $signature = 'doctors:generate-avatars {--force : Regenerate even for doctors who already have a photo}';

    protected $description = 'Generate a placeholder colored-initials avatar for every doctor without a profile photo';

    public function handle(): int
    {
        $query = Doctor::query();

        if (! $this->option('force')) {
            $query->whereNull('photo');
        }

        $doctors = $query->get();

        if ($doctors->isEmpty()) {
            $this->info('Every doctor already has a photo — nothing to do. Use --force to regenerate anyway.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($doctors->count());
        $bar->start();

        foreach ($doctors as $doctor) {
            $path = AvatarGenerator::saveFor($doctor->name, 'doctors', $doctor->id, $doctor->gender);
            $doctor->update(['photo' => $path]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Generated avatars for {$doctors->count()} doctor(s).");

        return self::SUCCESS;
    }
}
