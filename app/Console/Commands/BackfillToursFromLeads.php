<?php

namespace App\Console\Commands;

use App\Enums\TourStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Tour;
use App\Services\TourCodeGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillToursFromLeads extends Command
{
    protected $signature = 'tours:backfill-from-leads {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Create Tour Master records from existing group leads and link leads/invoices';

    public function handle(TourCodeGenerator $codeGenerator): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no database changes will be made.');
        }

        $leads = Lead::query()
            ->where('is_group_lead', true)
            ->whereNull('tour_id')
            ->whereNotNull('depature_date')
            ->orderBy('depature_date')
            ->get();

        if ($leads->isEmpty()) {
            $this->info('No group leads without tour_id found.');

            return self::SUCCESS;
        }

        $groups = $leads->groupBy(function (Lead $lead) {
            $tourName = Str::lower(trim((string) ($lead->tour ?: $lead->destination ?: 'tour')));

            return $tourName.'|'.$lead->depature_date?->toDateString();
        });

        $created = 0;
        $linked = 0;

        foreach ($groups as $key => $groupLeads) {
            /** @var Lead $sample */
            $sample = $groupLeads->first();
            $name = trim((string) ($sample->tour ?: $sample->destination ?: 'Group tour'));
            $departure = $sample->depature_date;

            $tourCode = $codeGenerator->generate($sample->destination, $departure, $name);

            $this->line("Group: {$name} ({$departure?->toDateString()}) → {$tourCode} — {$groupLeads->count()} lead(s)");

            if ($dryRun) {
                $created++;
                $linked += $groupLeads->count();

                continue;
            }

            $tour = Tour::query()->firstOrCreate(
                ['tour_code' => $tourCode],
                [
                    'name' => $name,
                    'departure_date' => $departure,
                    'package_price' => 0,
                    'currency' => 'LKR',
                    'status' => TourStatus::Open,
                ]
            );

            if ($tour->wasRecentlyCreated) {
                $created++;
            }

            foreach ($groupLeads as $lead) {
                $lead->update(['tour_id' => $tour->id]);
                Invoice::query()->where('lead_id', $lead->id)->update(['tour_id' => $tour->id]);
                $linked++;
            }
        }

        $this->info("Done. Tours created: {$created}. Leads linked: {$linked}.");

        return self::SUCCESS;
    }
}
