// Theme Toggle Management
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (document.body) {
        document.body.setAttribute('data-theme', theme);
        document.body.setAttribute('data-bs-theme', theme);
    }
    try {
        localStorage.setItem('oms_theme', theme);
    } catch (e) {}

    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        if (theme === 'dark') {
            themeIcon.className = 'bi bi-sun-fill';
            themeIcon.style.color = '#fbbf24';
        } else {
            themeIcon.className = 'bi bi-moon-stars-fill';
            themeIcon.style.color = '#6366f1';
        }
    }

    const toggleBtn = document.getElementById('themeToggleBtn');
    if (toggleBtn) {
        toggleBtn.setAttribute('title', theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode');
        toggleBtn.setAttribute('aria-label', theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode');
    }
}

function initTheme() {
    let savedTheme = 'dark';
    try {
        savedTheme = localStorage.getItem('oms_theme') || 'dark';
    } catch (e) {}
    applyTheme(savedTheme);

    const toggleBtn = document.getElementById('themeToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(nextTheme);
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTheme);
} else {
    initTheme();
}

// =====================================================================
// Sidebar Collapse Toggle
// =====================================================================
(function () {
    const STORAGE_KEY = 'oms_sidebar_collapsed';

    function applySidebarState(collapsed, animate) {
        const app     = document.querySelector('.app');
        const sidebar = document.getElementById('appSidebar');
        if (!app || !sidebar) return;

        if (!animate) {
            // Suppress all transitions on first load so there's no flash
            app.style.transition     = 'none';
            sidebar.style.transition = 'none';
            // Force reflow
            void app.offsetWidth;
        }

        if (collapsed) {
            app.classList.add('sidebar-collapsed');
        } else {
            app.classList.remove('sidebar-collapsed');
        }

        if (!animate) {
            // Re-enable transitions after paint
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    app.style.transition     = '';
                    sidebar.style.transition = '';
                });
            });
        }

        try { localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0'); } catch (e) {}
    }

    function initSidebar() {
        let collapsed = false;
        try { collapsed = localStorage.getItem(STORAGE_KEY) === '1'; } catch (e) {}

        // Apply immediately without animation
        applySidebarState(collapsed, false);

        // Hand off from pre-collapse CSS class (set in <head>) to JS-managed class
        document.documentElement.classList.remove('sidebar-pre-collapsed');

        // Collapse button (in full brand header)
        document.getElementById('sidebarCollapseBtn')?.addEventListener('click', (e) => {
            e.stopPropagation();
            applySidebarState(true, true);
        });

        // Expand: mini-logo-wrap is a div with role=button — handle click + keyboard
        const expandEl = document.getElementById('sidebarExpandBtn');
        if (expandEl) {
            expandEl.addEventListener('click', () => applySidebarState(false, true));
            expandEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    applySidebarState(false, true);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();

// =====================================================================
// Universal Action Button Loading Spinner & Form Submission Handler
// =====================================================================
(function () {
    let lastClickedSubmitBtn = null;

    // Track the exact button that triggered the form submission
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('button[type="submit"], input[type="submit"], button:not([type])');
        if (btn && btn.form) {
            lastClickedSubmitBtn = btn;
        }
    }, true);

    // Global form submission interceptor for confirmation & action spinners
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!form || !(form instanceof HTMLFormElement)) return;

        // 1. Handle confirmation dialogs
        const confirmMsg = form.dataset.confirm || form.getAttribute('data-confirm');
        if (confirmMsg) {
            if (!window.confirm(confirmMsg)) {
                event.preventDefault();
                event.stopImmediatePropagation();
                lastClickedSubmitBtn = null;
                return;
            }
        }

        // 2. Validate form inputs (if HTML5 validation fails, do not lock button)
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            lastClickedSubmitBtn = null;
            return;
        }

        // 3. Skip GET search/filter forms (handled smoothly via AJAX in app.js)
        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') {
            return;
        }

        // 4. Prevent duplicate rapid double-submissions
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = 'true';

        // 5. Find target submit button
        const btn = lastClickedSubmitBtn && form.contains(lastClickedSubmitBtn)
            ? lastClickedSubmitBtn
            : form.querySelector('button[type="submit"], input[type="submit"], button.btn-primary, button.btn-danger, button.btn-success, button.btn-outline-danger, button.btn-outline-primary');

        if (btn) {
            // Lock dimensions so button does not jump or collapse
            const rect = btn.getBoundingClientRect();
            if (rect.width > 0) {
                btn.style.minWidth = `${rect.width}px`;
            }

            // Save original state for potential bfcache restore
            btn.dataset.originalHtml = btn.innerHTML;

            const icon = btn.querySelector('i');
            const hasVisibleText = btn.textContent.trim().length > 0;
            const isIconOnly = btn.classList.contains('btn-icon') || (!hasVisibleText && icon);

            if (isIconOnly) {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            } else if (icon) {
                icon.className = 'spinner-border spinner-border-sm me-1';
                icon.setAttribute('role', 'status');
                icon.setAttribute('aria-hidden', 'true');
            } else {
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> ' + btn.innerHTML;
            }

            btn.classList.add('disabled', 'btn-loading');
            btn.style.pointerEvents = 'none';

            // Disable natively right after event dispatch to allow submit data
            setTimeout(() => {
                btn.disabled = true;
            }, 0);
        }

        // Disable all other buttons in this form to prevent duplicate actions
        form.querySelectorAll('button:not(.btn-loading), input[type="submit"]:not(.btn-loading)').forEach((otherBtn) => {
            otherBtn.classList.add('disabled');
            otherBtn.style.pointerEvents = 'none';
        });
    });

    // Restore buttons if user navigates back using browser Back button (bfcache)
    window.addEventListener('pageshow', (e) => {
        document.querySelectorAll('.btn-loading, [data-original-html]').forEach((btn) => {
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
            btn.disabled = false;
            btn.classList.remove('disabled', 'btn-loading');
            btn.style.pointerEvents = '';
            btn.style.minWidth = '';
        });
        document.querySelectorAll('form[data-submitting]').forEach((f) => {
            delete f.dataset.submitting;
            f.querySelectorAll('button.disabled, input.disabled').forEach((b) => {
                b.disabled = false;
                b.classList.remove('disabled');
                b.style.pointerEvents = '';
            });
        });
        lastClickedSubmitBtn = null;
    });
})();

