@extends('panel.layout')

@section('title', __('Xodimlar (Face ID)'))
@section('permission', 'employees.manage')

@section('content')
    <div class="page-head">
        <div>
            <h2>{{ __('Xodimlar (Face ID)') }}</h2>
            <p>{{ __('Face ID qurilmasidagi xodimlar. Davomat xodimga ID orqali bog‘lanadi.') }}</p>
        </div>
        <button class="btn" id="add-employee"><svg class="icon"><use href="#i-plus"></use></svg> {{ __('Yangi xodim') }}</button>
    </div>

    <div class="card">
        <div class="toolbar">
            <label class="field grow">
                <span>{{ __('Qidirish') }}</span>
                <div class="input-icon">
                    <svg class="icon"><use href="#i-search"></use></svg>
                    <input class="input" id="f-search" type="search" placeholder="{{ __('Ism, familiya, ID yoki telefon') }}">
                </div>
            </label>
            <label class="field">
                <span>{{ __('Bo‘lim') }}</span>
                <select class="select" id="f-dept">
                    <option value="">{{ __('Barcha bo‘limlar') }}</option>
                </select>
            </label>
            <label class="field">
                <span>{{ __('Holat') }}</span>
                <select class="select" id="f-status">
                    <option value="">{{ __('Hammasi') }}</option>
                    <option value="active">{{ __('Faol') }}</option>
                    <option value="inactive">{{ __('Nofaol') }}</option>
                    <option value="telegram">{{ __('Telegram bog‘langan') }}</option>
                </select>
            </label>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>{{ __('Xodim') }}</th>
                    <th>{{ __('Bo‘lim') }}</th>
                    <th>{{ __('Telefon') }}</th>
                    <th>Telegram</th>
                    <th>{{ __('Faol') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>

        <div class="table-footer">
            <span id="summary"></span>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal" id="employee-modal">
        <form class="modal-dialog" id="employee-form" role="dialog" aria-modal="true" aria-labelledby="employee-modal-title" novalidate>
            <div class="modal-head">
                <h3 id="employee-modal-title"></h3>
                <button type="button" class="btn ghost icon-only" data-close aria-label="{{ __('Yopish') }}"><svg class="icon"><use href="#i-x"></use></svg></button>
            </div>
            <div class="modal-body">
                <div class="alert" id="form-error" hidden></div>
                <div class="form-grid">
                    <label class="field full">
                        <span>{{ __('Face ID dagi ID') }} <span class="hint">— {{ __('qurilmadagi “Идентификатор” bilan bir xil') }}</span></span>
                        <input class="input" name="person_id" required maxlength="32" placeholder="0000000001" autocomplete="off">
                    </label>
                    <label class="field"><span>{{ __('Ism') }}</span><input class="input" name="first_name" required maxlength="255"></label>
                    <label class="field"><span>{{ __('Familiya') }}</span><input class="input" name="last_name" maxlength="255"></label>
                    <label class="field">
                        <span>{{ __('Bo‘lim') }}</span>
                        <input class="input" name="department" list="dept-options" maxlength="255" autocomplete="off">
                        <datalist id="dept-options"></datalist>
                    </label>
                    <label class="field"><span>{{ __('Telefon') }}</span><input class="input" name="phone" maxlength="32" placeholder="+998 90 123 45 67"></label>

                    <div class="field full">
                        <label class="check">
                            <input type="checkbox" name="is_active">
                            <div><div class="cell-main">{{ __('Faol') }}</div><div class="desc">{{ __('Nofaol xodim kunlik holat va kelmaganlar ro‘yxatiga kirmaydi') }}</div></div>
                        </label>
                    </div>
                    <div class="field full" id="unlink-wrap" hidden>
                        <label class="check">
                            <input type="checkbox" name="unlink_telegram">
                            <div><div class="cell-main">{{ __('Telegram bog‘lanishini uzish') }}</div><div class="desc" id="unlink-desc"></div></div>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn secondary" data-close>{{ __('Bekor qilish') }}</button>
                <button type="submit" class="btn">{{ __('Saqlash') }}</button>
            </div>
        </form>
    </div>
@endpush

@push('scripts')
<script>
Panel.ready.then(() => {
    const { api, esc, icon, openModal, closeModal, toast, confirm, setLoading, showFieldErrors, debounce, t } = Panel;
    const rows = document.getElementById('rows');
    const form = document.getElementById('employee-form');
    const formError = document.getElementById('form-error');
    const fSearch = document.getElementById('f-search');
    const fDept = document.getElementById('f-dept');
    const fStatus = document.getElementById('f-status');
    let employees = [];
    let editing = null;

    const fullName = (e) => [e.first_name, e.last_name].filter(Boolean).join(' ');

    function fillDepartments(departments) {
        const current = fDept.value;
        fDept.innerHTML = `<option value="">${t('Barcha bo‘limlar')}</option>` +
            departments.map((d) => `<option value="${esc(d)}">${esc(d)}</option>`).join('');
        fDept.value = departments.includes(current) ? current : '';
        document.getElementById('dept-options').innerHTML = departments.map((d) => `<option value="${esc(d)}">`).join('');
    }

    async function load() {
        rows.innerHTML = `<tr><td colspan="7"><div class="skeleton"></div></td></tr>`.repeat(3);
        try {
            const res = await api('/attendance-employees');
            employees = res.employees;
            fillDepartments(res.departments);
            render();
        } catch (e) {
            rows.innerHTML = `<tr><td colspan="7"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></td></tr>`;
        }
    }

    function filtered() {
        const q = fSearch.value.trim().toLowerCase();
        const dept = fDept.value;
        const status = fStatus.value;
        return employees.filter((e) => {
            if (dept && e.department !== dept) return false;
            if (status === 'active' && !e.is_active) return false;
            if (status === 'inactive' && e.is_active) return false;
            if (status === 'telegram' && !e.chat_id) return false;
            if (!q) return true;
            return [e.person_id, e.first_name, e.last_name, e.phone, fullName(e)]
                .some((v) => String(v || '').toLowerCase().includes(q));
        });
    }

    function render() {
        const list = filtered();
        rows.innerHTML = list.map((e) => `
            <tr class="${e.is_active ? '' : 'muted'}">
                <td class="nowrap"><code>${esc(e.person_id)}</code></td>
                <td><div class="cell-main">${esc(fullName(e)) || '—'}</div></td>
                <td class="nowrap">${e.department ? `<span class="badge">${esc(e.department)}</span>` : '—'}</td>
                <td class="nowrap">${esc(e.phone || '—')}</td>
                <td class="nowrap">${e.chat_id ? `<span class="badge green">${icon('check')} ${t('Bog‘langan')}</span>` : '<span class="muted">—</span>'}</td>
                <td>
                    <label class="switch" title="${t('Faol')}">
                        <input type="checkbox" data-toggle="${e.id}" ${e.is_active ? 'checked' : ''}><span class="track"></span>
                    </label>
                </td>
                <td class="actions">
                    <button class="btn ghost icon-only" data-edit="${e.id}" aria-label="${t('Tahrirlash')}" title="${t('Tahrirlash')}">${icon('edit')}</button>
                    <button class="btn ghost icon-only" data-delete="${e.id}" aria-label="${t('O‘chirish')}" title="${t('O‘chirish')}">${icon('trash')}</button>
                </td>
            </tr>`).join('') || `<tr><td colspan="7"><div class="state">${icon('users')}<div class="title">${employees.length ? t('Hech narsa topilmadi') : t('Xodimlar yo‘q')}</div></div></td></tr>`;

        const active = employees.filter((e) => e.is_active).length;
        const linked = employees.filter((e) => e.chat_id).length;
        document.getElementById('summary').textContent =
            t('Ko‘rsatilmoqda: :shown / :total · faol: :active · Telegram: :linked', { shown: list.length, total: employees.length, active, linked });
    }

    const byId = (id) => employees.find((e) => e.id === +id);

    function bodyOf(e, overrides = {}) {
        return {
            person_id: e.person_id, first_name: e.first_name, last_name: e.last_name,
            department: e.department, phone: e.phone, is_active: e.is_active, ...overrides,
        };
    }

    function openForm(employee) {
        editing = employee || null;
        form.reset();
        showFieldErrors(form, {});
        formError.hidden = true;
        document.getElementById('employee-modal-title').textContent = employee ? t('Xodimni tahrirlash') : t('Yangi xodim');

        const el = form.elements;
        if (employee) {
            el.person_id.value = employee.person_id;
            el.first_name.value = employee.first_name || '';
            el.last_name.value = employee.last_name || '';
            el.department.value = employee.department || '';
            el.phone.value = employee.phone || '';
            el.is_active.checked = employee.is_active;
        } else {
            el.is_active.checked = true;
        }
        const linked = !!(employee && employee.chat_id);
        document.getElementById('unlink-wrap').hidden = !linked;
        document.getElementById('unlink-desc').textContent = linked
            ? t('chat_id: :id — ID boshqa odamga o‘tgan bo‘lsa, belgilang', { id: employee.chat_id }) : '';
        openModal('employee-modal');
        setTimeout(() => (employee ? el.first_name : el.person_id).focus(), 50);
    }

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        formError.hidden = true;
        const el = form.elements;
        const body = {
            person_id: el.person_id.value.trim(),
            first_name: el.first_name.value.trim(),
            last_name: el.last_name.value.trim() || null,
            department: el.department.value.trim() || null,
            phone: el.phone.value.trim() || null,
            is_active: el.is_active.checked,
        };
        if (editing && editing.chat_id && el.unlink_telegram.checked) body.unlink_telegram = true;

        if (editing && editing.person_id !== body.person_id) {
            const ok = await confirm(
                t('ID ":old" → ":new" ga o‘zgaradi. Eski ID bo‘yicha davomat yozuvlari bu xodimga bog‘lanmay qoladi.', { old: editing.person_id, new: body.person_id }),
                { title: t('ID ni o‘zgartirish'), okText: t('O‘zgartirish') });
            if (!ok) return;
        }

        const btn = form.querySelector('button[type=submit]');
        setLoading(btn, true);
        try {
            await api(editing ? `/attendance-employees/${editing.id}` : '/attendance-employees', { method: editing ? 'PUT' : 'POST', body });
            closeModal('employee-modal');
            toast(editing ? t('O‘zgarishlar saqlandi') : t('Xodim qo‘shildi'), 'success');
            load();
        } catch (err) {
            showFieldErrors(form, err.errors);
            if (!Object.keys(err.errors).length) {
                formError.textContent = err.message;
                formError.hidden = false;
            }
        } finally {
            setLoading(btn, false);
        }
    });

    rows.addEventListener('change', async (ev) => {
        const cb = ev.target.closest('[data-toggle]');
        if (!cb) return;
        const e = byId(cb.dataset.toggle);
        cb.disabled = true;
        try {
            const saved = await api(`/attendance-employees/${e.id}`, { method: 'PUT', body: bodyOf(e, { is_active: cb.checked }) });
            Object.assign(e, saved);
            render();
            toast(saved.is_active ? t(':name faollashtirildi', { name: fullName(e) }) : t(':name nofaol qilindi', { name: fullName(e) }), 'success');
        } catch (err) {
            cb.checked = !cb.checked;
            cb.disabled = false;
            toast(err.message, 'error');
        }
    });

    rows.addEventListener('click', async (ev) => {
        const edit = ev.target.closest('[data-edit]');
        if (edit) return openForm(byId(edit.dataset.edit));

        const del = ev.target.closest('[data-delete]');
        if (!del) return;
        const e = byId(del.dataset.delete);
        const ok = await confirm(
            t('":name" (ID :id) ro‘yxatdan o‘chiriladi. Vaqtincha bo‘lsa, o‘chirish o‘rniga nofaol qiling.', { name: fullName(e), id: e.person_id }),
            { title: t('Xodimni o‘chirish'), okText: t('O‘chirish'), danger: true });
        if (!ok) return;
        try {
            await api(`/attendance-employees/${e.id}`, { method: 'DELETE' });
            toast(t('Xodim o‘chirildi'), 'success');
            load();
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    fSearch.addEventListener('input', debounce(render, 200));
    fDept.addEventListener('change', render);
    fStatus.addEventListener('change', render);
    document.getElementById('add-employee').addEventListener('click', () => openForm(null));
    load();
});
</script>
@endpush
