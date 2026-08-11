<?php

namespace App\Services;

use App\Models\DistribusiObat;
use App\Models\Obat;
use App\Models\OpnameStok;
use App\Models\PenerimaanStok;
use App\Models\PermintaanObat;
use App\Models\ReturObat;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    /**
     * Fluent builder state.
     */
    private ?string $logName = null;

    private mixed $subject = null;

    private mixed $causer = null;

    private array $properties = [];

    private ?string $event = null;

    private ?string $batchUuid = null;

    // ────────────────────────────────────────────────────────────
    //  Fluent Interface (wraps spatie/laravel-activitylog)
    // ────────────────────────────────────────────────────────────

    public function inLog(?string $logName): self
    {
        $this->logName = $logName;

        return $this;
    }

    public function on(mixed $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function by(mixed $causer): self
    {
        $this->causer = $causer;

        return $this;
    }

    public function with(array $properties): self
    {
        $this->properties = $properties;

        return $this;
    }

    public function event(?string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function batch(?string $uuid = null): self
    {
        $this->batchUuid = $uuid;

        return $this;
    }

    /**
     * Log the activity with the accumulated fluent state.
     */
    public function log(string $description): Activity
    {
        $instance = activity($this->logName);

        if ($this->subject !== null) {
            $instance->performedOn($this->subject);
        }

        if ($this->causer !== null) {
            $instance->causedBy($this->causer);
        }

        if ($this->properties !== []) {
            $instance->withProperties($this->properties);
        }

        if ($this->event !== null) {
            $instance->event($this->event);
        }

        if ($this->batchUuid !== null) {
            $instance->useLog($this->logName ?? config('activitylog.default_log_name', 'default'));
        }

        $this->reset();

        return $instance->log($description);
    }

    /**
     * Reset fluent state after each log call.
     */
    private function reset(): void
    {
        $this->logName = null;
        $this->subject = null;
        $this->causer = null;
        $this->properties = [];
        $this->event = null;
        $this->batchUuid = null;
    }

    // ────────────────────────────────────────────────────────────
    //  Generic CRUD shortcuts
    // ────────────────────────────────────────────────────────────

    public function created(mixed $subject, ?User $user = null, ?string $description = null): Activity
    {
        return $this->fluentCrud('created', $subject, $user, $description);
    }

    public function updated(mixed $subject, ?User $user = null, array $changes = [], ?string $description = null): Activity
    {
        return $this->fluentCrud('updated', $subject, $user, $description, $changes);
    }

    public function deleted(mixed $subject, ?User $user = null, ?string $description = null): Activity
    {
        return $this->fluentCrud('deleted', $subject, $user, $description);
    }

    public function restored(mixed $subject, ?User $user = null, ?string $description = null): Activity
    {
        return $this->fluentCrud('restored', $subject, $user, $description);
    }

    private function fluentCrud(
        string $event,
        mixed $subject,
        ?User $user = null,
        ?string $description = null,
        array $changes = [],
    ): Activity {
        $modelName = class_basename($subject);
        $desc = $description ?? "{$event} {$modelName} #{$subject->getKey()}";

        $logName = method_exists($subject, 'getActivitylogName')
            ? $subject->getActivitylogName()
            : 'master_data';

        $activity = $this
            ->inLog($logName)
            ->on($subject)
            ->by($user ?? request()?->user())
            ->event($event);

        if ($changes !== []) {
            $activity->with($changes);
        }

        return $activity->log($desc);
    }

    // ────────────────────────────────────────────────────────────
    //  Authentication
    // ────────────────────────────────────────────────────────────

    public function userLogin(User $user): Activity
    {
        return $this
            ->inLog('auth')
            ->by($user)
            ->event('login')
            ->log("User {$user->email} login ke sistem");
    }

    public function userLogout(User $user): Activity
    {
        return $this
            ->inLog('auth')
            ->by($user)
            ->event('logout')
            ->log("User {$user->email} logout dari sistem");
    }

    public function failedLogin(string $email, ?string $ip = null): Activity
    {
        $props = [];
        if ($ip !== null) {
            $props['ip'] = $ip;
        }

        return $this
            ->inLog('auth')
            ->event('failed_login')
            ->with($props)
            ->log("Gagal login dengan email {$email}");
    }

    // ────────────────────────────────────────────────────────────
    //  Master Data — Obat
    // ────────────────────────────────────────────────────────────

    public function createdObat(Obat $obat, User $user): Activity
    {
        return $this
            ->inLog('master_data')
            ->on($obat)
            ->by($user)
            ->event('created')
            ->with([
                'kode_obat' => $obat->kode_obat,
                'nama_obat' => $obat->nama_obat,
            ])
            ->log("Tambah obat {$obat->nama_obat} ({$obat->kode_obat})");
    }

    public function updatedObat(Obat $obat, User $user, array $changes = []): Activity
    {
        return $this
            ->inLog('master_data')
            ->on($obat)
            ->by($user)
            ->event('updated')
            ->with(array_filter([
                'kode_obat' => $obat->kode_obat,
                'nama_obat' => $obat->nama_obat,
                'changes' => $changes,
            ]))
            ->log("Ubah obat {$obat->nama_obat} ({$obat->kode_obat})");
    }

    public function deletedObat(Obat $obat, User $user): Activity
    {
        return $this
            ->inLog('master_data')
            ->on($obat)
            ->by($user)
            ->event('deleted')
            ->with([
                'kode_obat' => $obat->kode_obat,
                'nama_obat' => $obat->nama_obat,
            ])
            ->log("Hapus obat {$obat->nama_obat} ({$obat->kode_obat})");
    }

    // ────────────────────────────────────────────────────────────
    //  Permintaan Obat (Request Flow: pustu → puskesmas → dinas)
    // ────────────────────────────────────────────────────────────

    public function buatPermintaan(PermintaanObat $permintaan): Activity
    {
        return $this
            ->inLog('permintaan_obat')
            ->on($permintaan)
            ->by($permintaan->user)
            ->event('created')
            ->with([
                'nomor' => $permintaan->nomor_permintaan,
                'fasilitas' => $permintaan->fasilitas?->nama,
                'total_item' => $permintaan->details?->count(),
            ])
            ->log("Permintaan obat {$permintaan->nomor_permintaan} dibuat");
    }

    public function setujuiPermintaan(PermintaanObat $permintaan, ?string $catatan = null): Activity
    {
        $props = ['nomor' => $permintaan->nomor_permintaan];

        if ($catatan !== null) {
            $props['catatan'] = $catatan;
        }

        return $this
            ->inLog('permintaan_obat')
            ->on($permintaan)
            ->by(request()?->user())
            ->event('approved')
            ->with($props)
            ->log("Permintaan obat {$permintaan->nomor_permintaan} disetujui");
    }

    public function tolakPermintaan(PermintaanObat $permintaan, string $alasan): Activity
    {
        return $this
            ->inLog('permintaan_obat')
            ->on($permintaan)
            ->by(request()?->user())
            ->event('rejected')
            ->with([
                'nomor' => $permintaan->nomor_permintaan,
                'alasan' => $alasan,
            ])
            ->log("Permintaan obat {$permintaan->nomor_permintaan} ditolak: {$alasan}");
    }

    // ────────────────────────────────────────────────────────────
    //  Distribusi Obat
    // ────────────────────────────────────────────────────────────

    public function kirimDistribusi(DistribusiObat $distribusi): Activity
    {
        return $this
            ->inLog('distribusi_obat')
            ->on($distribusi)
            ->by(request()?->user())
            ->event('created')
            ->with([
                'nomor' => $distribusi->nomor_distribusi,
                'tujuan' => $distribusi->fasilitasTujuan?->nama,
                'total_item' => $distribusi->details?->count(),
            ])
            ->log("Distribusi obat {$distribusi->nomor_distribusi} dikirim ke {$distribusi->fasilitasTujuan?->nama}");
    }

    public function terimaDistribusi(DistribusiObat $distribusi, ?string $catatan = null): Activity
    {
        $props = [
            'nomor' => $distribusi->nomor_distribusi,
            'pengirim' => $distribusi->fasilitasAsal?->nama,
        ];

        if ($catatan !== null) {
            $props['catatan'] = $catatan;
        }

        return $this
            ->inLog('distribusi_obat')
            ->on($distribusi)
            ->by(request()?->user())
            ->event('received')
            ->with($props)
            ->log("Distribusi obat {$distribusi->nomor_distribusi} diterima");
    }

    // ────────────────────────────────────────────────────────────
    //  Retur Obat
    // ────────────────────────────────────────────────────────────

    public function buatRetur(ReturObat $retur): Activity
    {
        return $this
            ->inLog('retur_obat')
            ->on($retur)
            ->by(request()?->user())
            ->event('created')
            ->with([
                'nomor' => $retur->nomor_retur,
                'fasilitas' => $retur->fasilitas?->nama,
                'total_item' => $retur->details?->count(),
            ])
            ->log("Retur obat {$retur->nomor_retur} dibuat");
    }

    // ────────────────────────────────────────────────────────────
    //  Penerimaan Stok
    // ────────────────────────────────────────────────────────────

    public function buatPenerimaan(PenerimaanStok $penerimaan): Activity
    {
        return $this
            ->inLog('penerimaan_stok')
            ->on($penerimaan)
            ->by(request()?->user())
            ->event('created')
            ->with([
                'nomor' => $penerimaan->nomor_penerimaan,
                'supplier' => $penerimaan->supplier?->nama,
                'total_item' => $penerimaan->details?->count(),
            ])
            ->log("Penerimaan stok {$penerimaan->nomor_penerimaan} dari {$penerimaan->supplier?->nama}");
    }

    // ────────────────────────────────────────────────────────────
    //  Opname Stok
    // ────────────────────────────────────────────────────────────

    public function buatOpname(OpnameStok $opname): Activity
    {
        return $this
            ->inLog('opname_stok')
            ->on($opname)
            ->by(request()?->user())
            ->event('created')
            ->with([
                'nomor' => $opname->nomor_opname,
                'fasilitas' => $opname->fasilitas?->nama,
            ])
            ->log("Opname stok {$opname->nomor_opname} dimulai");
    }

    public function selesaiOpname(OpnameStok $opname): Activity
    {
        $totalSelisih = $opname->details?->sum('selisih') ?? 0;

        return $this
            ->inLog('opname_stok')
            ->on($opname)
            ->by(request()?->user())
            ->event('completed')
            ->with([
                'nomor' => $opname->nomor_opname,
                'fasilitas' => $opname->fasilitas?->nama,
                'total_selisih' => $totalSelisih,
            ])
            ->log("Opname stok {$opname->nomor_opname} selesai");
    }

    // ────────────────────────────────────────────────────────────
    //  Laporan
    // ────────────────────────────────────────────────────────────

    public function generateLaporan(string $tipe, mixed $laporan, User $user): Activity
    {
        $maps = [
            'lplpo' => ['log' => 'laporan_lplpo', 'label' => 'LPLPO'],
            'rko' => ['log' => 'laporan_rko', 'label' => 'RKO'],
            'neraca' => ['log' => 'laporan_neraca', 'label' => 'Neraca Tahunan'],
        ];

        $info = $maps[$tipe] ?? ['log' => 'laporan', 'label' => ucfirst($tipe)];

        return $this
            ->inLog($info['log'])
            ->by($user)
            ->event('generated')
            ->with([
                'tipe' => $tipe,
                'id' => $laporan->id,
            ])
            ->log("Laporan {$info['label']} #{$laporan->id} di-generate");
    }

    // ────────────────────────────────────────────────────────────
    //  User Management
    // ────────────────────────────────────────────────────────────

    public function createdUser(User $target, User $by): Activity
    {
        return $this
            ->inLog('user_management')
            ->on($target)
            ->by($by)
            ->event('created')
            ->with([
                'email' => $target->email,
                'name' => $target->name,
                'roles' => $target->getRoleNames()->toArray(),
            ])
            ->log("User {$target->name} ({$target->email}) dibuat oleh {$by->name}");
    }

    public function updatedUserRole(User $target, User $by, array $rolesBaru): Activity
    {
        return $this
            ->inLog('user_management')
            ->on($target)
            ->by($by)
            ->event('role_updated')
            ->with([
                'user' => $target->email,
                'roles_baru' => $rolesBaru,
            ])
            ->log("Role user {$target->email} diubah menjadi: ".implode(', ', $rolesBaru));
    }
}
