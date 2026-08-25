<?php

namespace Tests\Browser\Concerns;

use Laravel\Dusk\Browser;

trait FillsFilamentFields
{
    /**
     * Tekan tombol dengan retry - tahan stale element saat Livewire morph.
     * Fallback: klik via JS bila WebDriver tetap gagal.
     */
    private function tekan(Browser $browser, string $tombol): void
    {
        $terakhir = null;

        for ($i = 0; $i < 40; $i++) {
            try {
                $browser->press($tombol);

                return;
            } catch (\Throwable $e) {
                $terakhir = $e;
            }

            try {
                $klik = $browser->script(sprintf(
                    'return (() => { const b = [...document.querySelectorAll(\'button\')].find(x => x.textContent.trim() === %s && (x.offsetWidth || x.offsetHeight)); if (!b) return 0; b.click(); return 1; })();',
                    json_encode($tombol),
                ));

                if ((int) ($klik[0] ?? 0) === 1) {
                    return;
                }
            } catch (\Throwable $e) {
                $terakhir = $e;
            }

            usleep(250000);
        }

        $btns = $browser->script(
            'return JSON.stringify([...document.querySelectorAll("button")].map(b => b.textContent.trim()).filter(Boolean).slice(0, 40));',
        );

        throw new \RuntimeException("tekan '{$tombol}' gagal: {$terakhir?->getMessage()} | tombol tersedia: ".json_encode($btns));
    }

    /**
     * Set properti Livewire `data.<field>` pada komponen yang memuat tombol
     * acuan ($anchorText), lalu sinkron ke server.
     */
    private function setLivewireData(Browser $browser, string $anchorText, string $field, string $value): void
    {
        $hasil = $browser->script(sprintf(
            'return (() => { const btn = [...document.querySelectorAll(\'button\')].find(b => b.textContent.trim() === %1$s && (b.offsetWidth || b.offsetHeight)); const root = btn ? btn.closest(\'[wire\\\\:id]\') : null; const id = root ? root.getAttribute(\'wire:id\') : null; const c = id ? window.Livewire.find(id) : null; if (!c) return JSON.stringify({ ok: 0, alasan: \'komponen tidak ditemukan\' }); c.set(\'data.%2$s\', %3$s); return new Promise(resolve => setTimeout(() => { try { const s = typeof c.snapshot === \'string\' ? JSON.parse(c.snapshot) : c.snapshot; resolve(JSON.stringify({ ok: 1, nilai: s && s.data ? String(s.data.%2$s) : null })); } catch (e) { resolve(JSON.stringify({ ok: 1, nilai: \'set-ok-read-err\' })); } }, 800)); })();',
            json_encode($anchorText),
            $field,
            json_encode($value),
        ));

        $dekod = json_decode((string) ($hasil[0] ?? ''), true);
        if (($dekod['ok'] ?? 0) !== 1) {
            throw new \RuntimeException('setLivewireData gagal: '.($dekod['alasan'] ?? 'tidak diketahui'));
        }
        if (($dekod['nilai'] ?? '') !== $value) {
            throw new \RuntimeException("setLivewireData tidak tersinkron (terbaca: {$dekod['nilai']})");
        }
    }

    /**
     * Isi field (input/select) via native value setter + event input/change.
     * Tahan morph Livewire & komponen currency-mask yang tidak sinkron bila
     * diketik lewat WebDriver.
     *
     * ponytail: kembali ke type()/select() murni bila mask sudah entangle bersih.
     */
    private function isiField(Browser $browser, string $selector, string $value): void
    {
        $browser->script(sprintf(
            "(() => { const el = document.querySelector(%s); if (!el) throw new Error('tidak ketemu: %s'); const proto = el.tagName === 'SELECT' ? HTMLSelectElement : HTMLInputElement; Object.getOwnPropertyDescriptor(proto.prototype, 'value').set.call(el, %s); el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); })();",
            json_encode($selector),
            addslashes($selector),
            json_encode($value),
        ));
    }

    /**
     * Pilih opsi pada searchable select Filament: klik tombol pembuka,
     * isi kotak pencarian panel (select tanpa preload menunggu ketikan,
     * tanpa event change agar panel tak tertutup), tunggu opsi termuat
     * async, lalu klik li opsi (teks eksak) di dalam wrapper field yang sama.
     */
    private function pilihSearchable(Browser $browser, string $tail, string $label): void
    {
        $searchSel = 'div.fi-fo-select:has([id$="'.$tail.'"]) .fi-select-input-search-ctn input';

        $browser->click('[id$="'.$tail.'"]')
            ->waitUsing(5, 100, fn () => $browser->assertVisible($searchSel));

        // Hanya event input - event change membuat panel dropdown tertutup.
        $browser->script(sprintf(
            "(() => { const el = document.querySelector(%s); const proto = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set; proto.call(el, %s); el.dispatchEvent(new Event('input', { bubbles: true })); })();",
            json_encode($searchSel),
            json_encode($label),
        ));

        $browser->waitUsing(10, 200, function () use ($browser, $tail, $label): void {
            $klik = $browser->script(sprintf(
                'return (() => { const wrap = document.querySelector(\'div.fi-fo-select:has([id$="%s"])\'); if (!wrap) return 0; const btn = wrap.querySelector(\'button[id$="%s"]\'); if (btn && btn.textContent.trim() === %s) return 2; const el = [...wrap.querySelectorAll(\'li.fi-select-input-option\')].find(l => l.textContent.trim() === %s); if (!el) return 0; el.click(); return 1; })();',
                $tail,
                $tail,
                json_encode($label),
                json_encode($label),
            ));

            if (! in_array((int) ($klik[0] ?? 0), [1, 2], true)) {
                throw new \RuntimeException("opsi '{$label}' belum muncul");
            }
        });
    }
}
