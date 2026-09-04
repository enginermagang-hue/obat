# ╔══════════════════════════════════════════════════════════════════════════════╗
# ║                     📋 COMPREHENSIVE PERMISSION REFERENCE                     ║
# ╚══════════════════════════════════════════════════════════════════════════════╝

**Source**: `database/seeders/RoleAndPermissionSeeder.php`
**Last Updated**: 2026-07-02
**Total Permissions**: 136 (128 CRUD + 8 Legacy/Special)
**Total Resources**: 30
**Roles Defined**: 5 (super_admin, admin_gudang, admin_dinas, puskesmas, pustu)

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                         📊 SUMMARY STATISTICS                               ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

┌──────────────────────────────────────┬────────┬─────────────────────────────────┐
│ Metric                               │ Value  │ Details                        │
├──────────────────────────────────────┼────────┼─────────────────────────────────┤
│ Standard CRUD Permissions            │ 128    │ 30 resources × 4 actions       │
│ Legacy/Special Permissions           │ 8      │ Non-CRUD permissions           │
│ Total Permissions                    │ 136    │ All defined permissions        │
│ Resource Groups                      │ 17     │ Logical categorization         │
│ Roles Defined                        │ 5      │ Hierarchical access levels     │
└──────────────────────────────────────┴────────┴─────────────────────────────────┘

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                   📋 COMPLETE PERMISSION MATRIX (C|R|U|D)                    ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

### ┌──────────────────────────────────────────────────────────────────────────────┐
### │ ✅ STANDARD CRUD RESOURCES (All permissions exist system-wide)              │
### └──────────────────────────────────────────────────────────────────────────────┘

╔═════════════════╦═══╦═══╦═══╦═══╗
║ Resource        ║ C ║ R ║ U ║ D ║
╠═════════════════╬═══╬═══╬═══╬═══╣
║ users           ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ roles           ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ permissions     ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ fasilitas_kesehatan ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ obat            ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ sumber_dana     ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ stok_gudang     ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ stok_faskes     ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ batch_stok      ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ laporan_lplpo   ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ permintaan_obat ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ distribusi_obat ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ riwayat_stok    ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ pemakaian_obat  ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ penerimaan_stok ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ laporan_rko     ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ neraca_tahunan  ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ sumber_dana_penggunaan ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ alokasi_dana    ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ suppliers       ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ opname_stok     ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ retur_obat      ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ inspeksi_retur  ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ model_prediksi  ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ prediksi_kebutuhan ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ import_data_historis ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ pengaturan_laporan ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ avatar_presets  ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ user_preferences║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
║ activity_logs   ║ ✅ ║ ✅ ║ ✅ ║ ✅ ║
╚═════════════════╩═══╩═══╩═══╩═══╝

**Note**: ✅ = Permission exists in database. Actual assignment to roles varies (see Role Matrix below).

---

### ┌──────────────────────────────────────────────────────────────────────────────┐
### │ ⚠️  SPECIAL/LEGACY PERMISSIONS (Non-CRUD naming or special cases)           │
### └──────────────────────────────────────────────────────────────────────────────┘

╔════════════════════════╦═══════════════════════╦═══╦═══╦═══╦═══╦═══════════════════════════════════╗
║ Permission             ║ Resource/Description ║ C ║ R ║ U ║ D ║ Assigned To                         ║
╠════════════════════════╬═══════════════════════╬═══╬═══╬═══╬═══╬═══════════════════════════════════╣
║ view_dashboard         ║ Dashboard page        ║ - ║ ✅ ║ - ║ - ║ All roles                           ║
║ manage_obat            ║ Legacy: full control  ║ - ║ ⚠️ ║ ⚠️ ║ ⚠️ ║ admin_gudang, admin_dinas            ║
║ view_laporan           ║ Legacy: view all      ║ - ║ ✅ ║ - ║ - ║ super_admin, admin_gudang           ║
║ manage_laporan         ║ Legacy: manage all    ║ ⚠️ ║ ⚠️ ║ ⚠️ ║ ⚠️ ║ super_admin, admin_gudang            ║
║ input_laporan          ║ Legacy: input         ║ ✅ ║ - ║ - ║ - ║ super_admin, admin_gudang, puskesmas,│
║                       ║                       ║   ║   ║   ║   ║ pustu                                ║
║ manage_pengaturan_pdf ║ Legacy: PDF config    ║ - ║ ✅ ║ - ║ - ║ admin_gudang, admin_dinas            ║
║ manage_pengaturan_nomor║ Legacy: nomor format  ║ - ║ ❌ ║ - ║ - ║ ❌ Not assigned (BUG)               ║
╚════════════════════════╩═══════════════════════╩═══╩═══╩═══╩═══╩═══════════════════════════════════╝

