import { test, expect, Page } from '@playwright/test';

const BASE_URL = process.env.BASE_URL || 'http://localhost:8000';

const CREDENTIALS = {
  puskesmas: 'puskesmaskupangbarat@mail.com',
  adminDinas: 'admin_dinas@mail.com',
  adminGudang: 'admin_gudang@mail.com',
  password: '123',
};

async function login(page: Page, email: string, password: string): Promise<void> {
  await page.context().clearCookies();
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 15_000 });
  // Wait for the custom login form before reading its CSRF token.
  const csrfInput = page.locator('input[name="_token"]').first();
  await csrfInput.waitFor({ state: 'attached', timeout: 10_000 });
  const csrfToken = await csrfInput.inputValue();
  if (!csrfToken) {
    throw new Error('CSRF token not found on login page');
  }
  // Submit login form directly
  const response = await page.request.post(`${BASE_URL}/login`, {
    form: {
      _token: csrfToken,
      email,
      password,
    },
    maxRedirects: 0,
  });
  // Login should redirect (302) to admin dashboard
  if (response.status() !== 302 && response.status() !== 303) {
    throw new Error(`Login failed: status ${response.status()}`);
  }
}

async function logout(page: Page): Promise<void> {
  // Stop Livewire polling before switching users; PHP's built-in server is single-threaded.
  await page.goto('about:blank', { waitUntil: 'commit', timeout: 5_000 });
  await page.context().clearCookies();
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'commit', timeout: 15_000 });
}

async function waitForFilamentLoad(page: Page): Promise<void> {
  await page.waitForLoadState('domcontentloaded', { timeout: 10_000 });
}

async function openSelectAndPickFirst(page: Page, label: string): Promise<void> {
  const combobox = page.getByRole('combobox', { name: new RegExp(label, 'i') }).first();
  await combobox.click();
  await page.waitForTimeout(300);

  const option = page.locator('[role="option"], .fi-select-option, .fi-dropdown-list-item').filter({ visible: true }).first();
  await option.click();
  await page.waitForTimeout(300);
}

async function getStatusBadgeLocator(page: Page, expected: string) {
  return page.locator('span.rounded-full').filter({ hasText: expected });
}

async function navigateToReturView(page: Page, returId: string): Promise<void> {
  await page.goto(`${BASE_URL}/admin/retur-obat/${returId}`, { waitUntil: 'commit', timeout: 15_000 });
  await waitForFilamentLoad(page);
  await page.waitForTimeout(500);
}

