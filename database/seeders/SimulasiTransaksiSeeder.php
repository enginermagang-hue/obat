<?php

namespace Database\Seeders;

use App\Models\BatchStok;
use App\Models\DetailDistribusiObat;
use App\Models\DetailLplpo;
use App\Models\DetailPemakaianObat;
use App\Models\DetailPenerimaanStok;
use App\Models\DetailPermintaanObat;
use App\Models\DetailReturObat;
use App\Models\DetailRko;
use App\Models\DistribusiObat;
use App\Models\FasilitasKesehatan;
use App\Models\LaporanLplpo;
use App\Models\LaporanRko;
use App\Models\Obat;
use App\Models\PemakaianObat;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\ReturObat;
use App\Models\RiwayatStok;
use App\Models\StokFaskes;
use App\Models\StokGudang;
use App\Models\SumberDana;
use App\Models\SumberDanaPenggunaan;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

use function Laravel\Prompt\confirm;
use function Laravel\Prompt\multiselect;
use function Laravel\Prompt\select;

class SimulasiTransaksiSeeder extends Seeder
{
    private Collection $obatList;

    private Collection $puskesmas;

    private Collection $pustu;

    private Collection $suppliers;

    /** @var array<int, User> indexed by user id */
    private array $users = [];

    /** @var array<int, int> gudangStock[obat_id] = jumlah */
    private array $gudangStock = [];

    /** @var array<int, array<int, int>> faskesStock[fasilitas_id][obat_id] = jumlah */
    private array $faskesStock = [];

    /** @var array<int, array<int, array{batch_id: int, jumlah: int}[]>> batchByFaskes[fasilitas_id][obat_id][] */
    private array $batchByFaskes = [];

    /** @var array<int, array<int, array{batch_id: int, jumlah: int}[]>> batchByGudang[obat_id][] */
    private array $batchByGudang = [];

    /** @var array<int, bool> track which unique batches exist at gudang */
    private array $gudangBatchExists = [];

    /** @var array<int, array<int, int>> snapshot of facility stock at start of each month (LPLPO consistency) */
    private array $faskesStockStartOfMonth = [];

    /** Counter for nomor surat */
    private int $counterPenerimaan = 1;

    private int $counterPermintaan = 1;

    private int $counterDistribusi = 1;

    private int $counterLPLPO = 1;

    private int $counterRetur = 1;

    private int $counterRKO = 1;

    /** @var array<int, array{bulan: int, tahun: int}> track months when LPLPO is submitted */
    private array $faskesLPLPOGenerated = [];

    /** @var array<string, int> Stats counters for summary */
    private array $stats = [
        'penerimaan' => 0,
        'distribusi' => 0,
        'pemakaian' => 0,
        'permintaan' => 0,
        'lplpo' => 0,
        'retur' => 0,
        'rko' => 0,
    ];

    // -----------------------------------------------------------------------
    //  Consumption baseline: [puskesmas_avg_per_month, pustu_avg_per_month]
    // -----------------------------------------------------------------------
    private const KONSUMSI = [
        // === Volume Tinggi ===
        44 => [400, 180], // Parasetamol 500mg — demam/nyeri
        11 => [280, 130], // Amoxicillin 500mg — infeksi
        7 => [120, 50],  // Amlodipin 5mg — hipertensi
        6 => [100, 40],  // Amlodipin 10mg — hipertensi
        39 => [200, 80],  // Metformin 500mg — diabetes
        34 => [180, 70],  // Captopril 25mg — hipertensi
        19 => [350, 150], // Vitamin C 50mg — suplemen
        12 => [180, 80],  // Antasida tablet — maag
        32 => [200, 90],  // Ibuprofen 400mg — nyeri

        // === Volume Sedang ===
        37 => [150, 60],  // Kotrimoksazol — infeksi
        28 => [120, 60],  // Oralit — diare
        25 => [100, 40],  // Furosemid 40mg — hipertensi
        47 => [120, 50],  // Ranitidin 150mg — maag
        43 => [100, 40],  // Omeprazole 20mg — maag
        29 => [90, 35],   // Glimepirid 2mg — diabetes
        49 => [80, 30],   // Salbutamol 2mg — asma
        36 => [100, 40],  // Prednison 5mg — antiinflamasi
        40 => [100, 45],  // Diclofenac 50mg — nyeri sendi
        51 => [90, 35],   // Simvastatin 10mg — kolesterol
        33 => [100, 40],  // Kalsium Laktat — suplemen
        53 => [80, 40],   // Zinc Sulfate — diare
        4 => [50, 20],   // Alopurinol 300mg — asam urat
        14 => [180, 80],  // Fe + Folic Acid — tablet tambah darah
        45 => [60, 25],   // Pyridoxin 25mg — vitamin B6
        1 => [80, 30],   // Albendazole 400mg — cacingan (periodik)
        15 => [60, 25],   // Loratadin 10mg — alergi

        // === Volume Rendah (Spesifik) ===
        41 => [100, 40],  // OAT FDC Kat 1 — TB (program)
        52 => [60, 20],   // Tenofovir/Lamivudin/Efavirenz — ARV
        21 => [40, 15],   // Asiklovir 200mg — herpes
        46 => [30, 10],   // Primakuin 25mg — malaria
        24 => [20, 8],    // Diazepam 5mg — psikotropika
        30 => [15, 5],    // Haloperidol 5mg — psikotropika
        48 => [50, 20],   // Vitamin A 200.000 IU — program
        20 => [60, 25],   // Vitamin C 250mg

        // === Topikal/Salep ===
        18 => [20, 10],   // Mikonazol Krim
        35 => [20, 10],   // Betametason Krim
        50 => [15, 8],    // Gentamisin Salep Mata
        23 => [12, 6],    // Asiklovir Krim

        // === Injeksi ===
        26 => [10, 4],    // Epinephrine — emergency
        42 => [8, 3],     // Oksitosin — maternal
        38 => [15, 5],    // Lidocain — anestesi lokal

        // === Sirup ===
        31 => [30, 15],   // Ibuprofen Suspensi (anak)
        8 => [20, 10],   // Amoxicillin Sirup 250mg/5mL
        9 => [15, 8],    // Amoxicillin Sirup 125mg/5mL
        10 => [12, 6],    // Amoxicillin Sirup 100mg/5mL
        13 => [30, 15],   // Antasida Suspensi
        17 => [15, 8],    // Domperidon Suspensi
        16 => [50, 20],   // Domperidon 10mg
        2 => [10, 5],    // Albendazole Susp 200mg/5mL
        3 => [10, 5],    // Albendazole Susp 400mg/5mL
        22 => [30, 12],   // Asiklovir 400mg
        27 => [20, 10],   // Fitomenadion 10mg
    ];

    // -----------------------------------------------------------------------
    //  Seasonal multipliers (rainy season: Nov-Apr)
    // -----------------------------------------------------------------------
    private const OBAT_MUSIMAN = [44, 11, 28, 37, 53, 31]; // Parasetamol, Amoxicillin, Oralit, Kotrimoksazol, Zinc, Ibuprofen Susp