**Legend**:
  ✅ = Fully assigned
  ⚠️ = Partial/legacy (inconsistent with CRUD)
  ❌ = Missing/not assigned
  -  = Not applicable

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                    📊 PERMISSION COVERAGE BY ROLE                           ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

┌──────────────┬─────────────────┬─────────────────┬───────────┬──────────────────────────────┐
│ Role         │ Total Possible  │ Actually Assigned│ Coverage  │ Missing CRUD Resources       │
├──────────────┼─────────────────┼─────────────────┼───────────┼──────────────────────────────┤
│ super_admin  │ 136             │ 136             │ 100%      │ None                        │
│ admin_gudang │ 136             │ ~32             │ 23.5%     │ Most (uses legacy obat)     │
│ admin_dinas  │ 136             │ ~33             │ 24.3%     │ input_laporan, many CRUD    │
│ puskesmas    │ 136             │ ~34             │ 25.0%     │ Master data, settings       │
│ pustu        │ 136             │ ~25             │ 18.4%     │ Similar to puskesmas - less │
└──────────────┴─────────────────┴─────────────────┴───────────┴──────────────────────────────┘

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                    🗂️ PERMISSION GROUPS OVERVIEW                            ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

┌─────────────────────┬─────────────┬─────────────────┬──────────────┬──────────┬─────────┐
│ Group               │ Resources   │ CRUD Permissions│ Legacy/Spec. │ Total    │ % of    │
│                     │ Count       │ Count           │ Count        │ Count    │ Total   │
├─────────────────────┼─────────────┼─────────────────┼──────────────┼──────────┼─────────┤
│ User Management     │ 3           │ 12              │ 0            │ 12       │ 8.8%    │
│ Master Data         │ 3           │ 16              │ 2            │ 18       │ 13.2%   │
│ Inventory           │ 5           │ 20              │ 0            │ 20       │ 14.7%   │
│ Laporan             │ 3           │ 12              │ 3            │ 15       │ 11.0%   │
│ Permintaan          │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Distribusi          │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Pemakaian           │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Penerimaan          │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Retur Obat          │ 2           │ 8               │ 0            │ 8        │ 5.9%    │
│ Inspeksi Retur      │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Dana & Anggaran     │ 3           │ 12              │ 0            │ 12       │ 8.8%    │
│ Supplier            │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Opname Stok         │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ AI/Prediksi         │ 2           │ 8               │ 0            │ 8        │ 5.9%    │
│ Import              │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Settings            │ 1           │ 4               │ 3            │ 7        │ 5.1%    │
│ User Settings       │ 2           │ 8               │ 0            │ 8        │ 5.9%    │
│ Audit               │ 1           │ 4               │ 0            │ 4        │ 2.9%    │
│ Dashboard           │ 0           │ 0               │ 1            │ 1        │ 0.7%    │
├─────────────────────┴─────────────┴─────────────────┴──────────────┴──────────┼─────────┤
│ TOTAL               │ 30          │ 128             │ 8            │ 136      │ 100%    │
└──────────────────────────────────────────────────────────────────────────────────────┘

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                    🔐 PERMISSION DETAILS BY GROUP                            ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  1. 👥 USER MANAGEMENT (users, roles, permissions)                         ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═════════════════════╦═══════════╦═══════════╗
║ Permission          ║ Resource  ║ Action    ║
╠═════════════════════╬═══════════╬═══════════╣
║ view_users          ║ users     ║ view      ║
║ create_users        ║ users     ║ create    ║
║ update_users        ║ users     ║ update    ║
║ delete_users        ║ users     ║ delete    ║
║ view_roles          ║ roles     ║ view      ║
║ create_roles        ║ roles     ║ create    ║
║ update_roles        ║ roles     ║ update    ║
║ delete_roles        ║ roles     ║ delete    ║
║ view_permissions    ║ permissions║ view     ║
║ create_permissions  ║ permissions║ create   ║
║ update_permissions  ║ permissions║ update   ║
║ delete_permissions  ║ permissions║ delete   ║
╚═════════════════════╩═══════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — All 12 permissions
  ❌ Others — None

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  2. 💊 MASTER DATA (obat, fasilitas_kesehatan, sumber_dana)                ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔════════════════════════════╦═══════════════╦═══════════╦════════════════════─────╗
║ Permission                 ║ Resource      ║ Action    ║ Legacy Equivalent       ║
╠════════════════════════════╬═══════════════╬═══════════╬════════════════════─────╣
║ view_obat                  ║ obat          ║ view      ║ manage_obat ❌ (legacy)  ║
║ create_obat                ║ obat          ║ create    ║ -                        ║
║ update_obat                ║ obat          ║ update    ║ -                        ║
║ delete_obat                ║ obat          ║ delete    ║ -                        ║
║ view_fasilitas_kesehatan   ║ fasilitas...  ║ view      ║ -                        ║
║ create_fasilitas_kesehatan ║ fasilitas...  ║ create    ║ -                        ║
║ update_fasilitas_kesehatan ║ fasilitas...  ║ update    ║ -                        ║
║ delete_fasilitas_kesehatan ║ fasilitas...  ║ delete    ║ -                        ║
║ view_sumber_dana           ║ sumber_dana   ║ view      ║ -                        ║
║ create_sumber_dana         ║ sumber_dana   ║ create    ║ -                        ║
║ update_sumber_dana         ║ sumber_dana   ║ update    ║ -                        ║
║ delete_sumber_dana         ║ sumber_dana   ║ delete    ║ -                        ║
╚════════════════════════════╩═══════════════╩═══════════╩════════════════════─────╝