// =====================================================================
// Mobile Offcanvas Sidebar Drawer Controller
// =====================================================================
(function () {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.querySelector('[data-sidebar-toggle]');
    const closeBtn = document.getElementById('sidebarMobileCloseBtn');

    function openMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    function toggleMobileSidebar() {
        if (!sidebar) return;
        if (sidebar.classList.contains('open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    toggleBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMobileSidebar();
    });

    closeBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        closeMobileSidebar();
    });

    backdrop?.addEventListener('click', () => {
        closeMobileSidebar();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar?.classList.contains('open')) {
            closeMobileSidebar();
        }
    });

    sidebar?.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && e.target.closest('.nav-link')) {
            closeMobileSidebar();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992 && sidebar?.classList.contains('open')) {
            closeMobileSidebar();
        }
    }, { passive: true });
})();

document.querySelectorAll('[data-progress]').forEach((bar) => {
    const value = Math.max(0, Math.min(100, Number(bar.dataset.progress || 0)));
    bar.style.width = `${value}%`;
});

document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});


const liveClock = document.querySelector('[data-live-clock]');
if (liveClock) {
    setInterval(() => {
        liveClock.textContent = new Intl.DateTimeFormat('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit'
        }).format(new Date());
    }, 1000);
}

if (document.body.classList.contains('public-screen')) {
    document.addEventListener('dblclick', () => {
        if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    });
}

