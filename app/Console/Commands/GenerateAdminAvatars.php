<?php

namespace App\Console\Commands;

use App\Models\StaffAdmin;
use App\Support\AvatarGenerator;
use Illuminate\Console\Command;

class GenerateAdminAvatars extends Command
{
    protected $signature = 'admins:generate-avatars {--force : Regenerate even for admins who already have a photo}';

    protected $description = 'Generate a gender-styled placeholder avatar for every admin account without a profile photo';

    public function handle(): int
    {
        $query = StaffAdmin::where('role', 'admin');

        if (! $this->option('force')) {
            $query->whereNull('photo');
        }

        $admins = $query->get();

        if ($admins->isEmpty()) {
            $this->info('Every admin already has a photo — nothing to do. Use --force to regenerate anyway.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($admins->count());
        $bar->start();

        foreach ($admins as $admin) {
            $path = AvatarGenerator::saveFor($admin->name, 'admins', $admin->id, $admin->gender);
            $admin->update(['photo' => $path]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Generated avatars for {$admins->count()} admin account(s).");

        return self::SUCCESS;
    }
}