⚠️  LEGACY ISSUES:
  • manage_obat → admin_gudang & admin_dinas use legacy instead of CRUD
  • view_laporan, manage_laporan, input_laporan → ambiguous mapping

**Current Role Assignment**:
  ✅ super_admin — All 12
  ⚠️  admin_gudang — Only manage_obat (legacy), missing CRUD obat
  ⚠️  admin_dinas  — Only manage_obat (legacy), missing CRUD obat
  ❌ puskesmas    — None
  ❌ pustu        — None

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  3. 📦 INVENTORY (stok_gudang, stok_faskes, batch_stok, riwayat_stok)     ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════╦═════════════╦═══════════╗
║ Permission        ║ Resource    ║ Action    ║
╠═══════════════════╬═════════════╬═══════════╣
║ view_stok_gudang  ║ stok_gudang ║ view      ║
║ create_stok_gudang║ stok_gudang ║ create    ║
║ update_stok_gudang║ stok_gudang ║ update    ║
║ delete_stok_gudang║ stok_gudang ║ delete    ║
║ view_stok_faskes  ║ stok_faskes ║ view      ║
║ create_stok_faskes║ stok_faskes ║ create    ║
║ update_stok_faskes║ stok_faskes ║ update    ║
║ delete_stok_faskes║ stok_faskes ║ delete    ║
║ view_batch_stok   ║ batch_stok  ║ view      ║
║ create_batch_stok ║ batch_stok  ║ create    ║
║ update_batch_stok ║ batch_stok  ║ update    ║
║ delete_batch_stok ║ batch_stok  ║ delete    ║
║ view_riwayat_stok ║ riwayat_stok║ view      ║
║ create_riwayat... ║ riwayat_stok║ create    ║
║ update_riwayat... ║ riwayat_stok║ update    ║
║ delete_riwayat... ║ riwayat_stok║ delete    ║
╚═══════════════════╩═════════════╩═══════════╝

**Role Assignment**: Only super_admin has full CRUD. Others have partial view access.

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  4. 📈 LAPORAN (laporan_lplpo, laporan_rko, neraca_tahunan)               ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═════════════════════╦═══════════════╦═══════════╦════════════════════─────╗
║ Permission          ║ Resource      ║ Action    ║ Legacy Equivalent       ║
╠═════════════════════╬═══════════════╬═══════════╬════════════════════─────╣
║ view_laporan_lplpo  ║ laporan_lplpo ║ view      ║ -                       ║
║ create_laporan_lplpo║ laporan_lplpo ║ create    ║ input_laporan? ⚠️       ║
║ update_laporan_lplpo║ laporan_lplpo ║ update    ║ -                       ║
║ delete_laporan_lplpo║ laporan_lplpo ║ delete    ║ -                       ║
║ view_laporan_rko    ║ laporan_rko   ║ view      ║ -                       ║
║ create_laporan_rko  ║ laporan_rko   ║ create    ║ input_laporan? ⚠️       ║
║ update_laporan_rko  ║ laporan_rko   ║ update    ║ -                       ║
║ delete_laporan_rko  ║ laporan_rko   ║ delete    ║ -                       ║
║ view_neraca_tahunan ║ neraca_tahunan║ view      ║ -                       ║
║ create_neraca_...  ║ neraca_tahunan║ create    ║ input_laporan? ⚠️       ║
║ update_neraca_...  ║ neraca_tahunan║ update    ║ -                       ║
║ delete_neraca_...  ║ neraca_tahunan║ delete    ║ -                       ║
╚═════════════════════╩═══════════════╩═══════════╩════════════════════─────╝

⚠️  AMBIGUITY: `input_laporan` maps to all 3 `create_*` permissions? Unclear.