// =====================================================================
// OMS Custom Multiselect
// =====================================================================
(function () {
    const wrapper  = document.getElementById('assignedToWrapper');
    if (!wrapper) return;

    const trigger   = document.getElementById('msTrigger');
    const tagsEl    = document.getElementById('msTags');
    const dropdown  = document.getElementById('msDropdown');
    const searchEl  = document.getElementById('msSearch');
    const optionsEl = document.getElementById('msOptions');
    const chevron   = document.getElementById('msChevron');

    // Employee data pulled from option labels
    const employeeMap = {}; // id => name
    wrapper.querySelectorAll('.ms-option').forEach(opt => {
        const id   = opt.dataset.id;
        const name = opt.querySelector('strong')?.textContent?.trim() || '';
        employeeMap[id] = name;
    });

    function getSelected() {
        return [...wrapper.querySelectorAll('.ms-hidden-input')].map(i => i.value);
    }

    function setSelected(ids) {
        // Remove existing hidden inputs
        wrapper.querySelectorAll('.ms-hidden-input').forEach(i => i.remove());
        // Re-add
        ids.forEach(id => {
            const inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'employee_ids[]';
            inp.value = id;
            inp.className = 'ms-hidden-input';
            wrapper.appendChild(inp);
        });
    }

    function renderTags() {
        const ids = getSelected().filter(id => employeeMap[id] !== undefined);
        setSelected(ids);
        tagsEl.innerHTML = '';
        if (ids.length === 0) {
            tagsEl.innerHTML = '<span class="ms-placeholder" id="msPlaceholder">Select team members…</span>';
        } else {
            ids.forEach(id => {
                const name = employeeMap[id];
                const tag  = document.createElement('span');
                tag.className  = 'ms-tag';
                tag.dataset.id = id;
                tag.innerHTML  = `${name} <button type="button" class="ms-tag-remove" data-id="${id}">&times;</button>`;
                tagsEl.appendChild(tag);
            });
        }
        bindTagRemove();
    }

    function bindTagRemove() {
        tagsEl.querySelectorAll('.ms-tag-remove').forEach(btn => {
            btn.addEventListener('click', e => {
                e.stopPropagation();
                const id  = btn.dataset.id;
                const ids = getSelected().filter(i => i !== id);
                setSelected(ids);
                updateOption(id, false);
                renderTags();
            });
        });
    }

    function updateOption(id, checked) {
        const opt = optionsEl.querySelector(`.ms-option[data-id="${id}"]`);
        if (!opt) return;
        const box = opt.querySelector('.ms-checkbox');
        if (checked) {
            opt.classList.add('ms-option-checked');
            box.classList.add('checked');
            box.innerHTML = '<i class="bi bi-check2"></i>';
        } else {
            opt.classList.remove('ms-option-checked');
            box.classList.remove('checked');
            box.innerHTML = '';
        }
    }

    function updateDropdownPosition() {
        if (dropdown.style.display === 'none') return;
        const rect = trigger.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;
        // Require at least 230px below to open downwards; otherwise flip upward
        const minHeightNeeded = 230;

        if (spaceBelow < minHeightNeeded && spaceAbove > spaceBelow) {
            dropdown.classList.add('ms-dropdown-up');
        } else {
            dropdown.classList.remove('ms-dropdown-up');
        }
    }

    function openDropdown() {
        dropdown.style.display = 'block';
        chevron.style.transform = 'rotate(180deg)';
        updateDropdownPosition();
        searchEl.focus();
    }

    function closeDropdown() {
        dropdown.style.display = 'none';
        dropdown.classList.remove('ms-dropdown-up');
        chevron.style.transform = '';
        searchEl.value = '';
        filterOptions('');
    }

    function filterOptions(q) {
        const lq = q.toLowerCase();
        optionsEl.querySelectorAll('.ms-option').forEach(opt => {
            const text = opt.querySelector('.ms-option-text')?.textContent?.toLowerCase() || '';
            opt.style.display = text.includes(lq) ? '' : 'none';
        });
        updateDropdownPosition();
    }

    trigger.addEventListener('click', e => {
        if (e.target.classList.contains('ms-tag-remove')) return;
        dropdown.style.display === 'none' ? openDropdown() : closeDropdown();
    });

    trigger.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDropdown(); }
        if (e.key === 'Escape') closeDropdown();
    });

    searchEl.addEventListener('input', () => filterOptions(searchEl.value));

    window.addEventListener('resize', updateDropdownPosition, { passive: true });
    window.addEventListener('scroll', updateDropdownPosition, { passive: true, capture: true });

    optionsEl.addEventListener('click', e => {
        const opt = e.target.closest('.ms-option');
        if (!opt) return;
        const id = opt.dataset.id;
        const ids = getSelected();
        if (ids.includes(id)) {
            setSelected(ids.filter(i => i !== id));
            updateOption(id, false);
        } else {
            setSelected([...ids, id]);
            updateOption(id, true);
        }
        renderTags();
        updateDropdownPosition();
    });

    document.addEventListener('click', e => {
        if (!wrapper.contains(e.target)) closeDropdown();
    });

    bindTagRemove();
})();

