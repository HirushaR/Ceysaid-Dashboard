<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateFilesToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'files:migrate-to-s3 {--dry-run : Show what would be migrated without actually moving files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing local files to S3';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN - No files will be moved');
        }

        $attachments = Attachment::all();
        $this->info("Found {$attachments->count()} attachments to process");

        $migrated = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($attachments as $attachment) {
            $localPath = $attachment->file_path;
            $fullLocalPath = storage_path("app/public/{$localPath}");

            // Check if file exists locally
            if (!file_exists($fullLocalPath)) {
                $this->warn("⚠️  Local file not found: {$localPath}");
                $errors++;
                continue;
            }

            // Prepare S3 path (remove lead-attachments prefix if exists)
            $s3Path = str_replace('lead-attachments/', '', $localPath);
            
            // Skip if file already exists in S3
            if (Storage::disk('lead-attachments')->exists($s3Path)) {
                if ($dryRun) {
                    $this->line("Would skip (already exists in S3): {$localPath}");
                } else {
                    $this->line("⏭️  Already exists in S3: {$s3Path}");
                    $skipped++;
                }
                continue;
            }

            if ($dryRun) {
                $this->line("Would migrate: {$localPath} -> {$s3Path}");
                continue;
            }

            try {
                // Upload to S3
                $fileContents = file_get_contents($fullLocalPath);
                Storage::disk('lead-attachments')->put($s3Path, $fileContents);
                
                // Update database record
                $attachment->update(['file_path' => $s3Path]);
                
                $this->info("✅ Migrated: {$localPath} -> {$s3Path}");
                $migrated++;
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to migrate {$localPath}: " . $e->getMessage());
                $errors++;
            }
        }

        if (!$dryRun) {
            $this->info("\n📊 Migration Summary:");
            $this->info("✅ Successfully migrated: {$migrated}");
            $this->info("⏭️  Skipped (already in S3): {$skipped}");
            $this->info("❌ Errors: {$errors}");
            
            if ($migrated > 0) {
                $this->info("\n💡 Don't forget to:");
                $this->info("1. Update your .env file with S3 credentials");
                $this->info("2. Test file access in the admin panel");
                $this->info("3. Remove local files after confirming S3 migration works");
            }
        } else {
            $this->info("\nRun without --dry-run to perform actual migration");
        }

        return 0;
    }
}