**Role Assignment**:
  ✅ super_admin — Full CRUD all (12)
  ✅ admin_gudang — View only all (3)
  ✅ admin_dinas  — View only all (3)
  ✅ puskesmas   — Full CRUD all (12)
  ✅ pustu       — Full CRUD all (12)

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  5. 📝 PERMINTAAN OBAT (permintaan_obat)                                 ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════╦═══════════════╦═══════════╗
║ Permission            ║ Resource      ║ Action    ║
╠═══════════════════════╬═══════════════╬═══════════╣
║ view_permintaan_obat  ║ permintaan_obat║ view      ║
║ create_permintaan_obat║ permintaan_obat║ create    ║
║ update_permintaan_obat║ permintaan_obat║ update    ║
║ delete_permintaan_obat║ permintaan_obat║ delete    ║
╚═══════════════════════╩═══════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — View + Update (2) → Approver role
  ✅ admin_dinas  — View + Update (2) → Approver role
  ✅ puskesmas   — Full CRUD (4) → Can create & delete own requests
  ⚠️  pustu       — View + Create + Update (3) — **Missing Delete** (line 139)

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  6. 🚚 DISTRIBUSI OBAT (distribusi_obat)                                 ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════╦═══════════════╦═══════════╗
║ Permission            ║ Resource      ║ Action    ║
╠═══════════════════════╬═══════════════╬═══════════╣
║ view_distribusi_obat  ║ distribusi_obat║ view      ║
║ create_distribusi_obat║ distribusi_obat║ create    ║
║ update_distribusi_obat║ distribusi_obat║ update    ║
║ delete_distribusi_obat║ distribusi_obat║ delete    ║
╚═══════════════════════╩═══════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — Create + View + Update (3) — No delete
  ✅ admin_dinas  — View + Update (2)
  ✅ puskesmas   — Create + View + Update (3) — No delete
  ✅ pustu       — View + Update (2)

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  7. 💉 PEMAKAIAN OBAT (pemakaian_obat)                                   ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════╦═══════════════╦═══════════╗
║ Permission            ║ Resource      ║ Action    ║
╠═══════════════════════╬═══════════════╬═══════════╣
║ view_pemakaian_obat   ║ pemakaian_obat║ view      ║
║ create_pemakaian_obat ║ pemakaian_obat║ create    ║
║ update_pemakaian_obat ║ pemakaian_obat║ update    ║
║ delete_pemakaian_obat ║ pemakaian_obat║ delete    ║
╚═══════════════════════╩═══════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — View only (1)
  ✅ admin_dinas  — View only (1)
  ✅ puskesmas   — Full CRUD (4)
  ✅ pustu       — Full CRUD (4)

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  8. 📥 PENERIMAAN STOK (penerimaan_stok)                                 ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════╦═══════════════╦═══════════╗
║ Permission            ║ Resource      ║ Action    ║
╠═══════════════════════╬═══════════════╬═══════════╣
║ view_penerimaan_stok  ║ penerimaan_stok║ view      ║
║ create_penerimaan_stok║ penerimaan_stok║ create    ║
║ update_penerimaan_stok║ penerimaan_stok║ update    ║
║ delete_penerimaan_stok║ penerimaan_stok║ delete    ║
╚═══════════════════════╩═══════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — View + Create + Update (3)
  ✅ admin_dinas  — View only (1)
  ✅ puskesmas   — Full CRUD (4)
  ✅ pustu       — Full CRUD (4)

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║  9. ↩️  RETUR OBAT (retur_obat, inspeksi_retur)                           ║
### ╚════════════════════════════════════════════════════════════════════════════╝

#### Retur Obat
╔═══════════════════╦═══════════╦═══════════╗
║ Permission        ║ Resource  ║ Action    ║
╠═══════════════════╬═══════════╬═══════════╣
║ view_retur_obat   ║ retur_obat║ view      ║
║ create_retur_obat ║ retur_obat║ create    ║
║ update_retur_obat ║ retur_obat║ update    ║
║ delete_retur_obat ║ retur_obat║ delete    ║
╚═══════════════════╩═══════════╩═══════════╝

#### Inspeksi Retur
╔═════════════════════╦═════════════╦═══════════╗
║ Permission          ║ Resource    ║ Action    ║
╠═════════════════════╬═════════════╬═══════════╣
║ view_inspeksi_retur║ inspeksi_retur║ view      ║
║ create_inspeksi... ║ inspeksi_retur║ create    ║
║ update_inspeksi... ║ inspeksi_retur║ update    ║
║ delete_inspeksi... ║ inspeksi_retur║ delete    ║
╚═════════════════════╩═════════════╩═══════════╝

