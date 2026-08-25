import { test, expect, Page } from '@playwright/test';
import { execSync } from 'node:child_process';

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000';

const CREDENTIALS = {
  adminGudang: 'admin_gudang@mail.com',
  password: '123',
};

const OBAT_ITEMS = [
  'Albendazole Tablet 400 mg',
  'Amoxicillin Sirup Kering 125 mg/5 mL',
  'Amoxicillin Sirup Kering 250 mg/5 mL',
  'Amlodipin Tablet 5 mg',
  'Amlodipin Tablet 10 mg'
];
const JUMLAH_LIST = [100, 50, 50, 50, 50];

async function login(page: Page, email: string, password: string): Promise<void> {
  await page.context().clearCookies();
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 15_000 });
  const csrfInput = page.locator('input[name="_token"]').first();
  await csrfInput.waitFor({ state: 'attached', timeout: 10_000 });
  const csrfToken = await csrfInput.inputValue();
  if (!csrfToken) {
    throw new Error('CSRF token not found on login page');
  }
  const response = await page.request.post(`${BASE_URL}/login`, {
    form: {
      _token: csrfToken,
      email,
      password,
    },
    maxRedirects: 0,
  });
  if (response.status() !== 302 && response.status() !== 303) {
    throw new Error(`Login failed: status ${response.status()}`);
  }
}

async function logout(page: Page): Promise<void> {
  await page.goto('about:blank', { waitUntil: 'commit', timeout: 5_000 });
  await page.context().clearCookies();
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'commit', timeout: 15_000 });
}

// The visible wizard "next/submit" actions carry the `ml-auto` class; the
// Filament wizard's own next/prev buttons are hidden (display:none) and must
// be avoided — clicking them desyncs the Livewire `$step` from the visible UI.
// NOTE: `Locator.filter({ visible: true })` is NOT valid Playwright syntax, so
// we scope directly to the `ml-auto` custom action button instead.
// Filament disables action buttons whenever ANY Livewire request is in flight
// (wire:loading.attr="disabled"), which makes Playwright's click actionability
// check flaky. Wait for all in-flight requests to settle before interacting.
async function waitForLivewireIdle(page: Page): Promise<void> {
  await page.waitForFunction(
    () => document.querySelectorAll('[wire\\:loading]').length === 0,
    { timeout: 30_000 },
  );
  await page.waitForTimeout(300);
}

async function clickAction(page: Page, label: string): Promise<void> {
  let btn: ReturnType<Page['locator']>;
  if (label === 'Selanjutnya' || label === 'Simpan') {
    btn = page.locator('button.ml-auto.fi-ac-btn-action').filter({ hasText: new RegExp(label, 'i') });
  } else {
    // Use getByRole for exact accessible-name match (trims whitespace, more reliable for Buat/Simpan).
    btn = page.getByRole('button', { name: new RegExp(`^${label}$`, 'i') });
  }
  await waitForLivewireIdle(page);
  await btn.waitFor({ state: 'visible', timeout: 10_000 });
  await btn.click();
  await page.waitForTimeout(800);
  await waitForLivewireIdle(page);
}