    public function run(): void
    {
        // Ensure seed dependencies are met (self-contained execution)
        $this->call([
            RoleAndPermissionSeeder::class,
            FaskesSeeder::class,
            ObatSeeder::class,
            SupplierSeeder::class,
            SumberDanaSeeder::class,
            StokGudangSeeder::class,
            AvatarPresetSeeder::class,
        ]);

        $config = $this->promptUserConfig();

        $this->loadReferenceData();
        $this->createFaskesUsers();
        $this->initStockTracking();

        $startDate = Carbon::parse($config['start_date']);
        $totalMonths = $config['total_months'];
        $modules = $config['modules'];
        $dryRun = $config['dry_run'];
        $verbose = $config['verbose'];

        $endDate = (clone $startDate)->addMonths($totalMonths - 1);

        $this->command?->info('Konfigurasi simulasi:');
        $this->command?->info("  Periode     : {$startDate->format('M Y')} – {$endDate->format('M Y')} ({$totalMonths} bulan)");
        $this->command?->info('  Modul       : '.implode(', ', $modules));
        $this->command?->info('  Mode        : '.($dryRun ? 'Dry Run (tidak menyimpan)' : 'Live'));
        $this->command?->newLine();

        $startTime = microtime(true);
        $output = $this->command->getOutput();

        $output->writeln('  ╔═════════════════════════════════════╗');
        $output->writeln('  ║       Simulasi Transaksi Obat      ║');
        $output->writeln('  ╚═════════════════════════════════════╝');
        $output->writeln('');

        for ($month = 0; $month < $totalMonths; $month++) {
            $date = (clone $startDate)->addMonths($month);
            $label = $date->format('M Y');

            DB::transaction(fn () => $this->generateMonthlyData($date, $month, $modules));

            $elapsed = round(microtime(true) - $startTime, 1);
            $percent = (int) round(($month + 1) / $totalMonths * 100);
            $barLength = 22;
            $filled = (int) round($percent / 100 * $barLength);
            $bar = str_repeat('█', $filled).str_repeat('░', $barLength - $filled);

            $penerimaan = $this->counterPenerimaan - 1;
            $distribusi = $this->counterDistribusi - 1;
            $permintaan = $this->counterPermintaan - 1;
            $lplpo = $this->counterLPLPO - 1;
            $retur = $this->counterRetur - 1;
            $rko = $this->counterRKO - 1;

            $dashboard =
                "  {$bar}  {$percent}%  ⏱ {$elapsed}s  {$label}\n".
                "    PO: {$penerimaan}  │  SJ: {$distribusi}  │  RQ: {$permintaan}\n".
                "    LPLPO: {$lplpo}  │  RKO: {$rko}  │  Retur: {$retur}\n";

            if ($month > 0) {
                $output->write("\e[3A\e[J{$dashboard}");
            } else {
                $output->write($dashboard);
            }
        }

        $output->writeln('');

        $duration = round(microtime(true) - $startTime, 2);

        if (! $dryRun) {
            $this->command?->info('Menyimpan stok akhir...');
            $this->persistFinalStock();
        }

        $this->printSummaryTable($totalMonths, $duration, $dryRun);
    }

    // =======================================================================
    //  SETUP
    // =======================================================================

    private function loadReferenceData(): void
    {
        $this->obatList = Obat::all()->keyBy('id');
        $this->puskesmas = FasilitasKesehatan::where('tipe', 'puskesmas')->get();
        $this->pustu = FasilitasKesehatan::where('tipe', 'pustu')->get();
        $this->suppliers = Supplier::where('status', 'aktif')->get();
    }

    private function promptUserConfig(): array
    {
        $defaults = [
            'start_date' => '2024-06',
            'total_months' => 24,
            'modules' => ['penerimaan', 'distribusi', 'pemakaian', 'permintaan', 'lplpo', 'retur', 'rko'],
            'dry_run' => false,
            'verbose' => true,
        ];

        if (! $this->command) {
            return $defaults;
        }

        try {
            $startOptions = [];
            $startDate = Carbon::parse('2024-06-01');
            for ($i = 0; $i < 20; $i++) {
                $date = (clone $startDate)->addMonths($i);
                $startOptions[$date->format('Y-m')] = $date->format('M Y');
            }

            $startDateKey = select(
                label: 'Tanggal mulai simulasi',
                options: $startOptions,
                default: $defaults['start_date'],
            );

            $totalMonths = (int) select(
                label: 'Durasi simulasi (bulan)',
                options: ['6' => '6 bulan', '12' => '12 bulan', '18' => '18 bulan', '24' => '24 bulan', '36' => '36 bulan'],
                default: (string) $defaults['total_months'],
            );

            $modules = multiselect(
                label: 'Modul yang akan dijalankan',
                options: [
                    'penerimaan' => 'Penerimaan Stok Gudang',
                    'distribusi' => 'Distribusi Obat',
                    'pemakaian' => 'Pemakaian Obat',
                    'permintaan' => 'Permintaan Obat',
                    'lplpo' => 'Laporan LPLPO',
                    'retur' => 'Retur Obat',
                    'rko' => 'Rencana Kebutuhan Obat (RKO)',
                ],
                default: $defaults['modules'],
            );

            $dryRun = confirm(
                label: 'Mode dry run? (tanpa menyimpan ke database)',
                default: $defaults['dry_run'],
            );

            $verbose = confirm(
                label: 'Tampilkan detail progress per bulan?',
                default: $defaults['verbose'],
            );

            return [
                'start_date' => $startDateKey,
                'total_months' => $totalMonths,
                'modules' => $modules,
                'dry_run' => $dryRun,
                'verbose' => $verbose,
            ];
        } catch (\Throwable) {
            return $defaults;
        }
    }

    /**
     * Pick a SumberDana for the given year using weighted random distribution.
     * Distribution: 70% APBN (DAK + BOK), 20% APBD, 10% Dana Desa.
     * Each call independently random — no deterministic year-biasing, no caching
     * (we want the 70/20/10 distribution to apply per batch, not per year).
     */
    private function pickSumberDana(int $tahun): SumberDana
    {
        $rand = random_int(1, 100);

        if ($rand <= 70) {
            // 70% APBN: random between DAK and BOK
            $kode = random_int(1, 2) === 1 ? "DAK-{$tahun}-01" : "BOK-{$tahun}-01";
        } elseif ($rand <= 90) {
            // 20% APBD
            $kode = "APBD-{$tahun}-01";
        } else {
            // 10% Dana Desa
            $kode = "DANA-DESA-{$tahun}-01";
        }

        $sd = SumberDana::where('kode', $kode)->first();
        if (! $sd) {
            // Fallback: pick any active sumber dana for the year
            $sd = SumberDana::where('tahun', $tahun)->first()
                ?? SumberDana::inRandomOrder()->first();
        }

        return $sd;
    }

    private function createFaskesUsers(): void
    {
        $faskesAll = $this->puskesmas->merge($this->pustu);

        foreach ($faskesAll as $faskes) {
            $email = strtolower(str_replace([' ', '.', '-'], '', $faskes->nama)).'@mail.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => "Petugas {$faskes->nama}",
                    'password' => '123',
                    'email_verified_at' => now(),
                    'fasilitas_kesehatan_id' => $faskes->id,
                ],
            );