**Role Assignment — Retur**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — Full CRUD (4)
  ✅ admin_dinas  — View + Update (2)
  ✅ puskesmas   — Full CRUD (4)
  ✅ pustu       — Full CRUD (4)

**Role Assignment — Inspeksi**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — Create + View + Update (3) — No delete
  ✅ admin_dinas  — View only (1)
  ❌ puskesmas   — None
  ❌ pustu       — None

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 10. 📋 OPMNAME STOK (opname_stok)                                        ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════╦════════════╦═══════════╗
║ Permission        ║ Resource   ║ Action    ║
╠═══════════════════╬════════════╬═══════════╣
║ view_opname_stok  ║ opname_stok║ view      ║
║ create_opname_stok║ opname_stok║ create    ║
║ update_opname_stok║ opname_stok║ update    ║
║ delete_opname_stok║ opname_stok║ delete    ║
╚═══════════════════╩════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — Create + View + Update (3) — No delete
  ✅ admin_dinas  — View + Update (2)
  ✅ puskesmas   — Full CRUD (4)
  ✅ pustu       — Full CRUD (4)

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 11. 💰 DANA & ANGGARAN (sumber_dana, sumber_dana_penggunaan, alokasi_dana) ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════╦═════════════════╦═══════════╗
║ Permission                ║ Resource        ║ Action    ║
╠═══════════════════════════╬═════════════════╬═══════════╣
║ view_sumber_dana          ║ sumber_dana     ║ view      ║
║ create_sumber_dana        ║ sumber_dana     ║ create    ║
║ update_sumber_dana        ║ sumber_dana     ║ update    ║
║ delete_sumber_dana        ║ sumber_dana     ║ delete    ║
║ view_sumber_dana_penggunaan║ sumber_dana... ║ view      ║
║ create_sumber_dana_pen... ║ sumber_dana... ║ create    ║
║ update_sumber_dana_pen... ║ sumber_dana... ║ update    ║
║ delete_sumber_dana_pen... ║ sumber_dana... ║ delete    ║
║ view_alokasi_dana         ║ alokasi_dana    ║ view      ║
║ create_alokasi_dana       ║ alokasi_dana    ║ create    ║
║ update_alokasi_dana       ║ alokasi_dana    ║ update    ║
║ delete_alokasi_dana       ║ alokasi_dana    ║ delete    ║
╚═══════════════════════════╩═════════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — All (12)
  ❌ admin_gudang — None
  ✅ admin_dinas  — CRUD sumber_dana (4) + view_sumber_dana_penggunaan + view_alokasi_dana = 6 total
  ❌ puskesmas   — None
  ❌ pustu       — None

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 12. 🏢 SUPPLIERS (suppliers)                                             ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════╦════════════╦═══════════╗
║ Permission        ║ Resource   ║ Action    ║
╠═══════════════════╬════════════╬═══════════╣
║ view_suppliers    ║ suppliers  ║ view      ║
║ create_suppliers  ║ suppliers  ║ create    ║
║ update_suppliers  ║ suppliers  ║ update    ║
║ delete_suppliers  ║ suppliers  ║ delete    ║
╚═══════════════════╩════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — View + Create + Update (3) — No delete
  ❌ admin_dinas  — None
  ❌ puskesmas   — None
  ❌ pustu       — None

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 13. 🤖 AI/PREDIKSI (model_prediksi, prediksi_kebutuhan)                 ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════╦════════════════╦═══════════╗
║ Permission            ║ Resource       ║ Action    ║
╠═══════════════════════╬════════════════╬═══════════╣
║ view_model_prediksi   ║ model_prediksi ║ view      ║
║ create_model_prediksi ║ model_prediksi ║ create    ║
║ update_model_prediksi ║ model_prediksi ║ update    ║
║ delete_model_prediksi ║ model_prediksi ║ delete    ║
║ view_prediksi_kebutuhan ║ prediksi...   ║ view      ║
║ create_prediksi_ke... ║ prediksi...    ║ create    ║
║ update_prediksi_ke... ║ prediksi...    ║ update    ║
║ delete_prediksi_ke... ║ prediksi...    ║ delete    ║
╚═══════════════════════╩════════════════╩═══════════╝

**Role Assignment** (sesuai `RoleAndPermissionSeeder.php`):
  ✅ super_admin — Full CRUD all (8)
  ✅ admin_gudang — View only both (2)
  ✅ admin_dinas  — View only both (2)
  ✅ puskesmas   — View only both (2)
  ✅ pustu       — View prediksi_kebutuhan only (1)

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 14. 📊 IMPORT (import_data_historis)                                     ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════════╦═════════════════╦═══════════╗
║ Permission                ║ Resource        ║ Action    ║
╠═══════════════════════════╬═════════════════╬═══════════╣
║ view_import_data_historis ║ import_data_... ║ view      ║
║ create_import_data_historis║ import_data_...║ create    ║
║ update_import_data_historis║ import_data_...║ update    ║
║ delete_import_data_historis║ import_data_...║ delete    ║
╚═══════════════════════════╩═════════════════╩═══════════╝

