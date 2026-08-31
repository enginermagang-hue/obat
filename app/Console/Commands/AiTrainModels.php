<?php

namespace App\Console\Commands;

use App\Models\FasilitasKesehatan;
use App\Models\ModelPrediksi;
use App\Models\Obat;
use App\Services\PredictionService;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AiTrainModels extends Command
{
    protected $signature = 'ai:train-models
        {--fasilitas-id= : Only train for a specific facility ID}
        {--obat-id= : Only train for a specific drug ID}
        {--force : Retrain even if model is already active}';

    protected $description = 'Train AI prediction models for all facility+drug combinations';

    /**
     * How many facility+drug combinations we process at once via lazy chunking.
     */
    private const CHUNK_SIZE = 50;

    /**
     * Maximum combinations to process in one run (selaras docs: 500).
     */
    private const MAX_COMBINATIONS = 500;

    public function handle(PredictionService $predictionService): int
    {
        $this->info('Starting AI model training...');
        $startTime = microtime(true);

        $processed = 0;
        $trained = 0;
        $predictionsGenerated = 0;
        $errors = 0;

        $query = $this->buildCombinationsQuery();
        $totalCombinations = (clone $query)->count();

        if ($totalCombinations === 0) {
            $this->warn('No facility+drug combinations with usage data found.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalCombinations} combinations to process.");
        $progressBar = $this->output->createProgressBar(min($totalCombinations, self::MAX_COMBINATIONS));
        $progressBar->start();

        $combinations = $query->orderBy('p.fasilitas_id')->orderBy('d.obat_id')->lazy(self::CHUNK_SIZE);

        foreach ($combinations as $combo) {
            if ($processed >= self::MAX_COMBINATIONS) {
                $this->newLine();
                $this->warn('Reached max combinations limit ('.self::MAX_COMBINATIONS.').');

                break;
            }

            try {
                $faskes = FasilitasKesehatan::find($combo->fasilitas_id);
                $obat = Obat::find($combo->obat_id);

                if ($faskes === null || $obat === null) {
                    $errors++;

                    continue;
                }

                // Skip if model exists and is active (unless --force)
                if (! $this->option('force')) {
                    $existing = ModelPrediksi::where('fasilitas_id', $faskes->id)
                        ->where('obat_id', $obat->id)
                        ->where('status', 'aktif')
                        ->exists();

                    if ($existing) {
                        $processed++;

                        $progressBar->advance();

                        continue;
                    }
                }

                // Train model
                $model = $predictionService->train($faskes, $obat);
                $trained++;

                // Generate predictions for active or data_belum_cukup models
                if (in_array($model->status, ['aktif', 'data_belum_cukup'], true)) {
                    $predictions = $predictionService->generatePredictions($model);
                    $predictionsGenerated += $predictions->count();
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("Error processing faskes {$combo->fasilitas_id} + obat {$combo->obat_id}: {$e->getMessage()}");
            }

            $processed++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("Training completed in {$duration}s.");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Combinations processed', $processed],
                ['Models trained/updated', $trained],
                ['Predictions generated', $predictionsGenerated],
                ['Errors', $errors],
            ],
        );

        return self::SUCCESS;
    }

    /**
     * Build base query for distinct facility + drug combinations.
     */
    private function buildCombinationsQuery(): Builder
    {
        $query = DB::table('detail_pemakaian_obat as d')
            ->join('pemakaian_obat as p', 'p.id', '=', 'd.pemakaian_id')
            ->selectRaw('DISTINCT p.fasilitas_id as fasilitas_id, d.obat_id as obat_id');

        if ($this->option('fasilitas-id') !== null) {
            $query->where('p.fasilitas_id', (int) $this->option('fasilitas-id'));
        }

        if ($this->option('obat-id') !== null) {
            $query->where('d.obat_id', (int) $this->option('obat-id'));
        }

        return $query;
    }

    /**
     * Get distinct facility + drug combinations that have usage data.
     *
     * @return Collection<int, object{fasilitas_id: int, obat_id: int}>
     */
    private function getCombinations(): Collection
    {
        return $this->buildCombinationsQuery()->get();
    }
}
