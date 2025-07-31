<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ArchiveLeadCostsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archive:lead-costs-table {--backup : Create backup before archiving} {--drop : Drop the table after backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archive the old lead_costs table after migration to invoice system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!Schema::hasTable('lead_costs')) {
            $this->error('❌ The lead_costs table does not exist.');
            return 1;
        }

        $recordCount = DB::table('lead_costs')->count();
        $this->info("Found {$recordCount} records in lead_costs table.");

        if ($recordCount === 0) {
            $this->info('✅ Table is empty, safe to drop.');
        } else {
            $this->warn("⚠️  Table contains {$recordCount} records.");
        }

        // Create backup if requested
        if ($this->option('backup')) {
            $this->info('📦 Creating backup...');
            
            $timestamp = now()->format('Y_m_d_H_i_s');
            $backupTableName = "lead_costs_backup_{$timestamp}";
            
            try {
                DB::statement("CREATE TABLE {$backupTableName} AS SELECT * FROM lead_costs");
                $this->info("✅ Backup created: {$backupTableName}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to create backup: " . $e->getMessage());
                return 1;
            }
        }

        // Drop table if requested
        if ($this->option('drop')) {
            if (!$this->option('backup')) {
                $this->warn('⚠️  You are about to drop the table without creating a backup!');
            }
            
            if ($this->confirm('Are you sure you want to drop the lead_costs table?')) {
                try {
                    Schema::drop('lead_costs');
                    $this->info('✅ lead_costs table has been dropped.');
                } catch (\Exception $e) {
                    $this->error("❌ Failed to drop table: " . $e->getMessage());
                    return 1;
                }
            } else {
                $this->info('❌ Operation cancelled.');
                return 0;
            }
        }

        if (!$this->option('backup') && !$this->option('drop')) {
            $this->info('💡 Usage examples:');
            $this->info('  --backup          Create a backup table');
            $this->info('  --backup --drop   Create backup and drop original');
            $this->info('  --drop            Drop table (careful!)');
        }

        return 0;
    }
}
