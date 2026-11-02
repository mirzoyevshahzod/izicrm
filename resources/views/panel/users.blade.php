@extends('panel.layout')

@section('title', 'Foydalanuvchilar')
@section('permission', 'users.manage')

@section('content')
    <div class="page-head">
        <div>
            <h2>Foydalanuvchilar</h2>
            <p>Panelga kim kira olishi va qaysi bo‘limlarni ko‘rishini shu yerda boshqarasiz.</p>
        </div>
        <button class="btn" id="add-user"><svg class="icon"><use href="#i-plus"></use></svg> Yangi foydalanuvchi</button>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Foydalanuvchi</th>
                    <th>Telefon</th>
                    <th>Kirish huquqi</th>
                    <th>Qo‘shilgan</th>
                    <th></th>
                </tr>
                </thead>
                <tbody id="rows"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('modals')
    <div class="modal" id="user-modal">
        <form class="modal-dialog" id="user-form" role="dialog" aria-modal="true" aria-labelledby="user-modal-title" novalidate>
            <div class="modal-head">
                <h3 id="user-modal-title"></h3>
                <button type="button" class="btn ghost icon-only" data-close aria-label="Yopish"><svg class="icon"><use href="#i-x"></use></svg></button>
            </div>
            <div class="modal-body">
                <div class="alert" id="form-error" hidden></div>
                <div class="form-grid">
                    <label class="field full"><span>Ism familiya</span><input class="input" name="name" required></label>
                    <label class="field"><span>Email (login)</span><input class="input" name="email" type="email" required autocomplete="off"></label>
                    <label class="field"><span>Telefon</span><input class="input" name="phone_number" placeholder="+998 90 123 45 67"></label>
                    <label class="field full">
                        <span>Parol <span class="hint" id="password-hint"></span></span>
                        <input class="input" name="password" type="text" autocomplete="new-password" minlength="8">
                    </label>

                    <div class="field full">
                        <span>Kirish huquqi</span>
                        <label class="check">
                            <input type="checkbox" name="is_admin">
                            <div><div class="cell-main">Administrator</div><div class="desc">Barcha bo‘limlar va foydalanuvchilarni boshqarish</div></div>
                        </label>
                        <div class="grid" id="perm-list" style="gap:8px"></div>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn secondary" data-close>Bekor qilish</button>
                <button type="submit" class="btn">Saqlash</button>
            </div>
        </form>
    </div>
@endpush

