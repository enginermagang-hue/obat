import { test, expect, Page } from "@playwright/test";
import { execSync } from "node:child_process";

const BASE_URL = process.env.BASE_URL || "http://localhost:8000";

const CREDENTIALS = {
    adminGudang: "admin_gudang@mail.com",
    puskesmas: "puskesmaskupangbarat@mail.com",
    password: "123",
};

// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────

async function login(
    page: Page,
    email: string,
    password: string,
): Promise<void> {
    await page.context().clearCookies();
    await page.goto(`${BASE_URL}/login`, {
        waitUntil: "domcontentloaded",
        timeout: 15_000,
    });

    const csrfToken = await page
        .locator('input[name="_token"]')
        .first()
        .inputValue();
    const response = await page.request.post(`${BASE_URL}/login`, {
        form: { _token: csrfToken, email, password },
        maxRedirects: 0,
    });
    if (response.status() !== 302 && response.status() !== 303) {
        throw new Error(`Login failed: status ${response.status()}`);
    }
}

async function logout(page: Page): Promise<void> {
    await page.context().clearCookies();
    await page.goto("about:blank", { waitUntil: "commit", timeout: 5_000 });
}

/** Wait until all Livewire requests settle. */ async function waitForLivewireIdle(
    page: Page,
): Promise<void> {
    await page.waitForFunction(
        () => document.querySelectorAll("[wire\\:loading]").length === 0,
        { timeout: 30_000 },
    );
}

/** Open the CreateAction modal from the Stok Opname list page. */
async function openCreateModal(page: Page): Promise<void> {
    await page.getByRole("button", { name: "Buat Opname" }).click();
    // The modal body mounts a Livewire schema with the `tipe` select.
    await page
        .locator("#mountedActionSchema0\\.tipe")
        .waitFor({ state: "visible", timeout: 15_000 });
}

/** Select an option in a native `<select>` (used for `tipe` and `status`). */
async function selectNative(
    page: Page,
    id: string,
    optionLabel: string,
): Promise<void> {
    await page.locator(`[id="${id}"]`).selectOption({ label: optionLabel });
    await waitForLivewireIdle(page);
}

/**
 * Pick an option in a Filament Combobox (button.fi-select-input-btn).
 * `idFragment` is a stable suffix, e.g. `.obat_id` — the repeater row UUID is
 * dynamic, so we match on the suffix. We scope only to the visible dropdown
 * because closed listboxes stay in the DOM (their options are hidden).
 */
async function comboboxPick(
    page: Page,
    idFragment: string,
    optionText: string,
): Promise<void> {
    const trigger = page.locator(`button[id*="${idFragment}"]`).first();
    await trigger.click();
    await page
        .locator('[role="listbox"]:visible [role="option"]')
        .filter({ hasText: optionText })
        .first()
        .click();
    await waitForLivewireIdle(page);
}

/** Pick the first available option in a Filament Combobox (used for batch). */
async function comboboxPickFirst(
    page: Page,
    idFragment: string,
): Promise<void> {
    const trigger = page.locator(`button[id*="${idFragment}"]`).first();
    await trigger.click();
    await page
        .locator('[role="listbox"]:visible [role="option"]')
        .first()
        .click();
    await waitForLivewireIdle(page);
}

/** Fill a field whose id matches a stable suffix inside the repeatable item. */
async function fillItem(
    page: Page,
    idFragment: string,
    value: string,
): Promise<void> {
    const input = page.locator(`input[id*="${idFragment}"]`).first();
    await input.fill(value);
    await input.dispatchEvent("blur");
    await waitForLivewireIdle(page);
}

function getStatusFromDb(nomorOpname: string): string {
    const result = execSync(
        `php artisan tinker --execute "echo DB::table('opname_stok')->where('nomor_opname', '${nomorOpname}')->value('status');"`,
        { cwd: process.cwd() },
    ).toString();
    return result.trim();
}

// ─────────────────────────────────────────────
// Spec
// ─────────────────────────────────────────────

test.describe("Stok Opname: Gudang & Puskesmas", () => {
    test.beforeEach(async ({ page }) => {
        await page.context().clearCookies();
    });

    test("Gudang: membuat opname penyesuaian (1 item) → selesai", async ({
        page,
    }) => {
        test.setTimeout(120_000);

        const nomorOpname = `STRK-TEST-PENY-${Date.now()}`;
        const obat = "Albendazole Tablet 400 mg";

        await login(page, CREDENTIALS.adminGudang, CREDENTIALS.password);
        await page.goto(`${BASE_URL}/admin/stok-opname`, {
            waitUntil: "commit",
            timeout: 15_000,
        });
        await page.waitForLoadState("domcontentloaded", { timeout: 10_000 });

        await openCreateModal(page);

        // Isi field utama.
        await selectNative(
            page,
            "mountedActionSchema0.tipe",
            "Penyesuaian (Stok Existing)",
        );
        await selectNative(page, "mountedActionSchema0.status", "Selesai");
        await page
            .locator('[id="mountedActionSchema0.nomor_opname"]')
            .fill(nomorOpname);

        // Isi item pertama: obat + batch + stok fisik.
        await comboboxPick(page, ".obat_id", obat);
        await comboboxPickFirst(page, ".batch_id");
        await fillItem(page, ".stok_fisik", "490");

        // Submit.
        await page.getByRole("button", { name: "Buat Stok Opname" }).click();
        await page.waitForTimeout(1200);
        await waitForLivewireIdle(page);

        // Verifikasi: nomor opname muncul di list (kolom obat tidak ada di list).
        await expect(
            page.locator(`a:has-text("${nomorOpname}")`).first(),
        ).toBeVisible({ timeout: 10_000 });

        // Verifikasi status di DB = selesai (karena memicu koreksi stok).
        expect(getStatusFromDb(nomorOpname)).toBe("selesai");
    });

    test("Puskesmas: membuat opname stok_awal (1 item) → selesai", async ({
        page,
    }) => {
        test.setTimeout(120_000);

        const nomorOpname = `STRK-TEST-AWAL-${Date.now()}`;
        const obat = "Parasetamol Tablet 500 mg";

        await login(page, CREDENTIALS.puskesmas, CREDENTIALS.password);
        await page.goto(`${BASE_URL}/admin/stok-opname`, {
            waitUntil: "commit",
            timeout: 15_000,
        });
        await page.waitForLoadState("domcontentloaded", { timeout: 10_000 });

        await openCreateModal(page);

        // Isi field utama.
        await selectNative(page, "mountedActionSchema0.tipe", "Stok Awal");
        await selectNative(page, "mountedActionSchema0.status", "Selesai");
        await page
            .locator('[id="mountedActionSchema0.nomor_opname"]')
            .fill(nomorOpname);

        // Isi item pertama: obat + stok fisik (tipe stok_awal tidak punya batch).
        await comboboxPick(page, ".obat_id", obat);
        await fillItem(page, ".stok_fisik", "50");

        // Submit.
        await page.getByRole("button", { name: "Buat Stok Opname" }).click();
        await page.waitForTimeout(1200);
        await waitForLivewireIdle(page);

        // Verifikasi: nomor opname muncul di list.
        await expect(
            page.locator(`a:has-text("${nomorOpname}")`).first(),
        ).toBeVisible({ timeout: 10_000 });

        // Verifikasi status di DB = selesai.
        expect(getStatusFromDb(nomorOpname)).toBe("selesai");
    });
});