**Role Assignment**: ❌ No role has these permissions (unused resource).

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 15. ⚙️  SETTINGS (pengaturan_laporan + special)                          ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════════╦═══════════════════╦═══════════╦════════───────╗
║ Permission             ║ Resource          ║ Action    ║ Notes         ║
╠═══════════════════════╬═══════════════════╬═══════════╬════════───────╣
║ view_pengaturan_laporan║ pengaturan_laporan║ view      ║ Standard      ║
║ create_pengaturan_...  ║ pengaturan_laporan║ create    ║ Standard      ║
║ update_pengaturan_...  ║ pengaturan_laporan║ update    ║ Standard      ║
║ delete_pengaturan_...  ║ pengaturan_laporan║ delete    ║ Standard      ║
║ manage_pengaturan_pdf  ║ -                 ║ special   ║ Legacy (assigned)║
║ manage_pengaturan_nomor║ -                 ║ special   ║ ❌ Not assigned ║
║ view_dashboard         ║ -                 ║ special   ║ Assigned all  ║
╚═══════════════════════╩═══════════════════╩═══════════╩════════───────╝

**Role Assignment**:
  ✅ super_admin — All + auto all
  ✅ admin_gudang — manage_pengaturan_pdf only (not CRUD)
  ✅ admin_dinas  — manage_pengaturan_pdf only (not CRUD)
  ❌ puskesmas   — None
  ❌ pustu       — None

⚠️  BUG: `manage_pengaturan_nomor` created but never assigned.

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 16. 👤 USER SETTINGS (avatar_presets, user_preferences)                  ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═════════════════════════╦════════════════╦═══════════╗
║ Permission              ║ Resource       ║ Action    ║
╠═════════════════════════╬════════════════╬═══════════╣
║ view_avatar_presets     ║ avatar_presets ║ view      ║
║ create_avatar_presets   ║ avatar_presets ║ create    ║
║ update_avatar_presets   ║ avatar_presets ║ update    ║
║ delete_avatar_presets   ║ avatar_presets ║ delete    ║
║ view_user_preferences   ║ user_preferences║ view      ║
║ create_user_preferences ║ user_preferences║ create    ║
║ update_user_preferences ║ user_preferences║ update    ║
║ delete_user_preferences ║ user_preferences║ delete    ║
╚═════════════════════════╩════════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — All (8)
  ❌ admin_gudang — None
  ✅ admin_dinas  — All for user_preferences only (4)
  ❌ puskesmas   — None
  ❌ pustu       — None

---

### ╔════════════════════════════════════════════════════════════════════════════╗
### ║ 17. 📊 AUDIT (activity_logs)                                             ║
### ╚════════════════════════════════════════════════════════════════════════════╝

╔═══════════════════╦═══════════════╦═══════════╗
║ Permission        ║ Resource      ║ Action    ║
╠═══════════════════╬═══════════════╬═══════════╣
║ view_activity_logs║ activity_logs ║ view      ║
║ create_activity_...║ activity_logs ║ create    ║
║ update_activity_...║ activity_logs ║ update    ║
║ delete_activity_...║ activity_logs ║ delete    ║
╚═══════════════════╩═══════════════╩═══════════╝

**Role Assignment**:
  ✅ super_admin — Full CRUD (4)
  ✅ admin_gudang — View only (1)
  ✅ admin_dinas  — View only (1)
  ✅ puskesmas   — View only (1)
  ✅ pustu       — View only (1)

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                        🔀 LEGACY vs CRUD MAPPING                            ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

╔═════════════════════════╦═════════════════════════════════════════════╦════════════════════─────────╦═══════════╗
║ Legacy Permission       ║ Resource(s) It Should Map To                ║ Current Assignment           ║ Issue     ║
╠═════════════════════════╬═════════════════════════════════════════════╬════════════════════─────────╬═══════════╣
║ view_dashboard          ║ (special page)                             ║ All roles                    ║ ✅ OK     ║
║ manage_obat             ║ view_obat, create_obat, update_obat, ...  ║ admin_gudang, admin_dinas    ║ ⚠️ Migrate║
║ view_laporan            ║ view_laporan_lplpo, view_laporan_rko, ... ║ super_admin, admin_gudang    ║ ⚠️ Ambiguous
║ manage_laporan          ║ Full CRUD all laporan resources?          ║ super_admin, admin_gudang    ║ ⚠️ Ambiguous
║ input_laporan           ║ create_laporan_*?                          ║ super_admin, admin_gudang,   ║ ⚠️ Unclear
║                        ║                                           ║ puskesmas, pustu             ║           ║
║ manage_pengaturan_pdf   ║ CRUD pengaturan_laporan?                   ║ admin_gudang, admin_dinas    ║ ⚠️ Naming ║
║ manage_pengaturan_nomor ║ ?                                          ║ ❌ Not assigned (BUG)        ║ ❌ Missing ║
╚═════════════════════════╩═════════════════════════════════════════════╩════════════════════════════╩═══════════╝

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                     📌 CRITICAL ISSUES SUMMARY                              ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

