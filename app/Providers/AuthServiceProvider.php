<?php

namespace App\Providers;

use App\Models\LaporanLplpo;
use App\Models\LaporanRko;
use App\Models\NeracaTahunan;
use App\Models\OpnameStok;
use App\Models\PenerimaanStok;
use App\Models\Supplier;
use App\Policies\LaporanLplpoPolicy;
use App\Policies\LaporanRkoPolicy;
use App\Policies\NeracaTahunanPolicy;
use App\Policies\OpnameStokPolicy;
use App\Policies\PemakaianObatPolicy;
use App\Policies\PenerimaanStokPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SumberDanaPolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        Supplier::class => SupplierPolicy::class,
        SumberDana::class => SumberDanaPolicy::class,
        OpnameStok::class => OpnameStokPolicy::class,
        PemakaianObat::class => PemakaianObatPolicy::class,
        PenerimaanStok::class => PenerimaanStokPolicy::class,
        LaporanLplpo::class => LaporanLplpoPolicy::class,
        LaporanRko::class => LaporanRkoPolicy::class,
        NeracaTahunan::class => NeracaTahunanPolicy::class,
        ModelPrediksi::class => ModelPrediksiPolicy::class,
        PrediksiKebutuhan::class => PrediksiKebutuhanPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