test.describe('Retur Obat: Puskesmas → Gudang', () => {

  test.beforeEach(async ({ page }) => {
    await page.context().clearCookies();
  });

  test('E2E Flow: Buat → Ajukan → Setujui → Kirim → Terima → Selesai', async ({ page }) => {
    // ─────────────────────────────────────────────
    // STEP 1: Login sebagai puskesmas, buat retur (status: draft)
    // ─────────────────────────────────────────────
    await login(page, CREDENTIALS.puskesmas, CREDENTIALS.password);
    await waitForFilamentLoad(page);

    await page.goto(`${BASE_URL}/admin/retur-obat/create`, { waitUntil: 'commit', timeout: 15_000 });
    await waitForFilamentLoad(page);
    await page.waitForTimeout(500);

    // Select Distribusi Terkait
    await openSelectAndPickFirst(page, 'Distribusi');
    await page.waitForTimeout(500);

    // Select Alasan Retur
    await openSelectAndPickFirst(page, 'Alasan Retur');

    // Set tanggal retur (today)
    const today = new Date().toISOString().split('T')[0];
    const dateInput = page.locator('input[type="date"]').first();
    await dateInput.fill(today);
    await page.waitForTimeout(500);

    // ── Tambah Item via "Tambah Item" button ──
    await page.getByRole('button', { name: /Tambah Item/i }).first().click();
    await page.waitForTimeout(500);

    // Select obat
    await openSelectAndPickFirst(page, 'Obat');
    await page.waitForTimeout(500);

    // Select batch
    await openSelectAndPickFirst(page, 'Batch');
    await page.waitForTimeout(500);

    // Fill jumlah
    const jumlahInput = page.locator('input[type="number"]').last();
    await jumlahInput.fill('1');

    // Submit item modal
    await page.getByRole('button', { name: /^Kirim$/i }).first().click();
    await page.waitForTimeout(800);

    // Verify item was added (the obat name appears in the detail table row)
    await expect(page.locator('td').filter({ hasText: 'Albendazole Tablet 400 mg' }).first()).toBeVisible({ timeout: 5000 });

    // ── Submit form: Simpan as draft ──
    await page.getByRole('button', { name: /^Simpan$/i }).first().click();

    // Should redirect to edit page - wait up to 20s for the URL to change
    await page.waitForURL(/\/admin\/retur-obat\/\d+\/edit/, { timeout: 20_000 });

    // Get retur ID from URL for later steps
    const returUrl = page.url();
    const returId = returUrl.match(/\/admin\/retur-obat\/(\d+)\/edit/)?.[1];
    expect(returId).toBeDefined();
    await page.waitForTimeout(500);

    // ─────────────────────────────────────────────
    // STEP 2: Ajukan retur (draft → menunggu_approval)
    // ─────────────────────────────────────────────
    const ajukanBtn = page.getByRole('button', { name: /Ajukan Retur/i }).first();
    await ajukanBtn.click();
    await page.waitForTimeout(800);

    // Verify status is now "menunggu_approval" by navigating to view page
    await page.goto(`${BASE_URL}/admin/retur-obat/${returId}`, { waitUntil: 'commit', timeout: 15_000 });
    await waitForFilamentLoad(page);
    await page.waitForTimeout(500);

    const menungguStatus = await getStatusBadgeLocator(page, 'Menunggu Approval');
    await expect(menungguStatus).toBeVisible({ timeout: 5000 });

    await logout(page);

    // ─────────────────────────────────────────────
    // STEP 3: Login sebagai admin_dinas, setujui retur
    // ─────────────────────────────────────────────
    await login(page, CREDENTIALS.adminDinas, CREDENTIALS.password);

    await navigateToReturView(page, returId!);

    // Click Setujui
    await page.getByRole('button', { name: /^Setujui$/i }).first().click();
    await page.waitForTimeout(800);

    // Confirm modal (requiresConfirmation)
    await page.getByRole('button', { name: /^Konfirmasi$/i }).first().click();
    await page.waitForTimeout(800);

    // Reload to ensure fresh page state and avoid 403 from Livewire update
    await page.reload();
    await page.waitForTimeout(500);

    const disetujuiStatus = page.locator('span.rounded-full').filter({ hasText: 'Disetujui' }).first();
    await expect(disetujuiStatus).toBeVisible({ timeout: 10_000 });

    await logout(page);

    // ─────────────────────────────────────────────
    // STEP 4: Login sebagai admin_gudang, kirim retur
    // ─────────────────────────────────────────────
    await login(page, CREDENTIALS.adminGudang, CREDENTIALS.password);

    await navigateToReturView(page, returId!);

    // Click Kirim Retur
    const kirimReturButton = page.getByRole('button', { name: /^Kirim Retur$/i }).first();
    await expect(kirimReturButton).toBeVisible({ timeout: 8_000 });
    await kirimReturButton.click();
    await page.waitForTimeout(800);

    // Confirm modal (requiresConfirmation)
    await page.getByRole('button', { name: /^Konfirmasi$/i }).first().click();
    await page.waitForTimeout(800);

    // Reload to ensure fresh page state and avoid 403 from Livewire update
    await page.reload();
    await page.waitForTimeout(500);

    const dikirimStatus = page.locator('span.rounded-full').filter({ hasText: 'Dalam Pengiriman' }).first();
    await expect(dikirimStatus).toBeVisible({ timeout: 10_000 });

    // ─────────────────────────────────────────────
    // STEP 5: Masih admin_gudang (penerima barang), terima retur
    // ─────────────────────────────────────────────
    const terimaReturButton = page.getByRole('button', { name: /^Terima Retur$/i }).first();
    await expect(terimaReturButton).toBeVisible({ timeout: 8_000 });
    await terimaReturButton.click();
    await page.waitForTimeout(800);

    // Confirm modal (requiresConfirmation)
    await page.getByRole('button', { name: /^Konfirmasi$/i }).first().click();
    await page.waitForTimeout(800);

    // Reload to ensure fresh page state and avoid 403 from Livewire update
    await page.reload();
    await page.waitForTimeout(500);

    const diterimaStatus = page.locator('span.rounded-full').filter({ hasText: 'Diterima' }).first();
    await expect(diterimaStatus).toBeVisible({ timeout: 10_000 });

    await logout(page);

    // ─────────────────────────────────────────────
    // STEP 6: Login sebagai puskesmas (pengirim), tandai selesai
    // ─────────────────────────────────────────────
    await login(page, CREDENTIALS.puskesmas, CREDENTIALS.password);

    await navigateToReturView(page, returId!);

    const selesaiBtn = page.getByRole('button', { name: /^Tandai Selesai$/i }).first();
    await expect(selesaiBtn).toBeVisible({ timeout: 8_000 });
    await selesaiBtn.click();
    await page.waitForTimeout(800);

    const konfirmasiSelesaiBtn = page.getByRole('button', { name: /^Konfirmasi$/i }).first();
    await konfirmasiSelesaiBtn.click();
    await page.waitForTimeout(800);

    // Reload to ensure fresh page state and avoid 403 from Livewire update
    await page.reload();
    await page.waitForTimeout(500);

    const selesaiStatus = page.locator('span.rounded-full').filter({ hasText: 'Selesai' }).first();
    await expect(selesaiStatus).toBeVisible({ timeout: 10_000 });
  });
});