╔══════════════════════════════════════════════════════════════════════════════════╗
║ 1. ⚠️  LEGACY `manage_obat` IN USE                                               ║
║    → Affected: admin_gudang, admin_dinas                                         ║
║    → Should be replaced with CRUD `obat` permissions                            ║
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 2. ⚠️  LEGACY LAPORAN PERMISSIONS AMBIGUOUS                                      ║
║    → view_laporan, manage_laporan, input_laporan unclear mapping                ║
║    → Should map to specific resources (laporan_lplpo, laporan_rko, neraca)      ║
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 3. ❌ `manage_pengaturan_nomor` NOT ASSIGNED                                     ║
║    → Created on line 22 but no role receives it (BUG)                          ║
║    → Action: Assign or delete                                                    ║
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 4. ⚠️  `manage_pengaturan_pdf` NON-CRUD NAMING                                   ║
║    → Should use CRUD `pengaturan_laporan` instead                               ║
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 5. ⚠️  PUSTU MISSING `delete_permintaan_obat`                                   ║
║    → Line 139: has view+create+update but no delete                            ║
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 6. ⚠️  ADMIN_GUDANG `delete_penerimaan_stok`?                                    ║
║    → Line 79-80 shows view, create, update — verify if delete intended         ║
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 7. ⚠️  ADMIN_DINAS MISSING `input_laporan`                                       ║
║    → Has manage_pdf but not input_laporan — clarify intention                  ║
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 8. ⚠️  MANY RESOURCES UNASSIGNED                                                 ║
║    → users, roles, permissions, fasilitas, obat, suppliers, import, etc.       ║
║    → Need business decision: assign to specific roles or leave super-only     ║
╠══════════════════════════════════════════════════════════════════════════════════╣
╠══════════════════════════════════════════════════════════════════════════════════╣
║ 10. ⚠️  ADMIN_DINAS HAS `delete_sumber_dana`                                    ║
║     → Should dinas be able to delete master data (sumber_dana)?                ║
╚══════════════════════════════════════════════════════════════════════════════════╝

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                  🎯 ROLE ASSIGNMENT MATRIX (QUICK REFERENCE)                ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

Legend: V=view, C=create, U=update, D=delete, ⚠️=legacy, ❌=none