// =====================================================================
// Seamless AJAX Pagination & Search (Zero Whole-Page Refresh)
// =====================================================================
(function () {
    const pendingRequests = new Map();

    function initCardWidgets(container) {
        if (!container) return;
        // Re-initialize progress bars in swapped content
        container.querySelectorAll('[data-progress]').forEach((bar) => {
            const value = Math.max(0, Math.min(100, Number(bar.dataset.progress || 0)));
            bar.style.width = `${value}%`;
        });
    }

    async function loadCardAjax(card, url, pushHistory = true) {
        if (!card || !url) return;

        // Abort any ongoing request for this specific card
        if (pendingRequests.has(card)) {
            pendingRequests.get(card).abort();
        }

        const controller = new AbortController();
        pendingRequests.set(card, controller);

        card.classList.add('is-loading');

        try {
            const res = await fetch(url, {
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const html = await res.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');

            let newCard = null;
            if (card.id) {
                newCard = doc.getElementById(card.id);
            }
            if (!newCard) {
                // Fallback matching by card header title
                const curTitle = card.querySelector('.card-header strong')?.textContent?.trim();
                if (curTitle) {
                    const headers = doc.querySelectorAll('.card .card-header strong');
                    for (const h of headers) {
                        if (h.textContent.trim() === curTitle) {
                            newCard = h.closest('.card');
                            break;
                        }
                    }
                }
            }

            if (newCard) {
                card.innerHTML = newCard.innerHTML;
                initCardWidgets(card);
                if (pushHistory) {
                    window.history.pushState({ omsCardId: card.id, omsUrl: url }, '', url);
                }
            } else {
                // If structure differs, fallback to standard navigation
                window.location.href = url;
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                console.warn('AJAX load fallback:', err);
                window.location.href = url;
            }
        } finally {
            card.classList.remove('is-loading');
            pendingRequests.delete(card);
        }
    }

    // 1. Intercept pagination links inside card footers or card tables
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0) return;
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

        // Target pagination links inside card footer, btn-groups, or pagination wrappers
        const link = e.target.closest('.card-footer a, .card-footer .btn-group a, .pagination a');
        if (!link) return;

        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.hasAttribute('target') && link.getAttribute('target') !== '_self') return;
        if (link.hasAttribute('download') || link.hasAttribute('data-print')) return;

        const card = link.closest('.card');
        if (card) {
            e.preventDefault();
            loadCardAjax(card, href, true);
        }
    });

    // 2. Intercept GET search/filter forms inside cards
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form || (form.getAttribute('method') || 'GET').toUpperCase() !== 'GET') return;

        const card = form.closest('.card');
        if (!card) return;

        e.preventDefault();
        const action = form.getAttribute('action') || window.location.pathname;
        const formData = new FormData(form);
        const params = new URLSearchParams(formData);

        // Preserve other existing search params from window.location that this form doesn't control
        const curParams = new URLSearchParams(window.location.search);
        for (const [k, v] of params.entries()) {
            if (v.trim() !== '') {
                curParams.set(k, v);
            } else {
                curParams.delete(k);
            }
        }
        // When searching, reset main page to 1
        curParams.delete('page');
        curParams.delete('page_active');
        curParams.delete('page_completed');

        const finalUrl = action + (curParams.toString() ? '?' + curParams.toString() : '');
        loadCardAjax(card, finalUrl, true);
    });

    // 3. Handle browser back / forward buttons seamlessly
    window.addEventListener('popstate', () => {
        const cards = document.querySelectorAll('.card[id]');
        if (cards.length > 0) {
            cards.forEach(card => loadCardAjax(card, window.location.href, false));
        } else {
            window.location.reload();
        }
    });

    // 4. Delegated auto-submit for status selects
    document.addEventListener('change', (e) => {
        const field = e.target.closest('[data-auto-submit]');
        if (field && field.form) {
            const card = field.closest('.card') || field.closest('.oms-table-wrapper');
            if (card) {
                card.classList.add('is-loading');
            }
            field.form.submit();
        }
    });
})();

