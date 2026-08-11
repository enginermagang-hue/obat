<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PanduanController extends Controller
{
    private array $panduan = [
        [
            'slug' => 'overview',
            'judul' => 'Overview RUANG OBAT',
            'deskripsi' => 'Pengenalan Sistem Informasi Manajemen Obat, tujuan, dan hierarki fasilitas kesehatan.',
            'ikon' => 'heroicon-o-academic-cap',
            'section' => 'panduan-dasar',
        ],
        [
            'slug' => 'peran-pengguna',
            'judul' => 'Peran Pengguna',
            'deskripsi' => 'Penjelasan setiap peran: Super Admin, Admin Dinas, Admin Gudang, Puskesmas, dan Pustu.',
            'ikon' => 'heroicon-o-user-group',
            'section' => 'panduan-dasar',
        ],
        [
            'slug' => 'fasilitas',
            'judul' => 'Fasilitas Kesehatan',
            'deskripsi' => 'Pengelolaan data Puskesmas dan Pustu dalam hierarki fasilitas kesehatan.',
            'ikon' => 'heroicon-o-building-office-2',
            'section' => 'panduan-dasar',
        ],
        [
            'slug' => 'obat',
            'judul' => 'Manajemen Obat',
            'deskripsi' => 'Pengelolaan data master obat, kategori VEN, dan metode stok (FEFO/FIFO/LIFO).',
            'ikon' => 'heroicon-o-beaker',
            'section' => 'panduan-dasar',
        ],
        [
            'slug' => 'supplier',
            'judul' => 'Manajemen Supplier',
            'deskripsi' => 'Pengelolaan data supplier/penyedia obat, termasuk informasi kontak, NPWP, dan status keaktifan.',
            'ikon' => 'heroicon-o-truck',
            'section' => 'panduan-dasar',
        ],
        [
            'slug' => 'permintaan-distribusi',
            'judul' => 'Permintaan & Distribusi',
            'deskripsi' => 'Alur permintaan obat dari Pustu ke Puskesmas, dan distribusi obat antar fasilitas.',
            'ikon' => 'heroicon-o-arrow-right-arrow-left',
            'section' => 'fitur-utama',
        ],
        [
            'slug' => 'penerimaan-obat',
            'judul' => 'Penerimaan Obat',
            'deskripsi' => 'Panduan menerima obat melalui pembelian, hibah, distribusi, atau metode lainnya ke gudang dan fasilitas.',
            'ikon' => 'heroicon-o-archive-box-arrow-down',
            'section' => 'fitur-utama',
        ],
        [
            'slug' => 'stok-batch-fefo',
            'judul' => 'Stok Batch & FEFO',
            'deskripsi' => 'Cara kerja manajemen stok berbasis batch, tanggal kadaluarsa, dan sistem FEFO.',
            'ikon' => 'heroicon-o-cube',
            'section' => 'fitur-utama',
        ],
        [
            'slug' => 'stok-opname',
            'judul' => 'Stok Opname',
            'deskripsi' => 'Panduan melakukan stok opname untuk menyesuaikan stok fisik dengan data sistem.',
            'ikon' => 'heroicon-o-clipboard-document-check',
            'section' => 'fitur-utama',
        ],
        [
            'slug' => 'laporan',
            'judul' => 'Laporan & Dokumen',
            'deskripsi' => 'Panduan mencetak LPLPO, RKO, Neraca Tahunan, dan faktur distribusi/permintaan.',
            'ikon' => 'heroicon-o-document-text',
            'section' => 'laporan-ai',
        ],
        [
            'slug' => 'sumber-dana',
            'judul' => 'Sumber Dana Obat',
            'deskripsi' => 'Pengelolaan data sumber dana (anggaran) untuk pengadaan dan penggunaan obat.',
            'ikon' => 'heroicon-o-currency-dollar',
            'section' => 'laporan-ai',
        ],
        [
            'slug' => 'ai-prediksi',
            'judul' => 'AI Prediksi Kebutuhan',
            'deskripsi' => 'Menggunakan model Rubix ML untuk meramalkan kebutuhan obat masa depan.',
            'ikon' => 'heroicon-o-cpu-chip',
            'section' => 'laporan-ai',
        ],
    ];

    private array $sections = [
        [
            'id' => 'panduan-dasar',
            'judul' => 'Panduan Dasar',
            'children' => ['overview', 'peran-pengguna', 'fasilitas', 'obat', 'supplier', 'sumber-dana'],
        ],
        [
            'id' => 'fitur-utama',
            'judul' => 'Fitur Utama',
            'children' => ['permintaan-distribusi', 'penerimaan-obat', 'stok-batch-fefo', 'stok-opname'],
        ],
        [
            'id' => 'laporan-ai',
            'judul' => 'Laporan & AI',
            'children' => ['laporan', 'ai-prediksi'],
        ],
    ];

    public function index(): View
    {
        $panduan = $this->panduan;

        $sidebar = $this->buildSidebar();

        return view('panduan.index', compact('panduan', 'sidebar'));
    }

    public function show(string $slug): View
    {
        $item = collect($this->panduan)->firstWhere('slug', $slug);

        if (! $item) {
            abort(404);
        }

        $path = resource_path("docs/{$slug}.md");

        if (! file_exists($path)) {
            abort(404);
        }

        $konten = file_get_contents($path);
        $html = $this->markdownToHtml($konten);
        $judul = $item['judul'];

        $sidebar = $this->buildSidebar($slug);

        return view('panduan.show', compact('html', 'slug', 'judul', 'sidebar'));
    }

    private function buildSidebar(?string $activeSlug = null): array
    {
        $panduanBySlug = collect($this->panduan)->keyBy('slug');

        return collect($this->sections)->map(function ($section) use ($panduanBySlug, $activeSlug) {
            $children = collect($section['children'])->map(function ($slug) use ($panduanBySlug, $activeSlug) {
                $item = $panduanBySlug->get($slug);

                return [
                    'slug' => $slug,
                    'judul' => $item['judul'] ?? $slug,
                    'deskripsi' => $item['deskripsi'] ?? '',
                    'active' => $slug === $activeSlug,
                ];
            })->all();

            return [
                'id' => $section['id'],
                'judul' => $section['judul'],
                'children' => $children,
            ];
        })->all();
    }

    private function markdownToHtml(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $html = '';
        $inList = false;
        $listType = null;
        $inCode = false;
        $codeLanguage = '';
        $codeBuffer = '';
        $inTable = false;
        $tableHeaders = [];
        $tableRows = [];

        $flushList = function () use (&$html, &$inList, &$listType) {
            if ($inList) {
                $html .= $listType === 'ol' ? "</ol>\n" : "</ul>\n";
                $inList = false;
                $listType = null;
            }
        };

        $flushTable = function () use (&$html, &$inTable, &$tableHeaders, &$tableRows) {
            if ($inTable) {
                $html .= $this->renderTable($tableHeaders, $tableRows);
                $inTable = false;
                $tableHeaders = [];
                $tableRows = [];
            }
        };

        $count = count($lines);
        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];
            $trimmed = trim($line);

            if ($trimmed === '' || $trimmed === "\n") {
                // Do not flush lists on empty lines to allow multi-line lists
                $flushTable();
                if ($inCode) {
                    $codeBuffer = '';
                }

                continue;
            }

            if (str_starts_with($trimmed, '```')) {
                $flushList();
                $flushTable();
                if (! $inCode) {
                    $codeLanguage = str_replace(['```', '```bash'], '', $trimmed);
                    $codeBuffer = '';
                    $inCode = true;

                    continue;
                }
                $inCode = false;
                $escaped = e($codeBuffer);
                $html .= '<pre><code class="language-'.$codeLanguage.'">'.$escaped."</code></pre>\n";

                continue;
            }

            if ($inCode) {
                $codeBuffer .= $line."\n";

                continue;
            }

            // Table detection: a line starting with | and next line is a separator
            if (preg_match('/^\|.*\|\s*$/', $trimmed)) {
                $nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
                if (! $inTable && preg_match('/^\|[\s:|-]+\|\s*$/', $nextLine)) {
                    $flushList();
                    $inTable = true;
                    $tableHeaders = $this->parseTableRow($trimmed);
                    $i++;

                    continue;
                }
                if ($inTable) {
                    $tableRows[] = $this->parseTableRow($trimmed);

                    continue;
                }
            } elseif ($inTable) {
                $flushTable();
            }

            if (str_starts_with($trimmed, '### ')) {
                $flushList();
                $flushTable();
                $text = e(substr($trimmed, 4));
                $anchor = strtolower(preg_replace('/[^a-z0-9]+/', '-', $text));
                $html .= '<h3 class="scroll-mt-24" id="'.$anchor.'">'
                    .$text
                    ."<a href=\"#{$anchor}\" class=\"anchor-link\">#</a></h3>\n";

                continue;
            }
            if (str_starts_with($trimmed, '## ')) {
                $flushList();
                $flushTable();
                $text = e(substr($trimmed, 3));
                $anchor = strtolower(preg_replace('/[^a-z0-9]+/', '-', $text));
                $html .= '<h2 class="scroll-mt-24" id="'.$anchor.'">'
                    .$text
                    ."<a href=\"#{$anchor}\" class=\"anchor-link\">#</a></h2>\n";

                continue;
            }
            if (str_starts_with($trimmed, '# ')) {
                $flushList();
                $flushTable();
                $text = e(substr($trimmed, 2));
                $html .= '<h1>'.$text."</h1>\n";

                continue;
            }

            if (str_starts_with($trimmed, '- ')) {
                $flushTable();
                if ($inList && $listType !== 'ul') {
                    $html .= $listType === 'ol' ? "</ol>\n" : "</ul>\n";
                    $inList = false;
                }
                if (! $inList) {
                    $html .= "<ul class=\"pl-6\">\n";
                    $inList = true;
                    $listType = 'ul';
                }
                $itemText = ltrim(substr($trimmed, 2), ' ');
                $itemText = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $itemText);
                $html .= '<li>'.$itemText."</li>\n";

                continue;
            }

            if (preg_match('/^(\d+)\.\s/', $trimmed, $matches)) {
                $flushTable();
                $startNum = $matches[1];
                if ($inList && $listType !== 'ol') {
                    $html .= "</ul>\n";
                    $inList = false;
                }
                if (! $inList) {
                    $startAttr = $startNum > 1 ? ' start="'.$startNum.'"' : '';
                    $html .= "<ol {$startAttr}>\n";
                    $inList = true;
                    $listType = 'ol';
                }
                $itemText = ltrim(preg_replace('/^\d+\.\s/', '', $trimmed));
                $itemText = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $itemText);
                $html .= '<li>'.$itemText."</li>\n";

                continue;
            }

            if (str_starts_with($trimmed, '> ')) {
                $flushList();
                $flushTable();
                $text = e(ltrim(substr($trimmed, 2)));
                $html .= '<blockquote>'.$text."</blockquote>\n";

                continue;
            }

            $flushList();

            if (str_starts_with($trimmed, '![')) {
                $flushList();
                preg_match('/!\[(.*?)\]\((.*?)\)/', $trimmed, $matches);
                if (! empty($matches)) {
                    $alt = e($matches[1]);
                    $src = e($matches[2]);
                    $html .= '<figure class="flex flex-col items-center"><img src="'.$src.'" alt="'.$alt.'"><figcaption>'.$alt.'</figcaption></figure>'."\n";

                    continue;
                }
            }

            $trimmed = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $trimmed);
            $trimmed = preg_replace('/`(.+?)`/', '<code>$1</code>', $trimmed);
            $trimmed = preg_replace('/\[(.+?)\]\((.*?)\)/', '<a href="$2" target="_blank">$1</a>', $trimmed);
            $html .= '<p>'.$trimmed."</p>\n";
        }

        $flushList();
        $flushTable();
        if ($inCode) {
            $html .= '<pre><code>'.e($codeBuffer)."</code></pre>\n";
        }

        return $html;
    }

    private function parseTableRow(string $line): array
    {
        $line = trim($line);
        $line = trim($line, '|');
        $cells = explode('|', $line);

        return array_map(function ($cell) {
            $cell = trim($cell);
            $cell = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $cell);
            $cell = preg_replace('/`(.+?)`/', '<code>$1</code>', $cell);

            return $cell;
        }, $cells);
    }

    private function renderTable(array $headers, array $rows): string
    {
        $html = "<table>\n<thead>\n<tr>\n";
        foreach ($headers as $cell) {
            $html .= '<th>'.e($cell)."</th>\n";
        }
        $html .= "</tr>\n</thead>\n<tbody>\n";

        foreach ($rows as $row) {
            $html .= "<tr>\n";
            foreach ($row as $cell) {
                $html .= '<td>'.$cell."</td>\n";
            }
            $html .= "</tr>\n";
        }

        $html .= "</tbody>\n</table>\n";

        return $html;
    }
}
