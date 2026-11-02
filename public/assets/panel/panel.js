/*
 * IziCRM panel — umumiy JS: token, API so'rovlari, menyu, modal, toast, formatlash.
 * Sahifalar: Panel.ready.then(user => { ... }) ichida ishlaydi.
 */
(function () {
    'use strict';

    const TOKEN_KEY = 'auth_token';

    const storage = {
        get() { try { return localStorage.getItem(TOKEN_KEY); } catch (e) { return null; } },
        set(v) { try { localStorage.setItem(TOKEN_KEY, v); } catch (e) { /* private mode */ } },
        clear() { try { localStorage.removeItem(TOKEN_KEY); } catch (e) { /* private mode */ } },
    };

    function loginUrl() {
        const next = location.pathname + location.search;
        return '/login' + (next && next !== '/login' ? '?next=' + encodeURIComponent(next) : '');
    }

    function toLogin() {
        storage.clear();
        location.href = loginUrl();
    }

    class ApiError extends Error {
        constructor(status, data) {
            super((data && data.message) || 'Server bilan bog‘lanishda xatolik');
            this.status = status;
            this.errors = (data && data.errors) || {};
        }
    }

    async function api(path, { method = 'GET', body, query } = {}) {
        let url = '/api' + path;
        if (query) {
            const qs = new URLSearchParams();
            Object.entries(query).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined) qs.append(k, v); });
            if ([...qs].length) url += '?' + qs;
        }

        const headers = { Accept: 'application/json' };
        const token = storage.get();
        if (token) headers.Authorization = 'Bearer ' + token;
        if (body !== undefined) headers['Content-Type'] = 'application/json';

        let res;
        try {
            res = await fetch(url, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined });
        } catch (e) {
            throw new ApiError(0, { message: 'Internet yoki server bilan aloqa yo‘q' });
        }

        if (res.status === 401 && path !== '/login') {
            toLogin();
            throw new ApiError(401, { message: 'Sessiya tugadi' });
        }

        const data = await res.json().catch(() => null);
        if (!res.ok) {
            if (res.status === 403) throw new ApiError(403, { message: 'Bu bo‘limga ruxsatingiz yo‘q' });
            if (res.status === 429) throw new ApiError(429, { message: 'Juda ko‘p urinish. Birozdan keyin qayta urining.' });
            throw new ApiError(res.status, data);
        }
        return data;
    }

    async function download(path, query, filename) {
        const qs = new URLSearchParams();
        Object.entries(query || {}).forEach(([k, v]) => { if (v) qs.append(k, v); });
        const res = await fetch('/api' + path + ([...qs].length ? '?' + qs : ''), {
            headers: { Authorization: 'Bearer ' + storage.get() },
        });
        if (res.status === 401) return toLogin();
        if (!res.ok) throw new ApiError(res.status, { message: 'Faylni yuklab bo‘lmadi' });
        const url = URL.createObjectURL(await res.blob());
        const a = Object.assign(document.createElement('a'), { href: url, download: filename });
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    // ---------- Formatlash ----------
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pad = (n) => String(n).padStart(2, '0');

    const fmt = {
        date(v) {
            if (!v) return '—';
            const d = new Date(v);
            return isNaN(d) ? '—' : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
        },
        dateTime(v) {
            if (!v) return '—';
            const d = new Date(v);
            return isNaN(d) ? '—' : `${fmt.date(v)}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },
        longDate(v = new Date()) {
            const d = new Date(v);
            const months = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];
            const days = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];
            return `${d.getDate()}-${months[d.getMonth()]}, ${d.getFullYear()} · ${days[d.getDay()]}`;
        },
        number(n) {
            return Number(n || 0).toLocaleString('ru-RU').replace(/,/g, ' ');
        },
        money(n) {
            return fmt.number(Math.round(Number(n || 0))) + ' so‘m';
        },
        minutes(total) {
            total = Number(total || 0);
            const h = Math.floor(total / 60);
            const m = total % 60;
            if (!h) return `${m} daq`;
            return m ? `${h} soat ${m} daq` : `${h} soat`;
        },
        initials(name) {
            return String(name || '?').trim().split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase() || '?';
        },
    };

    // ---------- UI yordamchilari ----------
    function icon(name, cls = '') {
        return `<svg class="icon ${cls}" aria-hidden="true"><use href="#i-${name}"></use></svg>`;
    }

    function toast(message, type = 'info') {
        let box = document.querySelector('.toasts');
        if (!box) {
            box = Object.assign(document.createElement('div'), { className: 'toasts' });
            box.setAttribute('role', 'status');
            document.body.appendChild(box);
        }
        const el = Object.assign(document.createElement('div'), { className: 'toast ' + type, textContent: message });
        box.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }

    function openModal(id) {
        const m = document.getElementById(id);
        m.classList.add('open');
        const focusable = m.querySelector('input:not([type=hidden]):not([disabled]), select, textarea, button.btn');
        if (focusable) setTimeout(() => focusable.focus(), 30);
    }

    function closeModal(id) {
        const m = typeof id === 'string' ? document.getElementById(id) : id;
        if (!m) return;
        m.classList.remove('open');
        m.querySelectorAll('audio, video').forEach((el) => el.pause());
        m.dispatchEvent(new Event('modal:close'));
    }

    document.addEventListener('click', (e) => {
        const closer = e.target.closest('[data-close]');
        if (closer) return closeModal(closer.closest('.modal'));
        if (e.target.classList.contains('modal')) closeModal(e.target);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(closeModal);
    });

    function confirmDialog(message, { title = 'Tasdiqlang', okText = 'Ha', danger = false } = {}) {
        return new Promise((resolve) => {
            let m = document.getElementById('confirm-modal');
            if (!m) {
                m = document.createElement('div');
                m.className = 'modal';
                m.id = 'confirm-modal';
                m.innerHTML = `
                    <div class="modal-dialog sm" role="dialog" aria-modal="true">
                        <div class="modal-head"><h3></h3></div>
                        <div class="modal-body"><p style="margin:0"></p></div>
                        <div class="modal-foot">
                            <button class="btn secondary" data-close>Bekor qilish</button>
                            <button class="btn" data-ok></button>
                        </div>
                    </div>`;
                document.body.appendChild(m);
            }
            m.querySelector('h3').textContent = title;
            m.querySelector('p').textContent = message;
            const ok = m.querySelector('[data-ok]');
            ok.textContent = okText;
            ok.className = 'btn' + (danger ? ' danger' : '');

            let result = false;
            ok.onclick = () => { result = true; closeModal(m); };
            m.addEventListener('modal:close', () => resolve(result), { once: true });
            openModal('confirm-modal');
        });
    }

    function pagination(container, current, last, onPage) {
        container.innerHTML = '';
        if (!last || last <= 1) return;

        const pages = new Set([1, last, current - 1, current, current + 1]);
        const list = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);

        const btn = (label, page, { active = false, disabled = false, aria } = {}) => {
            const b = document.createElement('button');
            b.innerHTML = label;
            b.disabled = disabled;
            if (active) b.classList.add('active');
            if (aria) b.setAttribute('aria-label', aria);
            b.addEventListener('click', () => onPage(page));
            container.appendChild(b);
        };

        btn(icon('chevron-left'), current - 1, { disabled: current <= 1, aria: 'Oldingi' });
        list.forEach((p, i) => {
            if (i && p - list[i - 1] > 1) container.insertAdjacentHTML('beforeend', '<span class="gap">…</span>');
            btn(String(p), p, { active: p === current });
        });
        btn(icon('chevron-right'), current + 1, { disabled: current >= last, aria: 'Keyingi' });
    }

    function debounce(fn, ms = 350) {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    }

    function setLoading(button, loading) {
        if (loading) {
            button.dataset.label = button.innerHTML;
            button.innerHTML = '<span class="spinner"></span>';
            button.disabled = true;
        } else {
            button.innerHTML = button.dataset.label || button.innerHTML;
            button.disabled = false;
        }
    }

    function showFieldErrors(form, errors) {
        form.querySelectorAll('.field-error').forEach((el) => el.remove());
        Object.entries(errors || {}).forEach(([name, messages]) => {
            const input = form.querySelector(`[name="${name}"]`);
            const field = input && input.closest('.field');
            if (field) field.insertAdjacentHTML('beforeend', `<div class="field-error">${esc(messages[0])}</div>`);
        });
    }

    const can = (user, permission) => !permission || user.is_admin || (user.permissions || []).includes(permission);

    // ---------- Panel sahifalarini ishga tushirish ----------
    function boot() {
        const body = document.body;
        if (!body.classList.contains('panel')) return Promise.resolve(null);

        if (!storage.get()) {
            toLogin();
            return new Promise(() => {});
        }

        // Mobil menyu
        const sidebar = document.getElementById('sidebar');
        document.querySelector('.menu-toggle')?.addEventListener('click', () => sidebar.classList.add('open'));
        document.querySelector('.sidebar-backdrop')?.addEventListener('click', () => sidebar.classList.remove('open'));

        // Foydalanuvchi menyusi
        const userBtn = document.getElementById('user-menu-btn');
        const dropdown = document.getElementById('user-dropdown');
        userBtn?.addEventListener('click', (e) => { e.stopPropagation(); dropdown.classList.toggle('open'); });
        document.addEventListener('click', () => dropdown?.classList.remove('open'));

        document.querySelectorAll('[data-logout]').forEach((el) => el.addEventListener('click', async () => {
            try { await api('/logout', { method: 'POST' }); } catch (e) { /* baribir chiqamiz */ }
            storage.clear();
            location.href = '/login';
        }));

        return api('/me').then((user) => {
            document.querySelectorAll('[data-user-name]').forEach((el) => { el.textContent = user.name; });
            document.querySelectorAll('[data-user-role]').forEach((el) => { el.textContent = user.is_admin ? 'Administrator' : 'Xodim'; });
            document.querySelectorAll('[data-user-initials]').forEach((el) => { el.textContent = fmt.initials(user.name); });
            document.querySelectorAll('[data-permission]').forEach((el) => { el.hidden = !can(user, el.dataset.permission); });

            const required = body.dataset.pagePermission;
            if (!can(user, required)) {
                document.querySelector('.content').innerHTML = `
                    <div class="card"><div class="state">
                        ${icon('lock')}
                        <div class="title">Bu bo‘limga ruxsatingiz yo‘q</div>
                        <div>Ruxsat kerak bo‘lsa, administratorga murojaat qiling.</div>
                    </div></div>`;
                body.classList.remove('booting');
                return new Promise(() => {}); // sahifa skripti ishlamaydi
            }

            body.classList.remove('booting');
            return user;
        }).catch((e) => {
            if (e.status !== 401) {
                body.classList.remove('booting');
                document.querySelector('.content').innerHTML = `<div class="card"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></div>`;
            }
            return new Promise(() => {});
        });
    }

    window.Panel = {
        api, download, ApiError, esc, fmt, icon, toast, openModal, closeModal,
        confirm: confirmDialog, pagination, debounce, setLoading, showFieldErrors, can,
        token: storage,
        ready: null,
    };

    window.Panel.ready = new Promise((resolve) => {
        const start = () => boot().then(resolve);
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
        else start();
    });
})();