╔════════════════════════╦═══════════════╦═════════════╦═══════════════╦═══════════╦═══════════╗
║ Resource               ║ super_admin   ║ admin_gudang║ admin_dinas   ║ puskesmas ║ pustu     ║
╠════════════════════════╬═══════════════╬═════════════╬═══════════════╬═══════════╬═══════════╣
║ users                  ║ C+R+U+D       ║ ❌          ║ ❌            ║ ❌        ║ ❌        ║
║ roles                  ║ C+R+U+D       ║ ❌          ║ ❌            ║ ❌        ║ ❌        ║
║ permissions            ║ C+R+U+D       ║ ❌          ║ ❌            ║ ❌        ║ ❌        ║
║ fasilitas_kesehatan    ║ C+R+U+D       ║ ❌          ║ ❌            ║ ❌        ║ ❌        ║
║ obat                   ║ C+R+U+D       ║ ⚠️ legacy    ║ ⚠️ legacy     ║ ❌        ║ ❌        ║
║ suppliers              ║ C+R+U+D       ║ V+C+U       ║ ❌            ║ ❌        ║ ❌        ║
║ stok_gudang            ║ C+R+U+D       ║ V           ║ V             ║ ❌        ║ ❌        ║
║ stok_faskes            ║ C+R+U+D       ║ V           ║ V             ║ V         ║ V         ║
║ batch_stok             ║ C+R+U+D       ║ V           ║ V             ║ ❌        ║ ❌        ║
║ laporan_lplpo          ║ C+R+U+D       ║ V           ║ V             ║ C+R+U+D   ║ C+R+U+D   ║
║ permintaan_obat        ║ C+R+U+D       ║ V+U         ║ V+U           ║ C+R+U+D   ║ C+R+U     ║
║ distribusi_obat        ║ C+R+U+D       ║ C+V+U       ║ V+U           ║ C+V+U     ║ V+U       ║
║ riwayat_stok           ║ C+R+U+D       ║ V           ║ V             ║ V         ║ V         ║
║ pemakaian_obat         ║ C+R+U+D       ║ V           ║ V             ║ C+R+U+D   ║ C+R+U+D   ║
║ penerimaan_stok        ║ C+R+U+D       ║ V+C+U       ║ V             ║ C+R+U+D   ║ C+R+U+D   ║
║ laporan_rko            ║ C+R+U+D       ║ V           ║ V             ║ C+R+U+D   ║ C+R+U+D   ║
║ neraca_tahunan         ║ C+R+U+D       ║ V           ║ V             ║ C+R+U+D   ║ C+R+U+D   ║
║ sumber_dana            ║ C+R+U+D       ║ ❌          ║ C+R+U+D       ║ ❌        ║ ❌        ║
║ sumber_dana_penggunaan ║ C+R+U+D      ║ V           ║ V             ║ ❌        ║ ❌        ║
║ alokasi_dana           ║ C+R+U+D       ║ V           ║ V             ║ ❌        ║ ❌        ║
║ retur_obat             ║ C+R+U+D       ║ C+R+U+D     ║ V+U           ║ C+R+U+D   ║ C+R+U+D   ║
║ inspeksi_retur         ║ C+R+U+D       ║ C+V+U       ║ V             ║ ❌        ║ ❌        ║
║ opname_stok            ║ C+R+U+D       ║ C+V+U       ║ V+U           ║ C+R+U+D   ║ C+R+U+D   ║
║ model_prediksi         ║ C+R+U+D       ║ V           ║ V             ║ V         ║ ❌        ║
║ prediksi_kebutuhan     ║ C+R+U+D       ║ V           ║ V             ║ V         ║ V         ║
║ pengaturan_laporan     ║ C+R+U+D       ║ ⚠️ special  ║ ⚠️ special    ║ ❌        ║ ❌        ║
║ avatar_presets         ║ C+R+U+D       ║ ❌          ║ ❌            ║ ❌        ║ ❌        ║
║ user_preferences       ║ C+R+U+D       ║ ❌          ║ C+R+U+D       ║ ❌        ║ ❌        ║
║ activity_logs          ║ C+R+U+D       ║ V           ║ V             ║ V         ║ V         ║
║ import_data_historis   ║ C+R+U+D       ║ ❌          ║ ❌            ║ ❌        ║ ❌        ║
╚════════════════════════╩═══════════════╩═════════════╩═══════════════╩═══════════╩═══════════╝

---

## ╔══════════════════════════════════════════════════════════════════════════════╗
## ║                      📝 MIGRATION RECOMMENDATIONS                           ║
## ╚══════════════════════════════════════════════════════════════════════════════╝

### PHASE 1: Immediate Clean-up
┌──────────────────────────────────────────────────────────────────────────────┐
│ ✅ 1. Remove `manage_obat` and assign full CRUD `obat` to admin_gudang        │
│ ✅ 2. Remove legacy laporan permissions or map to specific resources         │
│ ✅ 3. Assign or delete `manage_pengaturan_nomor`                             │
│ ✅ 4. Standardize `manage_pengaturan_pdf` → CRUD `pengaturan_laporan`         │
└──────────────────────────────────────────────────────────────────────────────┘

### PHASE 2: Permission Gap Analysis
┌──────────────────────────────────────────────────────────────────────────────┐
│ • users, roles, permissions → super_admin only? (keep as is)                │
│ • fasilitas_kesehatan → assign to admin_dinas or super_admin?               │
│ • obat → admin_gudang full CRUD (master data management)                    │
│ • suppliers → admin_gudang full CRUD (keep or add delete?)                  │
│ • import_data_historis → who needs bulk import? (super_admin?)              │
│ • avatar_presets → who manages? (super_admin only?)                        │
│ • pengaturan_laporan → convert legacy to CRUD, assign to admin roles        │
└──────────────────────────────────────────────────────────────────────────────┘

### PHASE 3: Role Consistency
┌──────────────────────────────────────────────────────────────────────────────┐
│ • Ensure each role has clear, non-overlapping permission set               │
│ • Document business rationale for each role's permissions                  │
│ • Add validation in seeder to prevent typos and duplicates                 │
│ • Write tests to verify permission assignments                            │
└──────────────────────────────────────────────────────────────────────────────┘

---

**End of Reference Document**
Generated: 2026-01-27
Verification: Run `php artisan pint` after any seeder modifications.