@push('scripts')
<script>
Panel.ready.then((me) => {
    const { api, esc, fmt, icon, openModal, closeModal, toast, confirm, setLoading, showFieldErrors } = Panel;
    const rows = document.getElementById('rows');
    const form = document.getElementById('user-form');
    const formError = document.getElementById('form-error');
    let users = [];
    let permissions = {};
    let editing = null;

    function accessBadges(u) {
        if (u.is_admin) return `<span class="badge primary">${icon('shield')} Administrator</span>`;
        if (!u.permissions.length) return '<span class="badge red">Ruxsat yo‘q</span>';
        return u.permissions.map((p) => `<span class="badge">${esc(permissions[p] || p)}</span>`).join('');
    }

    async function load() {
        rows.innerHTML = `<tr><td colspan="5"><div class="skeleton"></div></td></tr>`.repeat(3);
        try {
            const res = await api('/users');
            users = res.users;
            permissions = res.permissions;
            renderPermissionChecks();
            rows.innerHTML = users.map((u, i) => `
                <tr>
                    <td>
                        <div class="user-cell">
                            <span class="avatar">${esc(fmt.initials(u.name))}</span>
                            <div>
                                <div class="cell-main">${esc(u.name)}${u.id === me.id ? ' <span class="badge">Siz</span>' : ''}</div>
                                <div class="cell-sub">${esc(u.email)}</div>
                            </div>
                        </div>
                    </td>
                    <td class="nowrap">${esc(u.phone_number || '—')}</td>
                    <td><div class="badges">${accessBadges(u)}</div></td>
                    <td class="nowrap muted">${esc(fmt.date(u.created_at))}</td>
                    <td class="actions">
                        <button class="btn ghost icon-only" data-edit="${i}" aria-label="Tahrirlash" title="Tahrirlash">${icon('edit')}</button>
                        ${u.id !== me.id ? `<button class="btn ghost icon-only" data-delete="${i}" aria-label="O‘chirish" title="O‘chirish">${icon('trash')}</button>` : ''}
                    </td>
                </tr>`).join('') || `<tr><td colspan="5"><div class="state">${icon('users')}<div class="title">Foydalanuvchilar yo‘q</div></div></td></tr>`;
        } catch (e) {
            rows.innerHTML = `<tr><td colspan="5"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></td></tr>`;
        }
    }

    function renderPermissionChecks() {
        document.getElementById('perm-list').innerHTML = Object.entries(permissions).map(([key, label]) => `
            <label class="check">
                <input type="checkbox" name="permissions" value="${esc(key)}">
                <div class="cell-main">${esc(label)}</div>
            </label>`).join('');
    }

    function syncAdminState() {
        const admin = form.elements.is_admin.checked;
        form.querySelectorAll('[name=permissions]').forEach((cb) => {
            cb.disabled = admin;
            cb.closest('.check').classList.toggle('disabled', admin);
            if (admin) cb.checked = true;
        });
    }

    function openForm(user) {
        editing = user || null;
        form.reset();
        showFieldErrors(form, {});
        formError.hidden = true;
        document.getElementById('user-modal-title').textContent = user ? 'Foydalanuvchini tahrirlash' : 'Yangi foydalanuvchi';
        document.getElementById('password-hint').textContent = user ? '— o‘zgartirmasangiz bo‘sh qoldiring' : '— kamida 8 belgi';

        if (user) {
            form.elements.name.value = user.name;
            form.elements.email.value = user.email;
            form.elements.phone_number.value = user.phone_number || '';
            form.elements.is_admin.checked = user.is_admin;
            form.querySelectorAll('[name=permissions]').forEach((cb) => { cb.checked = user.permissions.includes(cb.value); });
        }
        // O'zidan admin huquqini olib qo'ymasligi uchun
        form.elements.is_admin.disabled = !!(user && user.id === me.id && user.is_admin);
        syncAdminState();
        openModal('user-modal');
    }

    form.elements.is_admin.addEventListener('change', () => {
        if (!form.elements.is_admin.checked) form.querySelectorAll('[name=permissions]').forEach((cb) => { cb.checked = false; });
        syncAdminState();
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        formError.hidden = true;
        const isAdmin = form.elements.is_admin.checked;
        const body = {
            name: form.elements.name.value.trim(),
            email: form.elements.email.value.trim(),
            phone_number: form.elements.phone_number.value.trim() || null,
            is_admin: isAdmin,
            permissions: isAdmin ? [] : [...form.querySelectorAll('[name=permissions]:checked')].map((cb) => cb.value),
        };
        if (form.elements.password.value) body.password = form.elements.password.value;

        const btn = form.querySelector('button[type=submit]');
        setLoading(btn, true);
        try {
            await api(editing ? `/users/${editing.id}` : '/users', { method: editing ? 'PUT' : 'POST', body });
            closeModal('user-modal');
            toast(editing ? 'O‘zgarishlar saqlandi' : 'Foydalanuvchi qo‘shildi', 'success');
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

    rows.addEventListener('click', async (e) => {
        const edit = e.target.closest('[data-edit]');
        if (edit) return openForm(users[+edit.dataset.edit]);

        const del = e.target.closest('[data-delete]');
        if (!del) return;
        const user = users[+del.dataset.delete];
        const ok = await confirm(`"${user.name}" panelga boshqa kira olmaydi.`, { title: 'Foydalanuvchini o‘chirish', okText: 'O‘chirish', danger: true });
        if (!ok) return;
        try {
            await api(`/users/${user.id}`, { method: 'DELETE' });
            toast('Foydalanuvchi o‘chirildi', 'success');
            load();
        } catch (err) {
            toast(err.message, 'error');
        }
    });

    document.getElementById('add-user').addEventListener('click', () => openForm(null));
    load();
});
</script>
@endpush
