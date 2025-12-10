<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadRealAvatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-avatars {count=50 : Number of avatars per gender to download} {--start-index=1 : The index to start naming files from (e.g. 101)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads high-quality random user avatars. Use --start-index to append instead of overwrite.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $startIndex = (int) $this->option('start-index');

        $this->info("🚀 Downloading {$count} avatars per gender (Starting at index #{$startIndex})...");

        // Only ensure directories, do NOT delete/clean if we are appending (index > 1)
        if ($startIndex === 1) {
             $this->ensureDirectoriesExist(true); // True = Clean first
        } else {
             $this->ensureDirectoriesExist(false);
        }

        $this->downloadBatch('female', $count, $startIndex);
        $this->downloadBatch('male', $count, $startIndex);

        $this->info("✅ Download complete!");
    }

    private function ensureDirectoriesExist($clean = false)
    {
        if ($clean) {
            Storage::disk('public')->deleteDirectory('avatars/pool');
        }
        
        Storage::disk('public')->makeDirectory('avatars/pool/men');
        Storage::disk('public')->makeDirectory('avatars/pool/women');
    }

    private function downloadBatch(string $gender, int $count, int $startIndex)
    {
        $this->info("⬇️ Downloading {$count} {$gender} avatars starting at #{$startIndex}...");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $response = Http::get("https://randomuser.me/api/", [
            'results' => $count,
            'gender' => $gender,
            'inc' => 'picture',
            'noinfo' => true
        ]);

        if ($response->failed()) {
            $this->error("❌ Failed to connect to randomuser.me API");
            return;
        }

        $results = $response->json()['results'];

        foreach ($results as $index => $user) {
            $imageUrl = $user['picture']['large'];
            $imageContent = file_get_contents($imageUrl);
            
            // Calculate actual file index: StartIndex + Current Loop Index
            // e.g., Start 101 + Index 0 = 101.jpg
            $fileIndex = $startIndex + $index;
            $filename = str_pad($fileIndex, 3, '0', STR_PAD_LEFT) . '.jpg';
            
            $folder = $gender === 'male' ? 'men' : 'women';
            
            Storage::disk('public')->put("avatars/pool/{$folder}/{$filename}", $imageContent);
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
