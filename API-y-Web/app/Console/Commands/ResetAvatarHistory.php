<?php

namespace App\Console\Commands;

use App\Shared\Helpers\AvatarHelper;
use Illuminate\Console\Command;

class ResetAvatarHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'avatars:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets the usage history of avatars. Run this ONLY after migrate:fresh.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('⚠️  Start fresh? This will make the seeder reuse avatar images from #001 again. Use ONLY if you wiped the database.', true)) {
            AvatarHelper::reset();
            $this->info('✅ Avatar usage history has been reset. Next seed will start from image #001.');
        } else {
            $this->info('❌ Operation cancelled.');
        }
    }
}
