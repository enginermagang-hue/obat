document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initSidebarToggle();
    initSearchFilter();
    initSidebarCollapse();
    initTocBuilder();
    initTocScrollSpy();
});

function initThemeToggle() {
    const btn = document.getElementById('theme-toggle');
    const menu = document.getElementById('theme-menu');
    if (!btn || !menu) return;

    const applyTheme = () => {
        const theme = getTheme();
        const isDark = resolveDark(theme);
        document.documentElement.classList.toggle('dark', isDark);
        updateThemeIcon(theme, isDark);
        updateThemeCheck(theme);
    };

    applyTheme();

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    menu.querySelectorAll('[data-theme-set]').forEach((item) => {
        item.addEventListener('click', () => {
            const theme = item.dataset.themeSet;
            setTheme(theme);
            menu.classList.add('hidden');
            applyTheme();
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (getTheme() === 'system') applyTheme();
    });
}

function getTheme() {
    return localStorage.getItem('panduan-theme') || 'system';
}

function setTheme(theme) {
    localStorage.setItem('panduan-theme', theme);
}

function resolveDark(theme) {
    if (theme === 'dark') return true;
    if (theme === 'light') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function updateThemeIcon(theme, isDark) {
    const lightIcon = document.querySelector('[data-theme-icon-light]');
    const darkIcon = document.querySelector('[data-theme-icon-dark]');
    const systemIcon = document.querySelector('[data-theme-icon-system]');

    [lightIcon, darkIcon, systemIcon].forEach((el) => el && el.classList.add('hidden'));

    if (theme === 'light') {
        lightIcon?.classList.remove('hidden');
    } else if (theme === 'dark') {
        darkIcon?.classList.remove('hidden');
    } else {
        systemIcon?.classList.remove('hidden');
    }
}

function updateThemeCheck(theme) {
    document.querySelectorAll('[data-theme-check]').forEach((el) => {
        if (el.dataset.themeCheck === theme) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
}

function initSidebarToggle() {
    const toggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('panduan-sidebar');
    const backdrop = document.getElementById('mobile-backdrop');
    const closeBtn = document.getElementById('sidebar-close');

    if (!toggle || !sidebar || !backdrop) return;

    const open = () => {
        sidebar.classList.add('open');
        backdrop.classList.add('open');
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    };

    const close = () => {
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    };

    toggle.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', close);
    backdrop.addEventListener('click', close);

    document.querySelectorAll('#panduan-sidebar a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) close();
        });
    });
}

function initSearchFilter() {
    const input = document.getElementById('sidebar-search');
    if (!input) return;

    const items = Array.from(document.querySelectorAll('[data-search-item]'));
    const sections = Array.from(document.querySelectorAll('[data-search-section]'));
    const emptyState = document.getElementById('sidebar-empty');

    let debounceTimer;

    input.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const query = e.target.value.trim().toLowerCase();
            filterItems(query, items, sections, emptyState);
        }, 150);
    });
}

function filterItems(query, items, sections, emptyState) {
    if (query === '') {
        items.forEach((item) => {
            item.classList.remove('hidden');
            const match = item.querySelector('[data-search-match]');
            if (match) match.innerHTML = match.dataset.searchMatch;
        });
        sections.forEach((section) => {
            section.classList.remove('hidden');
            const toggle = section.querySelector('[data-section-toggle]');
            const body = section.querySelector('[data-section-body]');
            if (toggle && body) {
                body.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
        if (emptyState) emptyState.classList.add('hidden');
        return;
    }

    let visibleCount = 0;

    items.forEach((item) => {
        const titleEl = item.querySelector('[data-search-title]');
        const descEl = item.querySelector('[data-search-desc]');
        const title = titleEl ? titleEl.dataset.searchTitle.toLowerCase() : '';
        const desc = descEl ? descEl.dataset.searchDesc.toLowerCase() : '';

        if (title.includes(query) || desc.includes(query)) {
            item.classList.remove('hidden');
            visibleCount++;
            if (titleEl) titleEl.innerHTML = highlightMatch(titleEl.dataset.searchMatch, query);
        } else {
            item.classList.add('hidden');
            if (titleEl) titleEl.innerHTML = titleEl.dataset.searchMatch;
        }
    });

    sections.forEach((section) => {
        const hasVisible = section.querySelectorAll('[data-search-item]:not(.hidden)').length > 0;
        if (hasVisible) {
            section.classList.remove('hidden');
            const toggle = section.querySelector('[data-section-toggle]');
            const body = section.querySelector('[data-section-body]');
            if (toggle && body) {
                body.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            }
        } else {
            section.classList.add('hidden');
        }
    });

    if (emptyState) {
        if (visibleCount === 0) {
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
        }
    }
}

function highlightMatch(text, query) {
    if (!query) return text;
    const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${escaped})`, 'gi');
    return text.replace(regex, '<mark class="bg-primary-100 text-primary-800 rounded px-0.5">$1</mark>');
}

function initSidebarCollapse() {
    document.querySelectorAll('[data-section-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const body = toggle.nextElementSibling;
            const chevron = toggle.querySelector('[data-chevron]');
            if (!body) return;

            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                body.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
                if (chevron) chevron.classList.add('-rotate-90');
            } else {
                body.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
                if (chevron) chevron.classList.remove('-rotate-90');
            }
        });
    });
}

function initTocScrollSpy() {
    const headings = document.querySelectorAll('.prose-doc h2[id], .prose-doc h3[id]');
    const nav = document.getElementById('toc-nav');
    if (!headings.length || !nav) return;

    const links = Array.from(nav.querySelectorAll('a[href^="#"]'));
    if (!links.length) return;

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    links.forEach((link) => {
                        const href = link.getAttribute('href').replace('#', '');
                        if (href === id) {
                            link.classList.add('active');
                        } else {
                            link.classList.remove('active');
                        }
                    });
                }
            });
        },
        { rootMargin: '-80px 0px -60% 0px', threshold: 0 }
    );

    headings.forEach((h) => observer.observe(h));
}

function initTocBuilder() {
    const headings = document.querySelectorAll('.prose-doc h2[id], .prose-doc h3[id]');
    const nav = document.getElementById('toc-nav');
    if (!headings.length || !nav) return;

    nav.innerHTML = '';

    headings.forEach((h) => {
        const a = document.createElement('a');
        a.href = '#' + h.id;
        a.textContent = h.textContent.replace('#', '').trim();
        a.className = h.tagName === 'H3' ? 'toc-link toc-h3' : 'toc-link';
        nav.appendChild(a);
    });
}