            $role = $faskes->tipe === 'puskesmas' ? 'puskesmas' : 'pustu';
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $this->users[$faskes->id] = $user;
        }

        // Ensure system users exist
        $sysUserDefs = [
            ['email' => 'admin_gudang@mail.com', 'name' => 'Admin Gudang', 'role' => 'admin_gudang'],
            ['email' => 'admin_dinas@mail.com', 'name' => 'Admin Dinas', 'role' => 'admin_dinas'],
            ['email' => 'superadmin@mail.com', 'name' => 'Super Admin', 'role' => 'super_admin'],
        ];

        foreach ($sysUserDefs as $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                [
                    'name' => $def['name'],
                    'password' => '123',
                    'email_verified_at' => now(),
                    'fasilitas_kesehatan_id' => null,
                ],
            );

            $role = Role::firstOrCreate(['name' => $def['role'], 'guard_name' => 'web']);

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $this->users['sys_'.$user->email] = $user;
        }
    }

    private function initStockTracking(): void
    {
        // Load initial gudang stock
        $stokGudang = StokGudang::all();
        foreach ($stokGudang as $sg) {
            $this->gudangStock[$sg->obat_id] = (int) $sg->jumlah;

            // Pick sumber dana per-batch (so init stock follows 70/20/10 distribution instead of all-or-nothing)
            $sumberDanaAwal = $this->pickSumberDana(2024);

            // Create REAL batch records for initial stock (avoids FK issues)
            $batch = BatchStok::create([
                'penerimaan_id' => null,
                'fasilitas_id' => null,
                'sumber_dana_id' => $sumberDanaAwal->id,
                'obat_id' => $sg->obat_id,
                'batch_number' => 'INIT/'.str_pad((string) $sg->obat_id, 3, '0', STR_PAD_LEFT).'/JUN2024',
                'tanggal_expired' => '2026-12-31',
                'jumlah' => (int) $sg->jumlah,
                'status' => 'tersedia',
                'tanggal_masuk' => '2024-06-01',
                'harga_beli' => 0,
            ]);

            $this->batchByGudang[$sg->obat_id] = [[
                'batch_id' => $batch->id,
                'jumlah' => (int) $sg->jumlah,
            ]];
        }

        // Initialize empty faskes stock
        foreach ($this->puskesmas as $p) {
            $this->faskesStock[$p->id] = [];
            $this->batchByFaskes[$p->id] = [];
        }
        foreach ($this->pustu as $p) {
            $this->faskesStock[$p->id] = [];
            $this->batchByFaskes[$p->id] = [];
        }

        $this->faskesStockStartOfMonth = [];
    }

    // =======================================================================
    //  MONTHLY GENERATOR
    // =======================================================================

    private function generateMonthlyData(Carbon $date, int $monthIndex, array $modules): void
    {
        $seasonMul = $this->getSeasonMultiplier($date);

        // 0. For first 2 months, seed initial stock to faskes (so pemakaian has stock)
        if ($monthIndex < 2) {
            $this->distributeInitialStock($date);
        }

        // Snapshot facility stock at the start of this month (used by LPLPO for stok_awal).
        // Taken AFTER initial distribution so LPLPO of months 0-1 has an accurate stok_awal.
        $this->snapshotFaskesStock();

        // 1. Penerimaan Stok di Gudang (2-4 kali per bulan)
        if (in_array('penerimaan', $modules)) {
            $this->generatePenerimaanGudang($date);
        }

        // 2. Distribusi Obat (before pemakaian so faskes have stock).
        // Skipped in the first 2 months because distributeInitialStock already seeded stock.
        if (in_array('distribusi', $modules) && $monthIndex >= 2) {
            $this->generateAllDistribusi($date);
        }

        // 3. Pemakaian Obat di setiap faskes (puskesmas + pustu)
        if (in_array('pemakaian', $modules)) {
            $this->generatePemakaian($date, $seasonMul);
        }

        // 4. Permintaan Obat (based on remaining stock after pemakaian)
        if (in_array('permintaan', $modules)) {
            $this->generateAllPermintaan($date);
        }

        // 5. LPLPO
        if (in_array('lplpo', $modules)) {
            $this->generateAllLPLPO($date);
        }

        // 6. RKO — generated in December for the next year
        if (in_array('rko', $modules) && $date->month == 12) {
            $this->generateAllRKO($date);
        }

        // 7. Retur setiap 3 bulan
        if (in_array('retur', $modules) && $monthIndex > 0 && $monthIndex % 3 === 0) {
            $this->generateAllRetur($date);
        }
    }

    private function snapshotFaskesStock(): void
    {
        $this->faskesStockStartOfMonth = [];
        foreach ($this->faskesStock as $fasilitasId => $stocks) {
            foreach ($stocks as $obatId => $jumlah) {
                $this->faskesStockStartOfMonth[$fasilitasId][$obatId] = $jumlah;
            }
        }
    }

    // =======================================================================
    //  SEASONALITY
    // =======================================================================

    private function getSeasonMultiplier(Carbon $date): float
    {
        $month = $date->month;

        // Rainy season: Nov (11) – Apr (4)
        if ($month >= 11 || $month <= 4) {
            return 1.0 + random_int(20, 45) / 100; // +20% ~ +45%
        }

        return 1.0;
    }

    // =======================================================================
    //  1. PENERIMAAN STOK (Gudang)
    // =======================================================================

    private function generatePenerimaanGudang(Carbon $date): void
    {
        $totalReceipts = random_int(2, 4);
        $adminGudang = $this->users['sys_admin_gudang@mail.com'];

        for ($r = 0; $r < $totalReceipts; $r++) {
            $supplier = $this->suppliers->random();
            $tglTerima = (clone $date)->addDays(random_int(1, 25));
            // Pick sumber dana per-PO (more realistic — each PO has its own funding source)
            $sumberDana = $this->pickSumberDana($date->year);

            $penerimaan = PenerimaanStok::create([
                'nomor_penerimaan' => 'PO/'.$date->format('Y/m').'/'.str_pad((string) $this->counterPenerimaan++, 4, '0', STR_PAD_LEFT),
                'tipe' => 'pembelian',
                'supplier_id' => $supplier->id,
                'sumber_dana_id' => $sumberDana->id,
                'nomor_po' => 'PO-'.$date->format('Ymd').'-'.strtoupper(substr($supplier->nama, 0, 3)).'-'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
                'nomor_invoice' => 'INV/'.$date->format('Y/m').'/'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'tanggal_penerimaan' => $tglTerima,
                'fasilitas_id' => null, // gudang level
                'user_id' => $adminGudang->id,
                'status' => 'dikonfirmasi',
                'catatan' => 'Penerimaan bulan '.$date->format('F Y'),
                'total_biaya' => 0,
            ]);

            // Pick 5-12 drugs for this receipt
            $drugCount = random_int(5, 12);
            $selectedObat = $this->obatList->random(min($drugCount, $this->obatList->count()));
            $totalBiaya = 0;
            $jumlahObat = 0;

            foreach ($selectedObat as $obat) {
                $qty = $this->getPenerimaanQty($obat->id);

                if ($qty <= 0) {
                    continue;
                }

                $hargaSatuan = $obat->harga_satuan ?: $this->estimateHarga($obat->id);
                $subTotal = $qty * $hargaSatuan;
                $totalBiaya += $subTotal;
                $jumlahObat++;

                $batchNumber = 'BCH-'.$date->format('Ym').'-'.strtoupper(substr($obat->kode_obat, -4)).'-'.str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
                $tglExpired = (clone $date)->addMonths(random_int(12, 36))->addDays(random_int(1, 28));

                $batch = BatchStok::create([
                    'penerimaan_id' => $penerimaan->id,
                    'fasilitas_id' => null, // gudang
                    'sumber_dana_id' => $sumberDana->id,
                    'obat_id' => $obat->id,
                    'batch_number' => $batchNumber,
                    'tanggal_expired' => $tglExpired,
                    'jumlah' => $qty,
                    'status' => 'tersedia',
                    'tanggal_masuk' => $tglTerima,
                    'harga_beli' => $hargaSatuan,
                ]);

                DetailPenerimaanStok::create([
                    'penerimaan_id' => $penerimaan->id,
                    'obat_id' => $obat->id,
                    'batch_number' => $batchNumber,
                    'tanggal_expired' => $tglExpired,
                    'jumlah' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'sub_total' => $subTotal,
                    'keterangan' => null,
                ]);

                // Update in-memory gudang stock
                $this->gudangStock[$obat->id] = ($this->gudangStock[$obat->id] ?? 0) + $qty;
                $this->batchByGudang[$obat->id][] = [
                    'batch_id' => $batch->id,
                    'jumlah' => $qty,
                ];

                RiwayatStok::create([
                    'fasilitas_id' => null,
                    'obat_id' => $obat->id,
                    'tipe' => 'masuk',
                    'jumlah' => $qty,
                    'stok_sebelum' => $this->gudangStock[$obat->id] - $qty,
                    'stok_sesudah' => $this->gudangStock[$obat->id],
                    'referensi_type' => PenerimaanStok::class,
                    'referensi_id' => $penerimaan->id,
                    'user_id' => $adminGudang->id,
                    'keterangan' => 'Penerimaan dari '.$supplier->nama,
                    'tanggal' => $tglTerima,
                ]);
            }

            $penerimaan->update(['total_biaya' => $totalBiaya]);

            // Track sumber dana usage (1 row per PenerimaanStok, dinas level)
            if ($totalBiaya > 0) {
                SumberDanaPenggunaan::create([
                    'sumber_dana_id' => $sumberDana->id,
                    'rko_id' => null,
                    'fasilitas_id' => null, // dinas/gudang level
                    'tipe' => 'realisasi',
                    'jumlah_obat' => $jumlahObat,
                    'total_biaya' => $totalBiaya,
                    'tanggal' => $tglTerima,
                    'keterangan' => 'Realisasi penerimaan dari '.$supplier->nama.' ('.$sumberDana->kode.')',
                ]);
            }
        }

        $this->stats['penerimaan'] += $totalReceipts;
    }

    private function getPenerimaanQty(int $obatId): int
    {
        // Base quantity based on drug consumption to maintain ~3-4 months buffer
        return match ($obatId) {
            44 => random_int(2000, 5000),     // Parasetamol
            11 => random_int(1500, 4000),     // Amoxicillin
            7, 6 => random_int(1000, 3000),   // Amlodipin
            39 => random_int(1000, 2500),     // Metformin
            34 => random_int(800, 2000),      // Captopril
            19 => random_int(2000, 4000),     // Vitamin C 50mg
            12 => random_int(800, 2000),      // Antasida
            32 => random_int(1000, 2500),     // Ibuprofen
            37 => random_int(500, 1500),      // Kotrimoksazol
            28 => random_int(500, 1500),      // Oralit
            25, 47, 43 => random_int(500, 1500),
            14 => random_int(1000, 2000),     // Fe + Folic
            41 => random_int(500, 1500),      // OAT FDC
            52 => random_int(300, 800),       // ARV
            default => random_int(200, 1000),
        };
    }

    private function estimateHarga(int $obatId): float
    {
        return match ($obatId) {
            44 => 150,       // Parasetamol
            11 => 300,       // Amoxicillin
            6, 7 => 500,     // Amlodipin
            39 => 400,       // Metformin
            34 => 250,       // Captopril
            19 => 100,       // Vitamin C
            28 => 2000,      // Oralit
            26 => 5000,      // Epinephrine
            42 => 8000,      // Oksitosin
            38 => 3000,      // Lidocain
            41 => 2500,      // OAT FDC
            default => 1000,
        };
    }

    // =======================================================================
    //  2. PEMAKAIAN OBAT
    // =======================================================================

    private function generatePemakaian(Carbon $date, float $seasonMul): void
    {
        foreach ($this->puskesmas as $puskesmas) {
            $this->generatePemakaianFaskes($puskesmas, $date, $seasonMul, 'puskesmas');
        }
        foreach ($this->pustu as $pustu) {
            $this->generatePemakaianFaskes($pustu, $date, $seasonMul, 'pustu');
        }
    }

    private function generatePemakaianFaskes(FasilitasKesehatan $faskes, Carbon $date, float $seasonMul, string $tipe): void
    {
        $user = $this->users[$faskes->id] ?? null;
        if (! $user) {
            return;
        }

        $obatTerpakai = array_rand(self::KONSUMSI, random_int(12, 25));
        if (! is_array($obatTerpakai)) {
            $obatTerpakai = [$obatTerpakai];
        }

        $jenisPelayananList = ['rawat_jalan', 'rawat_inap', 'uks', 'posyandu', 'pusling', 'gigi', 'laboratorium', 'apotek', 'lainnya'];

        foreach ($obatTerpakai as $obatId) {
            [$avgPuskesmas, $avgPustu] = self::KONSUMSI[$obatId];
            $avg = $tipe === 'puskesmas' ? $avgPuskesmas : $avgPustu;

            // Apply seasonal multiplier for certain drugs
            $actualAvg = in_array($obatId, self::OBAT_MUSIMAN) ? (int) ($avg * $seasonMul) : $avg;

            // Random variation ±30%
            $jumlah = max(1, (int) ($actualAvg * random_int(70, 130) / 100));

            // Check stock availability — hanya pakai sesuai stok
            $currentStock = $this->faskesStock[$faskes->id][$obatId] ?? 0;
            if ($jumlah > $currentStock) {
                $jumlah = max(0, $currentStock);
            }
            if ($jumlah <= 0) {
                continue;
            }

            // Distribute pemakaian across several days
            $daysInMonth = (clone $date)->daysInMonth;
            $hariPakai = random_int(1, min($daysInMonth, 10));
            $usedDays = [];
            for ($d = 0; $d < $hariPakai; $d++) {
                $usedDays[] = random_int(1, $daysInMonth);
            }
            $usedDays = array_unique($usedDays);
            $perDay = max(1, (int) ($jumlah / count($usedDays)));

            foreach ($usedDays as $day) {
                $dayQty = $perDay;
                $tglPakai = (clone $date)->day(min($day, $daysInMonth));

                // Pick a batch
                $batchInfo = $this->pickBatch($faskes->id, $obatId, $dayQty);
                if ($batchInfo === null) {
                    continue;
                }

                // 1. Create header (1 pelayanan event = 1 patient encounter)
                $pemakaian = PemakaianObat::create([
                    'nomor_pemakaian' => PemakaianObat::generateNomorPemakaian($tglPakai),
                    'fasilitas_id' => $faskes->id,
                    'tanggal_pemakaian' => $tglPakai,
                    'jenis_pelayanan' => $jenisPelayananList[array_rand($jenisPelayananList)],
                    'nama_pasien' => fake()->name(),
                    'no_rekam_medis' => $this->generateNomorRM($tglPakai),
                    'diagnosa_kode' => $this->randomDiagnosa(),
                    'user_id' => $user->id,
                    'catatan' => null,
                ]);

                // 2. Create detail (1 row per obat used in this pelayanan)
                $detail = DetailPemakaianObat::create([
                    'pemakaian_id' => $pemakaian->id,
                    'obat_id' => $obatId,
                    'batch_id' => $batchInfo['batch_id'],
                    'jumlah' => $dayQty,
                    'dosis' => null,
                    'satuan_dosis' => null,
                    'catatan' => null,
                ]);

                // 3. Update stock (seeder parallel tracker)
                $stokSebelum = $this->faskesStock[$faskes->id][$obatId] ?? 0;
                $this->faskesStock[$faskes->id][$obatId] = $stokSebelum - $dayQty;

                // 4. Create riwayat (polymorphic ref → DetailPemakaianObat)
                RiwayatStok::create([
                    'fasilitas_id' => $faskes->id,
                    'obat_id' => $obatId,
                    'tipe' => 'keluar',
                    'jumlah' => $dayQty,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSebelum - $dayQty,
                    'referensi_type' => DetailPemakaianObat::class,
                    'referensi_id' => $detail->id,
                    'user_id' => $user->id,
                    'keterangan' => 'Pemakaian '.$tglPakai->format('d M Y'),
                    'tanggal' => $tglPakai,
                ]);

                $this->stats['pemakaian']++;
            }
        }
    }

    private function generateNomorRM(Carbon $date): string
    {
        return 'RM-'.$date->format('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function pickBatch(int $fasilitasId, int $obatId, int $jumlah): ?array
    {
        // Try facility-specific batches first
        $batches = $this->batchByFaskes[$fasilitasId][$obatId] ?? [];

        // Fall back to gudang if no facility batches (shouldn't happen after distribusi)
        if (empty($batches)) {
            return null;
        }

        // FIFO: oldest batches first
        usort($batches, fn ($a, $b) => $a['batch_id'] <=> $b['batch_id']);

        // Consume the requested quantity across batches until satisfied
        $remaining = $jumlah;
        $lastBatchId = null;

        foreach ($batches as &$batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($batch['jumlah'], $remaining);
            $batch['jumlah'] -= $take;
            $remaining -= $take;
            $lastBatchId = $batch['batch_id'];
        }

        // If we could not fulfil the full quantity, restore batches and bail out
        // (do not record a phantom deduction — faskesStock stays untouched on null).
        if ($remaining > 0 || $lastBatchId === null) {
            return null;
        }

        // Remove exhausted batches and persist the tracking array only on success
        $batches = array_values(array_filter($batches, fn ($b) => $b['jumlah'] > 0));
        $this->batchByFaskes[$fasilitasId][$obatId] = $batches;

        return ['batch_id' => $lastBatchId, 'sisa' => $remaining];
    }

    private function randomDiagnosa(): string
    {
        $diagnosa = [
            'A00', 'A01', 'A02', 'A03', 'A04', 'A05', 'A06', 'A07', 'A08', 'A09',
            'B00', 'B01', 'B02', 'B05', 'B15', 'B16', 'B17', 'B18', 'B19',
            'E10', 'E11', 'E14',
            'I10', 'I11', 'I15',
            'J00', 'J01', 'J02', 'J03', 'J04', 'J05', 'J06', 'J15', 'J18',
            'K21', 'K25', 'K26', 'K27', 'K29', 'K30',
            'L00', 'L01', 'L02', 'L03', 'L08',
            'M00', 'M01', 'M02', 'M05', 'M06', 'M10', 'M15', 'M17', 'M25',
            'N10', 'N11', 'N12', 'N30', 'N39', 'N40',
            'R50', 'R51', 'R52', 'R53', 'R55', 'R10', 'R11',
        ];

        return $diagnosa[array_rand($diagnosa)].'.'.random_int(0, 9);
    }

    // =======================================================================
    //  3. PERMINTAAN OBAT
    // =======================================================================

    private function generateAllPermintaan(Carbon $date): void
    {
        // Pustu → Puskesmas
        foreach ($this->pustu as $pustu) {
            $indukId = $pustu->puskesmas_induk_id;
            if (! $indukId) {
                continue;
            }
            $this->generatePermintaan($pustu->id, $indukId, 'pustu_ke_puskesmas', $date);
        }

        // Puskesmas → Dinas (gudang, represented as null fasilitas_id)
        foreach ($this->puskesmas as $puskesmas) {
            $this->generatePermintaan($puskesmas->id, null, 'puskesmas_ke_dinas', $date);
        }
    }

    private function generatePermintaan(int $pengirimId, ?int $tujuanId, string $tipe, Carbon $date): void
    {
        $stok = $tipe === 'pustu_ke_puskesmas' ? ($this->faskesStock[$pengirimId] ?? []) : ($this->faskesStock[$pengirimId] ?? []);

        // Drugs with stock below threshold need restocking
        $obatButuh = [];
        foreach ($stok as $obatId => $jumlah) {
            if ($jumlah < $this->getStokMinimum($obatId, $pengirimId)) {
                $obatButuh[] = $obatId;
            }
        }

        // Also request some drugs that are depleted (0 stock)
        $depleted = [];
        foreach ($this->faskesStock[$pengirimId] ?? [] as $obatId => $jumlah) {
            if ($jumlah <= 0) {
                $depleted[] = $obatId;
            }
        }

        $allRequested = array_unique(array_merge(
            $obatButuh,
            $depleted,
            // Add some random drugs to simulate normal request
            $this->obatList->random(min(random_int(2, 6), $this->obatList->count()))->pluck('id')->toArray(),
        ));

        $allRequested = array_slice($allRequested, 0, random_int(5, 15));
        if (empty($allRequested)) {
            return;
        }

        $petugas = $this->users[$pengirimId] ?? $this->users['sys_admin_gudang@mail.com'];
        $tglPermintaan = (clone $date)->addDays(random_int(5, 20));

        $permintaan = PermintaanObat::create([
            'nomor_permintaan' => 'RQ/'.$date->format('Y/m').'/'.str_pad((string) $this->counterPermintaan++, 4, '0', STR_PAD_LEFT),
            'fasilitas_pengirim_id' => $pengirimId,
            'fasilitas_tujuan_id' => $tujuanId,
            'tipe_permintaan' => $tipe,
            'lplpo_id' => null,
            'status' => 'diterima',
            'tanggal_permintaan' => $tglPermintaan,
            'tanggal_disetujui' => (clone $tglPermintaan)->addDays(random_int(1, 3)),
            'tanggal_dikirim' => (clone $tglPermintaan)->addDays(random_int(3, 7)),
            'tanggal_diterima' => (clone $tglPermintaan)->addDays(random_int(5, 10)),
            'disetujui_oleh' => $this->users['sys_admin_dinas@mail.com']->id,
            'catatan' => null,
        ]);

        foreach ($allRequested as $obatId) {
            $currentStock = $this->faskesStock[$pengirimId][$obatId] ?? 0;
            $avgConsumption = $this->getAvgConsumption($obatId, $pengirimId);
            $diminta = max(50, (int) ($avgConsumption * random_int(15, 30) / 10) - $currentStock);
            $diminta = max($diminta, random_int(50, 200));

            // Approve 70-100% of what was requested
            $disetujui = (int) ($diminta * random_int(70, 100) / 100);
            $dikirim = (int) ($disetujui * random_int(80, 100) / 100);
            $diterima = (int) ($dikirim * random_int(90, 100) / 100);

            DetailPermintaanObat::create([
                'permintaan_id' => $permintaan->id,
                'obat_id' => $obatId,
                'jumlah_diminta' => $diminta,
                'jumlah_disetujui' => $disetujui,
                'jumlah_dikirim' => $dikirim,
                'jumlah_diterima' => $diterima,
                'catatan' => null,
            ]);
        }

        $this->stats['permintaan']++;
    }

    private function getStokMinimum(int $obatId, int $fasilitasId): int
    {
        $avg = $this->getAvgConsumption($obatId, $fasilitasId);

        // Minimum = 1.5× monthly consumption
        return max(20, (int) ($avg * 1.5));
    }

    private function getAvgConsumption(int $obatId, int $fasilitasId): int
    {
        $faskes = $this->puskesmas->firstWhere('id', $fasilitasId)
            ?? $this->pustu->firstWhere('id', $fasilitasId);

        $tipe = $faskes && $faskes->tipe === 'puskesmas' ? 'puskesmas' : 'pustu';

        [$avgPuskesmas, $avgPustu] = self::KONSUMSI[$obatId] ?? [50, 20];

        return $tipe === 'puskesmas' ? $avgPuskesmas : $avgPustu;
    }

    // =======================================================================
    //  4. DISTRIBUSI OBAT
    // =======================================================================

    private function distributeInitialStock(Carbon $date): void
    {
        // Distribute 2 months worth of stock to each puskesmas
        foreach ($this->puskesmas as $puskesmas) {
            $this->generateDistribusi(null, $puskesmas->id, 'dinas_ke_puskesmas', $date, true);
        }
        // Distribute to each pustu via their induk
        foreach ($this->pustu as $pustu) {
            $indukId = $pustu->puskesmas_induk_id;
            if ($indukId) {
                $this->generateDistribusi($indukId, $pustu->id, 'puskesmas_ke_pustu', $date, true);
            }
        }
    }

    private function generateAllDistribusi(Carbon $date): void
    {
        // Dinas → Puskesmas
        foreach ($this->puskesmas as $puskesmas) {
            $this->generateDistribusi(null, $puskesmas->id, 'dinas_ke_puskesmas', $date, false);
        }

        // Puskesmas → Pustu
        foreach ($this->pustu as $pustu) {
            $indukId = $pustu->puskesmas_induk_id;
            if (! $indukId) {
                continue;
            }
            $this->generateDistribusi($indukId, $pustu->id, 'puskesmas_ke_pustu', $date, false);
        }
    }

    private function generateDistribusi(?int $pengirimId, int $penerimaId, string $tipe, Carbon $date, bool $isInitial = false): void
    {
        // Determine drugs to distribute based on consumption needs
        $faskes = $this->puskesmas->firstWhere('id', $penerimaId)
            ?? $this->pustu->firstWhere('id', $penerimaId);
        $tipeFaskes = $faskes && $faskes->tipe === 'puskesmas' ? 'puskesmas' : 'pustu';

        $obatIds = [];
        $currentFaskesStock = $this->faskesStock[$penerimaId] ?? [];

        foreach (self::KONSUMSI as $obatId => [$avgPusk, $avgPustu]) {
            $avg = $tipeFaskes === 'puskesmas' ? $avgPusk : $avgPustu;
            $stok = $currentFaskesStock[$obatId] ?? 0;
            // If stock is below 2 months need, request restock
            if ($stok < $avg * 2 || $isInitial) {
                $obatIds[] = $obatId;
            }
        }

        // Limit to random subset
        shuffle($obatIds);
        $obatIds = array_slice($obatIds, 0, random_int(8, 20));
        if (empty($obatIds)) {
            return;
        }

        $adminGudang = $this->users['sys_admin_gudang@mail.com'];
        $adminDinas = $this->users['sys_admin_dinas@mail.com'];
        $tanggalKirim = (clone $date)->addDays(random_int(10, 25));

        // Create the distribution header
        $distribusi = DistribusiObat::create([
            'nomor_surat_jalan' => 'SJ/'.$date->format('Y/m').'/'.str_pad((string) $this->counterDistribusi++, 4, '0', STR_PAD_LEFT),
            'permintaan_id' => null,
            'tipe_distribusi' => $tipe,
            'fasilitas_pengirim_id' => $pengirimId,
            'fasilitas_penerima_id' => $penerimaId,
            'status' => 'diterima',
            'tanggal_kirim' => $tanggalKirim,
            'tanggal_terima' => (clone $tanggalKirim)->addDays(random_int(1, 2)),
            'pengirim_id' => $pengirimId ? ($this->users[$pengirimId]?->id ?? $adminGudang->id) : $adminGudang->id,
            'penerima_id' => $this->users[$penerimaId]?->id ?? $adminDinas->id,
            'catatan' => null,
        ]);

        $sumberDanaAlokasi = [];

        foreach ($obatIds as $obatId) {
            // Determine qty to send based on estimated need
            $avgCons = $this->getAvgConsumption($obatId, $penerimaId);
            $currentStock = $this->faskesStock[$penerimaId][$obatId] ?? 0;
            // Send 1.5-3 months of supply minus current stock
            $qty = max(50, (int) ($avgCons * random_int(15, 30) / 10) - $currentStock);

            // Check source stock
            $sourceStock = $pengirimId === null
                ? ($this->gudangStock[$obatId] ?? 0)
                : ($this->faskesStock[$pengirimId][$obatId] ?? 0);

            if ($qty > $sourceStock) {
                $qty = max(0, $sourceStock);
            }
            if ($qty <= 0) {
                continue;
            }

            // Deduct from source
            if ($pengirimId === null) {
                // From gudang
                $stokSebelum = $this->gudangStock[$obatId] ?? 0;
                $this->gudangStock[$obatId] = $stokSebelum - $qty;

                // Pick batch
                $batchId = $this->deductGudangBatch($obatId, $qty);

                // Add to faskes stock
                $this->faskesStock[$penerimaId][$obatId] = ($this->faskesStock[$penerimaId][$obatId] ?? 0) + $qty;

                // Track batch at faskes
                if ($batchId) {
                    $this->batchByFaskes[$penerimaId][$obatId][] = [
                        'batch_id' => $batchId,
                        'jumlah' => $qty,
                    ];
                }

                RiwayatStok::create([
                    'fasilitas_id' => null,
                    'obat_id' => $obatId,
                    'tipe' => 'distribusi_keluar',
                    'jumlah' => -$qty,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSebelum - $qty,
                    'referensi_type' => DistribusiObat::class,
                    'referensi_id' => $distribusi->id,
                    'user_id' => $adminGudang->id,
                    'keterangan' => 'Distribusi ke faskes',
                    'tanggal' => $tanggalKirim,
                ]);
            } else {
                // From puskesmas to pustu
                $stokSebelum = $this->faskesStock[$pengirimId][$obatId] ?? 0;
                $this->faskesStock[$pengirimId][$obatId] = $stokSebelum - $qty;
                $this->faskesStock[$penerimaId][$obatId] = ($this->faskesStock[$penerimaId][$obatId] ?? 0) + $qty;

                // Move batch
                $batchId = $this->deductFacilityBatch($pengirimId, $obatId, $qty);
                if ($batchId) {
                    $this->batchByFaskes[$penerimaId][$obatId][] = [
                        'batch_id' => $batchId,
                        'jumlah' => $qty,
                    ];
                }

                RiwayatStok::create([
                    'fasilitas_id' => $pengirimId,
                    'obat_id' => $obatId,
                    'tipe' => 'distribusi_keluar',
                    'jumlah' => -$qty,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSebelum - $qty,
                    'referensi_type' => DistribusiObat::class,
                    'referensi_id' => $distribusi->id,
                    'user_id' => $this->users[$pengirimId]->id ?? $adminGudang->id,
                    'keterangan' => 'Distribusi ke '.($this->pustu->firstWhere('id', $penerimaId)?->nama ?? 'Pustu'),
                    'tanggal' => $tanggalKirim,
                ]);
            }

            // Riwayat masuk untuk penerima
            $stokPenerimaSebelum = ($this->faskesStock[$penerimaId][$obatId] ?? 0) - $qty;

            RiwayatStok::create([
                'fasilitas_id' => $penerimaId,
                'obat_id' => $obatId,
                'tipe' => 'distribusi_masuk',
                'jumlah' => $qty,
                'stok_sebelum' => $stokPenerimaSebelum,
                'stok_sesudah' => $stokPenerimaSebelum + $qty,
                'referensi_type' => DistribusiObat::class,
                'referensi_id' => $distribusi->id,
                'user_id' => $this->users[$penerimaId]?->id ?? $adminDinas->id,
                'keterangan' => 'Penerimaan distribusi',
                'tanggal' => $tanggalKirim,
            ]);

            // Detail distribusi
            $finalBatchId = $batchId ?? 0;
            if ($finalBatchId <= 0) {
                $finalBatchId = BatchStok::where('obat_id', $obatId)
                    ->where('fasilitas_id', $pengirimId)
                    ->value('id') ?? 0;
            }
            if ($finalBatchId <= 0) {
                continue;
            }
            DetailDistribusiObat::create([
                'distribusi_id' => $distribusi->id,
                'obat_id' => $obatId,
                'batch_id' => $finalBatchId,
                'jumlah' => $qty,
            ]);

            // Update batch_stok for faskes if from gudang
            if ($pengirimId === null && $finalBatchId > 0) {
                $batch = BatchStok::find($finalBatchId);
                if ($batch) {
                    BatchStok::create([
                        'penerimaan_id' => $batch->penerimaan_id,
                        'fasilitas_id' => $penerimaId,
                        'sumber_dana_id' => $batch->sumber_dana_id, // carry funding source from source batch
                        'obat_id' => $obatId,
                        'batch_number' => $batch->batch_number,
                        'tanggal_expired' => $batch->tanggal_expired,
                        'jumlah' => $qty,
                        'status' => 'tersedia',
                        'tanggal_masuk' => $tanggalKirim,
                        'harga_beli' => $batch->harga_beli,
                    ]);
                }
            }

            // Akumulasi alokasi dana per sumber dana
            if ($finalBatchId > 0) {
                $alokasiBatch = BatchStok::find($finalBatchId);
                if ($alokasiBatch && $alokasiBatch->sumber_dana_id) {
                    $sdId = $alokasiBatch->sumber_dana_id;
                    $harga = (float) ($alokasiBatch->harga_beli ?: $this->estimateHarga($obatId));
                    $biaya = $harga * $qty;

                    if (! isset($sumberDanaAlokasi[$sdId])) {
                        $sumberDanaAlokasi[$sdId] = ['jumlah_obat' => 0, 'total_biaya' => 0.0];
                    }
                    $sumberDanaAlokasi[$sdId]['jumlah_obat']++;
                    $sumberDanaAlokasi[$sdId]['total_biaya'] += $biaya;
                }
            }
        }

        // Catat alokasi dana ke faskes
        foreach ($sumberDanaAlokasi as $sdId => $data) {
            SumberDanaPenggunaan::create([
                'sumber_dana_id' => $sdId,
                'rko_id' => null,
                'fasilitas_id' => $penerimaId,
                'tipe' => 'alokasi',
                'jumlah_obat' => $data['jumlah_obat'],
                'total_biaya' => $data['total_biaya'],
                'tanggal' => $tanggalKirim,
                'keterangan' => 'Alokasi distribusi ke '.($faskes->nama ?? 'Unknown').' ('.$tipe.')',
            ]);
        }

        $this->stats['distribusi']++;
    }

    private function deductGudangBatch(int $obatId, int $jumlah): ?int
    {
        $batches = &$this->batchByGudang[$obatId];
        if (empty($batches)) {
            return null;
        }

        // Sort: oldest batches first (FIFO approach)
        usort($batches, fn ($a, $b) => $a['batch_id'] <=> $b['batch_id']);

        $remaining = $jumlah;
        $lastBatchId = null;

        foreach ($batches as &$batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($batch['jumlah'], $remaining);
            $batch['jumlah'] -= $take;
            $remaining -= $take;
            $lastBatchId = $batch['batch_id'];
        }

        // Remove empty batches
        $batches = array_filter($batches, fn ($b) => $b['jumlah'] > 0);
        // Re-index
        $batches = array_values($batches);

        return $lastBatchId;
    }

    private function deductFacilityBatch(int $fasilitasId, int $obatId, int $jumlah): ?int
    {
        $batches = &$this->batchByFaskes[$fasilitasId][$obatId];
        if (empty($batches)) {
            return null;
        }

        usort($batches, fn ($a, $b) => $a['batch_id'] <=> $b['batch_id']); // FIFO

        $remaining = $jumlah;
        $lastBatchId = null;

        foreach ($batches as &$batch) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($batch['jumlah'], $remaining);
            $batch['jumlah'] -= $take;
            $remaining -= $take;
            $lastBatchId = $batch['batch_id'];
        }

        $batches = array_values(array_filter($batches, fn ($b) => $b['jumlah'] > 0));

        return $lastBatchId;
    }

    // =======================================================================
    //  5. LPLPO (Monthly Reports)
    // =======================================================================

    private function generateAllLPLPO(Carbon $date): void
    {
        foreach ($this->puskesmas as $puskesmas) {
            $this->generateLPLPO($puskesmas->id, $date);
        }
        foreach ($this->pustu as $pustu) {
            $this->generateLPLPO($pustu->id, $date);
        }
    }

    private function generateLPLPO(int $fasilitasId, Carbon $date): void
    {
        $user = $this->users[$fasilitasId] ?? $this->users['sys_admin_gudang@mail.com'];

        $tglBuat = (clone $date)->endOfMonth();

        $lplpo = LaporanLplpo::create([
            'nomor_laporan' => 'LPLPO/'.$date->format('Y/m').'/'.str_pad((string) $this->counterLPLPO++, 4, '0', STR_PAD_LEFT),
            'fasilitas_id' => $fasilitasId,
            'periode_bulan' => $date->month,
            'periode_tahun' => $date->year,
            'status' => 'selesai',
            'tanggal_pembuatan' => $tglBuat,
            'dibuat_oleh' => $user->id,
            'catatan' => null,
        ]);

        // Add detail for drugs that had stock movement
        $drugsWithMovement = $this->obatList->random(min(random_int(15, 30), $this->obatList->count()));
        $monthStart = (clone $date)->startOfMonth();
        $monthEnd = (clone $date)->endOfMonth();

        foreach ($drugsWithMovement as $obat) {
            $stokAwal = (int) ($this->faskesStockStartOfMonth[$fasilitasId][$obat->id]
                ?? $this->faskesStock[$fasilitasId][$obat->id]
                ?? random_int(50, 500));

            // Query actual stock movements for this month
            $masuk = (int) RiwayatStok::query()
                ->where('fasilitas_id', $fasilitasId)
                ->where('obat_id', $obat->id)
                ->whereIn('tipe', ['masuk', 'distribusi_masuk'])
                ->whereBetween('tanggal', [$monthStart, $monthEnd])
                ->sum('jumlah');

            $keluar = (int) RiwayatStok::query()
                ->where('fasilitas_id', $fasilitasId)
                ->where('obat_id', $obat->id)
                ->whereIn('tipe', ['keluar', 'distribusi_keluar', 'rusak', 'expired', 'penyesuaian', 'hilang'])
                ->where('jumlah', '<', 0)
                ->whereBetween('tanggal', [$monthStart, $monthEnd])
                ->sum('jumlah');
            $keluar = abs($keluar);

            $sisa = $stokAwal + $masuk - $keluar;
            $permintaanLanjut = max(50, $keluar + random_int(10, 50));
            $stokOptimum = max(50, (int) ($this->getAvgConsumption($obat->id, $fasilitasId) * 3));

            DetailLplpo::create([
                'lplpo_id' => $lplpo->id,
                'obat_id' => $obat->id,
                'stok_awal' => $stokAwal,
                'jumlah_masuk' => $masuk,
                'jumlah_keluar' => $keluar,
                'sisa_stok' => $sisa,
                'stok_optimum' => $stokOptimum,
                'permintaan_selanjutnya' => $permintaanLanjut,
                'sudah_diminta' => random_int(0, 1),
                'permintaan_id' => null,
                'keterangan' => null,
            ]);
        }

        $this->stats['lplpo']++;
    }

    // =======================================================================
    //  6. RKO (Annual Plans — generated in December for next year)
    // =======================================================================

    private function generateAllRKO(Carbon $date): void
    {
        $tahunRko = $date->year + 1;

        foreach ($this->puskesmas as $puskesmas) {
            $this->generateRKO($puskesmas->id, $tahunRko, $date);
        }
    }

    private function generateRKO(int $fasilitasId, int $tahunRko, Carbon $date): void
    {
        $user = $this->users[$fasilitasId] ?? $this->users['sys_admin_gudang@mail.com'];
        $adminDinas = $this->users['sys_admin_dinas@mail.com'];
        $tglBuat = (clone $date)->endOfMonth();

        $tahunIni = $date->year;
        $startYear = Carbon::create($tahunIni, 1, 1, 0, 0, 0);
        $endYear = Carbon::create($tahunIni, 12, 31, 23, 59, 59);

        // Determine drugs to include
        $obatIds = [];
        foreach (self::KONSUMSI as $obatId => $_) {
            $avg = $this->getAvgConsumption($obatId, $fasilitasId);

            $pemakaianTahunIni = (int) RiwayatStok::query()
                ->where('fasilitas_id', $fasilitasId)
                ->where('obat_id', $obatId)
                ->whereIn('tipe', ['keluar', 'distribusi_keluar', 'rusak', 'expired', 'penyesuaian', 'hilang'])
                ->where('jumlah', '<', 0)
                ->whereBetween('tanggal', [$startYear, $endYear])
                ->sum('jumlah');
            $pemakaianTahunIni = abs($pemakaianTahunIni);

            if ($pemakaianTahunIni > 0 || $avg > 0) {
                $obatIds[] = $obatId;
            }
        }

        if (empty($obatIds)) {
            return;
        }

        shuffle($obatIds);
        $obatIds = array_slice($obatIds, 0, min(count($obatIds), random_int(20, 40)));

        $lplpoReferensi = LaporanLplpo::where('fasilitas_id', $fasilitasId)
            ->where('periode_tahun', $tahunIni)
            ->latest()
            ->first();

        $rko = LaporanRko::create([
            'nomor_rko' => 'RKO/'.$date->format('Y/m').'/'.str_pad((string) $this->counterRKO++, 4, '0', STR_PAD_LEFT),
            'fasilitas_id' => $fasilitasId,
            'periode_tahun' => $tahunRko,
            'status' => 'disetujui',
            'tanggal_pembuatan' => $tglBuat,
            'tanggal_pengajuan' => $tglBuat,
            'tanggal_disetujui' => (clone $tglBuat)->addDays(random_int(1, 5)),
            'dibuat_oleh' => $user->id,
            'disetujui_oleh' => $adminDinas->id,
            'catatan' => null,
        ]);

        $totalAnggaran = 0;
        $drugCount = 0;

        foreach ($obatIds as $obatId) {
            $obat = $this->obatList->get($obatId);
            if (! $obat) {
                continue;
            }

            $avg = $this->getAvgConsumption($obatId, $fasilitasId);

            // Actual usage in current year
            $pemakaianTahunLalu = (int) RiwayatStok::query()
                ->where('fasilitas_id', $fasilitasId)
                ->where('obat_id', $obatId)
                ->whereIn('tipe', ['keluar', 'distribusi_keluar', 'rusak', 'expired', 'penyesuaian', 'hilang'])
                ->where('jumlah', '<', 0)
                ->whereBetween('tanggal', [$startYear, $endYear])
                ->sum('jumlah');
            $pemakaianTahunLalu = abs($pemakaianTahunLalu);

            // Count distinct months with actual usage data
            $bulanAktif = (int) DB::table('riwayat_stok')
                ->where('fasilitas_id', $fasilitasId)
                ->where('obat_id', $obatId)
                ->whereIn('tipe', ['keluar', 'distribusi_keluar'])
                ->where('jumlah', '<', 0)
                ->whereBetween('tanggal', [$startYear, $endYear])
                ->selectRaw('COUNT(DISTINCT DATE_FORMAT(tanggal, \'%Y-%m\')) as cnt')
                ->value('cnt') ?? 0;

            if ($pemakaianTahunLalu > 0 && $bulanAktif > 0) {
                $rataRataBulanan = (int) round($pemakaianTahunLalu / $bulanAktif);
            } else {
                $rataRataBulanan = $avg;
            }

            $stokAkhir = $this->faskesStock[$fasilitasId][$obatId] ?? 0;
            $hargaPerkiraan = (float) ($obat->harga_satuan ?: $this->estimateHarga($obat->id));

            // VEN kategori
            $venKategori = $obat->ven_kategori;
            if (! in_array($venKategori, ['V', 'E', 'N'])) {
                $venKategori = match (true) {
                    in_array($obatId, [26, 42]) => 'V',
                    in_array($obatId, [44, 11, 7, 6, 39, 34, 37, 28, 41]) => 'E',
                    default => 'N',
                };
            }

            $bufferPersen = match ($venKategori) {
                'V' => 30,
                'E' => 20,
                'N' => 10,
                default => 15,
            };

            $kebutuhanTahunan = $rataRataBulanan * 18;
            $rencanaKebutuhan = max(0, $kebutuhanTahunan - $stokAkhir);
            $bufferQty = (int) round($rencanaKebutuhan * $bufferPersen / 100);
            $totalKebutuhan = $rencanaKebutuhan + $bufferQty;
            $usulan = $totalKebutuhan;
            $totalHarga = $usulan * $hargaPerkiraan;
            $totalAnggaran += $totalHarga;
            $drugCount++;

            DetailRko::create([
                'rko_id' => $rko->id,
                'obat_id' => $obatId,
                'pemakaian_tahun_sebelumnya' => (int) $pemakaianTahunLalu,
                'rata_rata_pemakaian_bulanan' => $rataRataBulanan,
                'stok_akhir' => $stokAkhir,
                'kebutuhan_tahunan' => $kebutuhanTahunan,
                'rencana_kebutuhan' => $rencanaKebutuhan,
                'buffer_stock_persen' => $bufferPersen,
                'buffer_stok_qty' => $bufferQty,
                'total_kebutuhan' => $totalKebutuhan,
                'usulan' => $usulan,
                'harga_perkiraan' => $hargaPerkiraan,
                'total_harga' => $totalHarga,
                'ven_kategori' => $venKategori,
                'abc_kategori' => null,
                'lplpo_referensi_id' => $lplpoReferensi?->id,
                'prediksi_id' => null,
                'keterangan' => null,
            ]);
        }

        $rko->update(['total_anggaran' => $totalAnggaran]);

        if ($totalAnggaran > 0 && $drugCount > 0) {
            SumberDanaPenggunaan::create([
                'sumber_dana_id' => $this->pickSumberDana($tahunRko)->id,
                'rko_id' => $rko->id,
                'fasilitas_id' => $fasilitasId,
                'tipe' => 'alokasi',
                'jumlah_obat' => $drugCount,
                'total_biaya' => $totalAnggaran,
                'tanggal' => $tglBuat,
                'keterangan' => 'RKO tahun '.$tahunRko.' untuk '.($this->puskesmas->firstWhere('id', $fasilitasId)?->nama ?? 'Puskesmas'),
            ]);
        }

        $this->stats['rko']++;
    }

    // =======================================================================
    //  7. RETUR OBAT (Every 3 months)
    // =======================================================================

    private function generateAllRetur(Carbon $date): void
    {
        // Returns from puskesmas to dinas
        foreach ($this->puskesmas as $puskesmas) {
            if (random_int(1, 100) > 30) {
                continue; // 30% chance per puskesmas per quarter
            }
            $this->generateRetur($puskesmas->id, null, 'puskesmas_ke_gudang', $date);
        }
    }

    private function generateRetur(int $pengirimId, ?int $penerimaId, string $tipeRetur, Carbon $date): void
    {
        $alasanList = ['expired', 'rusak', 'kelebihan_stok', 'near_expiry'];
        $alasan = $alasanList[array_rand($alasanList)];

        $tipeRiwayat = match ($alasan) {
            'rusak' => 'rusak',
            'expired' => 'expired',
            default => 'penyesuaian',
        };

        $isReturnToGudang = in_array($alasan, ['expired', 'kelebihan_stok', 'near_expiry'], true);

        $adminDinas = $this->users['sys_admin_dinas@mail.com'];
        $petugas = $this->users[$pengirimId] ?? $adminDinas;

        $faskes = $this->puskesmas->firstWhere('id', $pengirimId)
            ?? $this->pustu->firstWhere('id', $pengirimId);

        $tglRetur = (clone $date)->addDays(random_int(1, 15));

        $retur = ReturObat::create([
            'nomor_retur' => ReturObat::generateNomorRetur($pengirimId, $tglRetur->format('Y-m-d')),
            'distribusi_id' => null,
            'fasilitas_pengirim_id' => $pengirimId,
            'fasilitas_penerima_id' => $penerimaId,
            'tipe_retur' => $tipeRetur,
            'alasan' => $alasan,
            'alasan_lainnya' => null,
            'status' => 'selesai',
            'tanggal_retur' => $tglRetur,
            'tanggal_disetujui' => (clone $date)->addDays(random_int(2, 5)),
            'tanggal_ditolak' => null,
            'tanggal_dikirim' => (clone $date)->addDays(random_int(3, 7)),
            'tanggal_diterima' => (clone $date)->addDays(random_int(5, 10)),
            'disetujui_oleh' => $adminDinas->id,
            'catatan' => 'Retur '.str_replace('_', ' ', $alasan),
        ]);

        // Pick 1-3 drugs to return
        $returnCount = random_int(1, 3);
        $selectedObat = $this->obatList->random(min($returnCount, $this->obatList->count()));

        foreach ($selectedObat as $obat) {
            $currentStock = $this->faskesStock[$pengirimId][$obat->id] ?? 0;

            $maxQty = match ($alasan) {
                'expired' => min(50, $currentStock),
                'rusak' => min(20, $currentStock),
                'kelebihan_stok' => min(100, $currentStock),
                'near_expiry' => min(50, $currentStock),
                default => min(30, $currentStock),
            };
            $minQty = match ($alasan) {
                'rusak' => 2,
                default => 5,
            };

            if ($maxQty < $minQty) {
                continue;
            }

            $qty = random_int($minQty, $maxQty);

            if ($qty <= 0) {
                continue;
            }

            // Deduct from facility stock
            $this->faskesStock[$pengirimId][$obat->id] = $currentStock - $qty;

            // Pick a batch
            $batchId = $this->deductFacilityBatch($pengirimId, $obat->id, $qty);

            DetailReturObat::create([
                'retur_id' => $retur->id,
                'obat_id' => $obat->id,
                'batch_id' => $batchId ?? 0,
                'jumlah_retur' => $qty,
                'bukti_foto' => null,
                'catatan' => 'Retur '.str_replace('_', ' ', $alasan),
            ]);

            // Riwayat stok keluar dari faskes
            RiwayatStok::create([
                'fasilitas_id' => $pengirimId,
                'obat_id' => $obat->id,
                'tipe' => $tipeRiwayat,
                'jumlah' => -$qty,
                'stok_sebelum' => $currentStock,
                'stok_sesudah' => $currentStock - $qty,
                'referensi_type' => ReturObat::class,
                'referensi_id' => $retur->id,
                'user_id' => $petugas->id,
                'keterangan' => 'Retur: '.str_replace('_', ' ', $alasan),
                'tanggal' => $retur->tanggal_retur,
            ]);

            // Untuk retur non-rusak, stok kembali ke gudang
            if ($isReturnToGudang && $qty > 0) {
                $stokSebelumGudang = $this->gudangStock[$obat->id] ?? 0;
                $this->gudangStock[$obat->id] = $stokSebelumGudang + $qty;

                $sumberDanaRetur = $batchId
                    ? optional(BatchStok::find($batchId))->sumber_dana_id
                    : null;

                BatchStok::create([
                    'penerimaan_id' => null,
                    'fasilitas_id' => null,
                    'sumber_dana_id' => $sumberDanaRetur,
                    'obat_id' => $obat->id,
                    'batch_number' => 'RTN/'.$date->format('Y/m').'/'.strtoupper(substr($obat->kode_obat ?? '', -4)).'/'.str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT),
                    'tanggal_expired' => now()->addMonths(random_int(6, 12)),
                    'jumlah' => $qty,
                    'status' => 'tersedia',
                    'tanggal_masuk' => $retur->tanggal_retur,
                    'harga_beli' => 0,
                ]);

                RiwayatStok::create([
                    'fasilitas_id' => null,
                    'obat_id' => $obat->id,
                    'tipe' => 'masuk',
                    'jumlah' => $qty,
                    'stok_sebelum' => $stokSebelumGudang,
                    'stok_sesudah' => $stokSebelumGudang + $qty,
                    'referensi_type' => ReturObat::class,
                    'referensi_id' => $retur->id,
                    'user_id' => $adminDinas->id,
                    'keterangan' => 'Retur masuk dari '.($faskes->nama ?? 'faskes').' ('.str_replace('_', ' ', $alasan).')',
                    'tanggal' => $retur->tanggal_retur,
                ]);
            }
        }

        $this->stats['retur']++;
    }

    // =======================================================================
    //  FINAL PERSIST
    // =======================================================================

    private function persistFinalStock(): void
    {
        // Update gudang stock (use updateOrCreate to mirror stok_faskes pattern; preserve stok_minimum from StokGudangSeeder)
        foreach ($this->gudangStock as $obatId => $jumlah) {
            StokGudang::updateOrCreate(
                ['obat_id' => $obatId],
                ['jumlah' => max(0, $jumlah)],
            );
        }

        // Update/create faskes stock
        foreach ($this->faskesStock as $fasilitasId => $stocks) {
            foreach ($stocks as $obatId => $jumlah) {
                if ($jumlah <= 0) {
                    continue;
                }

                StokFaskes::updateOrCreate(
                    [
                        'fasilitas_id' => $fasilitasId,
                        'obat_id' => $obatId,
                    ],
                    [
                        'jumlah' => max(0, $jumlah),
                        'stok_minimum' => $this->getStokMinimum($obatId, $fasilitasId),
                    ],
                );
            }
        }
    }

    private function printSummaryTable(int $totalMonths, float $duration, bool $dryRun): void
    {
        $this->command?->info('Ringkasan Simulasi:');
        $this->command?->newLine();

        $totalPemakaian = $this->stats['pemakaian'];
        $totalRetur = $this->stats['retur'];
        $totalLplpo = $this->stats['lplpo'];
        $totalRko = $this->stats['rko'];
        $totalPenerimaan = $this->counterPenerimaan - 1;
        $totalDistribusi = $this->counterDistribusi - 1;
        $totalPermintaan = $this->counterPermintaan - 1;

        $this->command->table(
            ['Metrik', 'Nilai'],
            [
                ['Bulan disimulasikan', $totalMonths],
                ['Total penerimaan stok', $totalPenerimaan],
                ['Total distribusi obat', $totalDistribusi],
                ['Total pemakaian obat', number_format($totalPemakaian)],
                ['Total permintaan obat', $totalPermintaan],
                ['Total LPLPO', $totalLplpo],
                ['Total RKO', $totalRko],
                ['Total retur obat', $totalRetur],
                ['Waktu eksekusi', "{$duration}s"],
                ['Mode', $dryRun ? 'Dry Run (rolled back)' : 'Live'],
            ],
        );
    }
}
