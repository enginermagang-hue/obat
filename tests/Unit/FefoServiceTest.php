<?php

namespace Tests\Unit;

use App\Models\BatchStok;
use App\Models\FasilitasKesehatan;
use App\Models\Obat;
use App\Services\FefoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FefoServiceTest extends TestCase
{
    use RefreshDatabase;

    private FefoService $service;

    private Obat $obat;

    private FasilitasKesehatan $faskes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(FefoService::class);

        // Create test data
        $this->obat = Obat::factory()->create(['status' => 'aktif']);
        $this->faskes = FasilitasKesehatan::factory()->create(['tipe' => 'puskesmas', 'status' => 'aktif']);
    }

    public function test_get_available_batches_returns_empty_when_no_batches(): void
    {
        $batches = $this->service->getAvailableBatches($this->obat->id, $this->faskes->id);

        $this->assertCount(0, $batches);
    }

    public function test_get_available_batches_returns_fefo_sorted(): void
    {
        $batchA = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);
        $batchB = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);
        $batchC = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-09-01',
            'jumlah' => 200,
            'status' => 'tersedia',
        ]);

        $batches = $this->service->getAvailableBatches($this->obat->id, $this->faskes->id);

        // Should be sorted by tanggal_expired ASC (FEFO): B, A, C
        $this->assertCount(3, $batches);
        $this->assertEquals($batchB->id, $batches[0]->id);
        $this->assertEquals($batchA->id, $batches[1]->id);
        $this->assertEquals($batchC->id, $batches[2]->id);
    }

    public function test_get_available_batches_excludes_non_tersedia(): void
    {
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'karantina',
        ]);
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);

        $batches = $this->service->getAvailableBatches($this->obat->id, $this->faskes->id);

        $this->assertCount(1, $batches);
    }

    public function test_get_best_batch_id_returns_earliest_expiry(): void
    {
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-09-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);
        $earliest = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);

        $batchId = $this->service->getBestBatchId($this->obat->id, $this->faskes->id);

        $this->assertEquals($earliest->id, $batchId);
    }

    public function test_get_best_batch_id_returns_null_when_no_batches(): void
    {
        $batchId = $this->service->getBestBatchId($this->obat->id, $this->faskes->id);

        $this->assertNull($batchId);
    }

    public function test_allocate_single_batch_sufficient(): void
    {
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);

        $result = $this->service->allocate($this->obat->id, 30, $this->faskes->id);

        $this->assertCount(1, $result);
        $this->assertEquals(30, $result[0]['jumlah']);
    }

    public function test_allocate_splits_across_batches_when_insufficient(): void
    {
        $batchA = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);
        $batchB = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);

        $result = $this->service->allocate($this->obat->id, 120, $this->faskes->id);

        $this->assertCount(2, $result);
        $this->assertEquals($batchA->id, $result[0]['batch_id']);
        $this->assertEquals(50, $result[0]['jumlah']);
        $this->assertEquals($batchB->id, $result[1]['batch_id']);
        $this->assertEquals(70, $result[1]['jumlah']); // 120 - 50 = 70
    }

    public function test_allocate_three_batches_split(): void
    {
        $batchA = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);
        $batchB = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 30,
            'status' => 'tersedia',
        ]);
        $batchC = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-09-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);

        $result = $this->service->allocate($this->obat->id, 150, $this->faskes->id);

        $this->assertCount(3, $result);
        $this->assertEquals($batchA->id, $result[0]['batch_id']);
        $this->assertEquals(50, $result[0]['jumlah']);
        $this->assertEquals($batchB->id, $result[1]['batch_id']);
        $this->assertEquals(30, $result[1]['jumlah']);
        $this->assertEquals($batchC->id, $result[2]['batch_id']);
        $this->assertEquals(70, $result[2]['jumlah']);
    }

    public function test_allocate_returns_available_when_total_insufficient(): void
    {
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 30,
            'status' => 'tersedia',
        ]);
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 20,
            'status' => 'tersedia',
        ]);

        // Request more than total stock (50)
        $result = $this->service->allocate($this->obat->id, 100, $this->faskes->id);

        // Should return whatever is available (50)
        $this->assertCount(2, $result);
        $this->assertEquals(30, $result[0]['jumlah']);
        $this->assertEquals(20, $result[1]['jumlah']);
    }

    public function test_allocate_returns_empty_for_zero_quantity(): void
    {
        $result = $this->service->allocate($this->obat->id, 0, $this->faskes->id);

        $this->assertCount(0, $result);
    }

    public function test_allocate_returns_empty_when_no_batches(): void
    {
        $result = $this->service->allocate($this->obat->id, 50, $this->faskes->id);

        $this->assertCount(0, $result);
    }

    public function test_has_sufficient_stock_returns_true(): void
    {
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);

        $this->assertTrue($this->service->hasSufficientStock($this->obat->id, 80, $this->faskes->id));
    }

    public function test_has_sufficient_stock_returns_false(): void
    {
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);

        $this->assertFalse($this->service->hasSufficientStock($this->obat->id, 100, $this->faskes->id));
    }

    public function test_allocate_prefers_null_fasilitas_for_gudang_dinas(): void
    {
        // Create batches with null fasilitas_id (gudang dinas) and with faskes
        $batchGudang = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => null,
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);
        BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);

        // When querying with null fasilitas (gudang), only gudang batches
        $batches = $this->service->getAvailableBatches($this->obat->id, null);

        $this->assertCount(1, $batches);
        $this->assertEquals($batchGudang->id, $batches[0]->id);
    }

    public function test_get_available_batches_returns_fifo_sorted(): void
    {
        // Create batches with explicit tanggal_masuk for FIFO ordering
        $batchA = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-01-15',
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);
        $batchB = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-01-01',
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);
        $batchC = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-02-01',
            'tanggal_expired' => '2026-09-01',
            'jumlah' => 200,
            'status' => 'tersedia',
        ]);

        // FIFO should sort by tanggal_masuk ASC
        $batches = $this->service->getAvailableBatches($this->obat->id, $this->faskes->id, 'fifo');

        $this->assertCount(3, $batches);
        // Sorted by tanggal_masuk: B (2026-01-01), A (2026-01-15), C (2026-02-01)
        $this->assertEquals($batchB->id, $batches[0]->id);
        $this->assertEquals($batchA->id, $batches[1]->id);
        $this->assertEquals($batchC->id, $batches[2]->id);
    }

    public function test_get_available_batches_returns_lifo_sorted(): void
    {
        // Create batches with explicit tanggal_masuk for LIFO ordering
        $batchA = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-01-15',
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);
        $batchB = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-01-01',
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);
        $batchC = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-02-01',
            'tanggal_expired' => '2026-09-01',
            'jumlah' => 200,
            'status' => 'tersedia',
        ]);

        // LIFO should sort by tanggal_masuk DESC
        $batches = $this->service->getAvailableBatches($this->obat->id, $this->faskes->id, 'lifo');

        $this->assertCount(3, $batches);
        // Sorted by tanggal_masuk DESC: C (2026-02-01), A (2026-01-15), B (2026-01-01)
        $this->assertEquals($batchC->id, $batches[0]->id);
        $this->assertEquals($batchA->id, $batches[1]->id);
        $this->assertEquals($batchB->id, $batches[2]->id);
    }

    public function test_allocate_fifo_splits_across_batches(): void
    {
        $batchA = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-01-01',
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);
        $batchB = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-02-01',
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);

        $result = $this->service->allocate($this->obat->id, 120, $this->faskes->id, 'fifo');

        // FIFO: should take from oldest first (A, then B)
        $this->assertCount(2, $result);
        $this->assertEquals($batchA->id, $result[0]['batch_id']);
        $this->assertEquals(50, $result[0]['jumlah']);
        $this->assertEquals($batchB->id, $result[1]['batch_id']);
        $this->assertEquals(70, $result[1]['jumlah']);
    }

    public function test_allocate_lifo_splits_across_batches(): void
    {
        $batchA = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-01-01',
            'tanggal_expired' => '2026-03-01',
            'jumlah' => 50,
            'status' => 'tersedia',
        ]);
        $batchB = BatchStok::factory()->create([
            'obat_id' => $this->obat->id,
            'fasilitas_id' => $this->faskes->id,
            'tanggal_masuk' => '2026-02-01',
            'tanggal_expired' => '2026-06-01',
            'jumlah' => 100,
            'status' => 'tersedia',
        ]);

        $result = $this->service->allocate($this->obat->id, 120, $this->faskes->id, 'lifo');

        // LIFO: should take from newest first (B, then A)
        $this->assertCount(2, $result);
        $this->assertEquals($batchB->id, $result[0]['batch_id']);
        $this->assertEquals(100, $result[0]['jumlah']);
        $this->assertEquals($batchA->id, $result[1]['batch_id']);
        $this->assertEquals(20, $result[1]['jumlah']);
    }
}