// =====================================================================
// Announcements: Edit Modal & Toast Smooth Scroll
// =====================================================================
(function () {
    // Edit Modal Population
    const editModal = document.getElementById('editAnnouncementModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            if (!btn) return;
            const id = btn.dataset.id;
            const title = btn.dataset.title || '';
            const content = btn.dataset.content || '';
            const type = btn.dataset.type || 'General';
            const startDate = btn.dataset.startDate || '';
            const endDate = btn.dataset.endDate || '';

            const form = document.getElementById('editAnnouncementForm');
            if (form) {
                form.action = `dashboard/announcements/${id}/update`;
            }
            const titleInput = document.getElementById('editAnnTitle');
            const contentInput = document.getElementById('editAnnContent');
            const typeSelect = document.getElementById('editAnnType');
            const startDateInput = document.getElementById('editAnnStartDate');
            const endDateInput = document.getElementById('editAnnEndDate');

            if (titleInput) titleInput.value = title;
            if (contentInput) contentInput.value = content;
            if (typeSelect) typeSelect.value = type;
            if (startDateInput) startDateInput.value = startDate;
            if (endDateInput) endDateInput.value = endDate;
        });
    }

    // Toast "See Announcements" Smooth Scroll & Pulse Highlight
    document.addEventListener('click', (e) => {
        const toastLink = e.target.closest('#toastAnnouncementLink');
        if (!toastLink) return;

        e.preventDefault();
        const target = document.getElementById('announcementsBox');
        if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.classList.remove('announcements-highlight');
            void target.offsetWidth; // force reflow
            target.classList.add('announcements-highlight');
            setTimeout(() => {
                target.classList.remove('announcements-highlight');
            }, 2500);
        }
    });
})();

// =====================================================================
// Universal Password Visibility Toggle
// =====================================================================
(function () {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.toggle-password-btn');
        if (!btn) return;
        const targetId = btn.getAttribute('data-target');
        const input = targetId ? document.getElementById(targetId) : btn.closest('.input-group')?.querySelector('input');
        if (!input) return;
        const isPassword = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPassword ? 'text' : 'password');
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
    });
})();

// =====================================================================
// Date & Time Input Picker on Click Anywhere on Field
// =====================================================================
(function () {
    function triggerDatePicker(input) {
        if (!input || input.disabled || input.readOnly) return;
        if (typeof input.showPicker === 'function') {
            try {
                input.showPicker();
            } catch (err) {}
        }
    }

    document.addEventListener('click', function (e) {
        const dateInput = e.target.closest('input[type="date"], input[type="datetime-local"], input[type="time"], input[type="month"]');
        if (dateInput) {
            triggerDatePicker(dateInput);
        }
    });

    document.addEventListener('focusin', function (e) {
        if (e.target && e.target.matches && e.target.matches('input[type="date"], input[type="datetime-local"], input[type="time"], input[type="month"]')) {
            triggerDatePicker(e.target);
        }
    });
})();