test.describe('Penerimaan Obat: Supplier → Gudang', () => {
  test.beforeAll(() => {
    // Keep data — jangan hapus (sesuai instruksi: biarkan untuk semua test)
    // Jika perlu file dummy dengan kode unik, buat dengan suffix timestamp
    try {
      const dummyPath = require('node:path').join(process.cwd(), 'e2e', 'dummy-surat.pdf');
      const codedPath = dummyPath.replace('.pdf', `-${Date.now().toString().slice(-6)}.pdf`);
      if (require('node:fs').existsSync(dummyPath) && !require('node:fs').existsSync(codedPath)) {
        require('node:fs').copyFileSync(dummyPath, codedPath);
      }
    } catch {}
  });

  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
  });

  test('E2E Flow: Buat penerimaan pembelian dengan 5 item → Dikonfirmasi', async ({ page }) => {
    const nomor = `E2E-PNM-${Date.now()}`;
    const today = new Date().toISOString().split('T')[0];

    await login(page, CREDENTIALS.adminGudang, CREDENTIALS.password);
    await page.goto(`${BASE_URL}/admin/penerimaan-stok/create`, { waitUntil: 'commit', timeout: 15_000 });
    await page.waitForLoadState('domcontentloaded', { timeout: 10_000 });
    await page.waitForTimeout(800);

    // ─────────────────────────────────────────────
    // STEP 1: Informasi
    // ─────────────────────────────────────────────
    await page.locator('#form\\.nomor_penerimaan').fill(nomor);
    await page.locator('#form\\.tipe').selectOption('pembelian');
    await page.waitForTimeout(800);

    await page.locator('#form\\.tanggal_penerimaan').fill(today);
    await page.locator('#form\\.supplier_id').selectOption({ index: 1 });
    await page.waitForTimeout(400);
    await page.locator('#form\\.sumber_dana_id').selectOption({ index: 1 });
    await page.waitForTimeout(400);
    await page.locator('#form\\.nomor_invoice').fill(`INV-${Date.now()}`);

    await clickAction(page, 'Selanjutnya');
    await page.waitForTimeout(1500);

    // ─────────────────────────────────────────────
    // STEP 2: Item Obat (5 item)
    // ─────────────────────────────────────────────
    // Uncheck auto-generate so we control batch numbers deterministically.
    const autoGen = page.locator('#form\\.auto_generate_batch_number');
    if (await autoGen.isChecked()) {
      await autoGen.uncheck();
      await page.waitForTimeout(500);
    }

    // NOTE: The wizard step tab "Item Obat" contains "Tambah item..." in its
    // accessible name, so we must use an anchored regex to avoid matching it.
    const addItemBtn = page.getByRole('button', { name: /^Tambah Item$/i });
    for (let i = 1; i < OBAT_ITEMS.length; i++) {
      await waitForLivewireIdle(page);
      const beforeCount = await page.locator('select[id$=".obat_id"]').count();
      await addItemBtn.click();
      await page.waitForTimeout(1200);
      const afterCount = await page.locator('select[id$=".obat_id"]').count();
      console.log(`ADD ITEM iter=${i} before=${beforeCount} after=${afterCount}`);
      if (afterCount <= beforeCount) {
        await waitForLivewireIdle(page);
        await addItemBtn.click();
        await page.waitForTimeout(1200);
        const retryCount = await page.locator('select[id$=".obat_id"]').count();
        console.log(`ADD ITEM retry iter=${i} count=${retryCount}`);
      }
    }

    const beforeFillCount = await page.locator('select[id$=".obat_id"]').count();
    console.log('OBAT SELECT COUNT BEFORE FILL:', beforeFillCount);
    // Use page.evaluate for filling — more reliable than Playwright fill/selectOption for Livewire sync (proven via chrome-devtools).
    for (let i = 0; i < OBAT_ITEMS.length; i++) {
      await waitForLivewireIdle(page);
      await page.evaluate(
        ({ idx, obat, jumlah, batch }) => {
          const sel = document.querySelectorAll('select[id$=".obat_id"]')[idx] as HTMLSelectElement;
          if (sel) {
            const opt = Array.from(sel.options).find((o) => o.text === obat);
            if (opt) {
              sel.value = opt.value;
              sel.dispatchEvent(new Event('input', { bubbles: true }));
              sel.dispatchEvent(new Event('change', { bubbles: true }));
              sel.dispatchEvent(new Event('blur', { bubbles: true }));
            }
          }
          const jml = document.querySelectorAll('input[id$=".jumlah"]')[idx] as HTMLInputElement;
          if (jml) {
            jml.value = String(jumlah);
            jml.dispatchEvent(new Event('input', { bubbles: true }));
            jml.dispatchEvent(new Event('change', { bubbles: true }));
            jml.dispatchEvent(new Event('blur', { bubbles: true }));
          }
          const tgl = document.querySelectorAll('input[id$=".tanggal_expired"]')[idx] as HTMLInputElement;
          if (tgl) {
            tgl.value = '2027-08-24';
            tgl.dispatchEvent(new Event('input', { bubbles: true }));
            tgl.dispatchEvent(new Event('change', { bubbles: true }));
            tgl.dispatchEvent(new Event('blur', { bubbles: true }));
          }
          const b = document.querySelectorAll('input[id$=".batch_number"]')[idx] as HTMLInputElement;
          if (b) {
            b.value = batch;
            b.dispatchEvent(new Event('input', { bubbles: true }));
            b.dispatchEvent(new Event('change', { bubbles: true }));
            b.dispatchEvent(new Event('blur', { bubbles: true }));
          }
        },
        { idx: i, obat: OBAT_ITEMS[i], jumlah: JUMLAH_LIST[i], batch: `E2E-PNM-00${i + 1}` },
      );
      await page.waitForTimeout(700);
      await waitForLivewireIdle(page);
    }

    const rowCountAfterFill = await page.locator('select[id$=".obat_id"]').count();
    console.log('ROW COUNT AFTER FILL:', rowCountAfterFill);
    const selectedObat = await page.evaluate(() => Array.from(document.querySelectorAll('select[id$=".obat_id"]')).map(s => s.options[s.selectedIndex]?.text));
    console.log('SELECTED OBAT AFTER FILL:', JSON.stringify(selectedObat));

    // Delete any extra empty rows that Livewire may have auto-added during fill.
    let currentCount = await page.locator('select[id$=".obat_id"]').count();
    console.log(`ROW COUNT BEFORE CLEANUP: ${currentCount}`);
    for (let r = currentCount - 1; r >= OBAT_ITEMS.length; r--) {
      const isEmpty = await page.locator('select[id$=".obat_id"]').nth(r).evaluate((el) => {
        const sel = el as HTMLSelectElement;
        return sel.selectedIndex <= 0;
      });
      console.log(`CHECK EXTRA ROW r=${r} isEmpty=${isEmpty}`);
      if (isEmpty) {
        const before = await page.locator('select[id$=".obat_id"]').count();
        await page.locator('button[aria-label="Hapus"]').last().click();
        try {
          await page.waitForFunction((prev) => document.querySelectorAll('select[id$=".obat_id"]').length < prev, before, { timeout: 8_000 });
        } catch {}
        await waitForLivewireIdle(page);
        // Extra idle time — ensure Livewire state catches up with DOM after repeater delete
        await page.waitForTimeout(800);
        console.log(`DELETED EXTRA ROW r=${r} newCount=${await page.locator('select[id$=".obat_id"]').count()}`);
      }
    }
    const finalRowCount = await page.locator('select[id$=".obat_id"]').count();
    console.log('ROW COUNT AFTER CLEANUP:', finalRowCount);
    const selectedAfterCleanup = await page.evaluate(() => Array.from(document.querySelectorAll('select[id$=".obat_id"]')).map(s => s.options[s.selectedIndex]?.text));
    console.log('SELECTED AFTER CLEANUP:', JSON.stringify(selectedAfterCleanup));

    await clickAction(page, 'Selanjutnya');

    // Verify we actually landed on Konfirmasi
    try {
      await page.locator('h2:has-text("Konfirmasi Penerimaan")').waitFor({ state: 'visible', timeout: 12_000 });
      await page.getByRole('button', { name: /^Buat$/i }).waitFor({ state: 'visible', timeout: 10_000 });
    } catch {
      const url = page.url();
      const errs = await page.locator('.fi-fo-field-error-message, [data-validation-error], .text-danger-600').allTextContents();
      console.log('FAILED TO REACH KONFIRMASI url=', url, 'errs=', JSON.stringify(errs.slice(0,3)));
      throw new Error(`Failed to reach Konfirmasi step — still at ${url}`);
    }
    await waitForLivewireIdle(page);

    const actionBtns = await page.locator('button.fi-ac-btn-action').allTextContents();
    console.log('ACTION BUTTONS ON KONFIRMASI:', JSON.stringify(actionBtns.map(t => t.trim()).filter(Boolean)));

    // ─────────────────────────────────────────────
    // STEP 3: Konfirmasi → Buat
    // ─────────────────────────────────────────────
    await page.locator('#form\\.catatan').fill('Penerimaan E2E test - 5 item obat');
    await clickAction(page, 'Buat');

    // Should redirect to the view page — give Livewire time, then check for errors if not.
    try {
      await page.waitForURL(/\/admin\/penerimaan-stok\/\d+$/, { timeout: 20_000 });
    } catch {
      const urlAfterBuat = page.url();
      const buatErrors = await page.locator('.fi-fo-field-error-message, [data-validation-error], .text-danger-600, .fi-toast').allTextContents();
      console.log('BUAT CLICK URL STAYED:', urlAfterBuat);
      console.log('BUAT CLICK ERRORS:', JSON.stringify(buatErrors.slice(0, 5)));
      throw new Error(`Buat did not navigate — still at ${urlAfterBuat} — errors: ${JSON.stringify(buatErrors.slice(0, 3))}`);
    }
    await page.waitForLoadState('domcontentloaded', { timeout: 10_000 });
    await page.waitForTimeout(800);

    // ─────────────────────────────────────────────
    // VERIFY: status Dikonfirmasi + 5 item obat
    // ─────────────────────────────────────────────
    const heading = await page.locator('main h1').innerText();
    expect(heading).toContain(nomor);

    const dikonfirmasiBadge = page.locator('span.rounded-full').filter({ hasText: 'Dikonfirmasi' }).first();
    await expect(dikonfirmasiBadge).toBeVisible({ timeout: 10_000 });

    for (const obat of OBAT_ITEMS) {
      await expect(page.locator('body')).toContainText(obat, { timeout: 10_000 });
    }

    await logout(page);
  });
});
