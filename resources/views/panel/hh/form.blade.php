@extends('panel.layout')

@section('title', 'Qidiruv filtri')
@section('permission', 'hh.view')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('panel.hh', [], false) }}" class="muted" style="display:inline-flex;align-items:center;gap:4px">
                <svg class="icon"><use href="#i-chevron-left"></use></svg> Qidiruvlar
            </a>
            <h2 id="form-title" style="margin-top:6px">Yangi qidiruv</h2>
            <p>Filtrlar hh.uz dagi kengaytirilgan rezyume qidiruvi bilan bir xil. Pastda hh.uz da hozir nechta rezyume mosligi ko‘rinadi.</p>
        </div>
    </div>

    <div id="notice"></div>

    <form id="search-form" novalidate>
        <div class="card">
            <div class="form-section">
                <div class="label">Qidiruv nomi<span class="hint">Faqat siz uchun, masalan “Ombor mudiri”</span></div>
                <div class="controls">
                    <label class="field"><input class="input" name="name" required maxlength="255" placeholder="Masalan: Logist, Toshkent"></label>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Kalit so‘zlar<span class="hint">Lavozim, ko‘nikma yoki kompaniya nomi</span></div>
                <div class="controls">
                    <div id="keywords" class="controls"></div>
                    <div><button type="button" class="btn ghost sm" id="add-keyword"><svg class="icon"><use href="#i-plus"></use></svg> Shart qo‘shish</button></div>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Kasbiy soha</div>
                <div class="controls">
                    <div class="chips" id="role-chips"></div>
                    <div><button type="button" class="btn secondary sm" data-picker="professional_role">Sohani tanlash</button></div>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Hudud<span class="hint">Tanlanmasa — butun O‘zbekiston</span></div>
                <div class="controls">
                    <div class="chips" id="area-chips"></div>
                    <div class="row">
                        <button type="button" class="btn secondary sm" data-picker="area">Hududni tanlash</button>
                        <select class="select" name="relocation" id="relocation" style="max-width:420px"></select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Kutilayotgan maosh</div>
                <div class="controls">
                    <div class="row">
                        <input class="input narrow" name="salary_from" type="number" min="0" placeholder="dan">
                        <input class="input narrow" name="salary_to" type="number" min="0" placeholder="gacha">
                        <select class="select narrow" name="currency" id="currency"></select>
                    </div>
                    <label class="inline-check"><input type="checkbox" name="label" value="only_with_salary"> Maoshi ko‘rsatilmagan rezyumelarni chiqarmaslik</label>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Yosh</div>
                <div class="controls">
                    <div class="row">
                        <input class="input narrow" name="age_from" type="number" min="14" max="100" placeholder="dan">
                        <input class="input narrow" name="age_to" type="number" min="14" max="100" placeholder="gacha">
                    </div>
                    <label class="inline-check"><input type="checkbox" name="label" value="only_with_age"> Yoshi ko‘rsatilmaganlarni chiqarmaslik</label>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Jinsi</div>
                <div class="controls">
                    <div class="checks" id="gender"></div>
                    <label class="inline-check"><input type="checkbox" name="label" value="only_with_gender"> Jinsi ko‘rsatilmaganlarni chiqarmaslik</label>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Ish tajribasi</div>
                <div class="controls"><div class="checks" id="experience"></div></div>
            </div>

            <div class="form-section">
                <div class="label">Ta’lim</div>
                <div class="controls"><div class="checks" id="education_level"></div></div>
            </div>

            <div class="form-section">
                <div class="label">Tillarni bilish</div>
                <div class="controls">
                    <div id="languages" class="controls"></div>
                    <div><button type="button" class="btn ghost sm" id="add-language"><svg class="icon"><use href="#i-plus"></use></svg> Til qo‘shish</button></div>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Asosiy ko‘nikmalar</div>
                <div class="controls">
                    <div class="chips" id="skill-chips"></div>
                    <div class="suggest">
                        <input class="input" id="skill-input" placeholder="Masalan: 1C, Excel, logistika" autocomplete="off">
                        <div class="suggest-list" id="skill-list" hidden></div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Bandlik turi</div>
                <div class="controls"><div class="checks" id="employment_form"></div></div>
            </div>

            <div class="form-section">
                <div class="label">Ish formati</div>
                <div class="controls"><div class="checks" id="work_format"></div></div>
            </div>

            <div class="form-section">
                <div class="label">Haydovchilik guvohnomasi</div>
                <div class="controls">
                    <div class="checks" id="driver_license_types"></div>
                    <label class="inline-check"><input type="checkbox" name="label" value="only_with_vehicle"> Shaxsiy avtomobili bor</label>
                </div>
            </div>

            <div class="form-section">
                <div class="label">Ish qidirish holati</div>
                <div class="controls"><div class="checks" id="job_search_status"></div></div>
            </div>

            <div class="form-section">
                <div class="label">Qo‘shimcha</div>
                <div class="controls">
                    <label class="inline-check"><input type="checkbox" name="label" value="only_with_photo"> Faqat rasmli rezyumelar</label>
                    <label class="inline-check"><input type="checkbox" name="label" value="exclude_viewed_by_employer_id"> Kompaniyamiz ko‘rgan rezyumelarni yashirish</label>
                </div>
            </div>
        </div>

        <div class="sticky-bar">
            <div class="found" id="found">hh.uz da: <strong>—</strong></div>
            <label class="switch" title="Har soatda avtomatik tekshirish">
                <input type="checkbox" name="is_active" checked><span class="track"></span> <span>Har soatda tekshirish</span>
            </label>
            <a class="btn secondary" href="{{ route('panel.hh', [], false) }}">Bekor qilish</a>
            <button class="btn" type="submit" id="save">Saqlash</button>
        </div>
    </form>
@endsection

@push('modals')
    <div class="modal" id="picker-modal">
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="picker-title">
            <div class="modal-head">
                <h3 id="picker-title"></h3>
                <button type="button" class="btn ghost icon-only" data-close aria-label="Yopish"><svg class="icon"><use href="#i-x"></use></svg></button>
            </div>
            <div class="modal-body">
                <div class="input-icon">
                    <svg class="icon"><use href="#i-search"></use></svg>
                    <input class="input" id="picker-search" type="search" placeholder="Qidirish">
                </div>
                <div class="picker-list" id="picker-list"></div>
            </div>
            <div class="modal-foot">
                <span class="muted" id="picker-count" style="margin-right:auto"></span>
                <button type="button" class="btn secondary" id="picker-clear">Tozalash</button>
                <button type="button" class="btn" id="picker-done">Tayyor</button>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
Panel.ready.then(async () => {
    const { api, esc, fmt, icon, toast, openModal, closeModal, debounce, setLoading, showFieldErrors } = Panel;
    const form = document.getElementById('search-form');
    const editId = (location.pathname.match(/\/hh\/searches\/(\d+)\/edit/) || [])[1];
    const state = { area: [], professional_role: [], skill: [] };
    let dict;

    try {
        dict = await api('/hh/dictionaries');
    } catch (e) {
        form.innerHTML = `<div class="card"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></div>`;
        return;
    }
    if (!dict.connected) {
        document.getElementById('notice').innerHTML = `<div class="notice">${icon('alert')}<div><strong>hh.uz ulanmagan.</strong> Filtrni saqlash mumkin, lekin natijalar hh.uz ulangandan keyin keladi.</div></div>`;
    }

    // ---------- Formani qurish ----------
    const options = (items, selected) => items.map((i) => `<option value="${esc(i.id)}" ${i.id === selected ? 'selected' : ''}>${esc(i.name)}</option>`).join('');

    function pills(id, items, type = 'checkbox', name = id) {
        document.getElementById(id).innerHTML = items.map((i) => `
            <label class="pill-check"><input type="${type}" name="${name}" value="${esc(i.id)}"><span>${esc(i.name)}</span></label>`).join('');
    }

    pills('experience', dict.experience);
    pills('education_level', dict.education_level);
    pills('employment_form', dict.employment_form);
    pills('work_format', dict.work_format);
    pills('driver_license_types', dict.driver_license_types.map((d) => ({ id: d.id, name: d.id })));
    pills('job_search_status', dict.job_search_status);
    pills('gender', [{ id: '', name: 'Ahamiyatsiz' }, ...dict.gender], 'radio');
    form.querySelector('[name=gender][value=""]').checked = true;
    document.getElementById('currency').innerHTML = options(dict.currency, 'UZS');
    document.getElementById('relocation').innerHTML = options(dict.relocation, 'living_or_relocation');

    // Kalit so'zlar qatorlari
    const keywordsBox = document.getElementById('keywords');
    function addKeyword(k = {}) {
        const row = document.createElement('div');
        row.className = 'kw-row';
        row.innerHTML = `
            <input class="input" data-k="text" placeholder="Masalan: логист OR logist" value="${esc(k.text || '')}">
            <select class="select" data-k="field">${options(dict.field, k.field || 'everywhere')}</select>
            <select class="select" data-k="logic">${options(dict.logic, k.logic || 'all')}</select>
            <select class="select" data-k="period">${options(dict.period, k.period || 'all_time')}</select>
            <button type="button" class="btn ghost icon-only" data-remove aria-label="O‘chirish">${icon('x')}</button>`;
        keywordsBox.appendChild(row);
    }
    document.getElementById('add-keyword').addEventListener('click', () => { addKeyword(); keywordsBox.lastChild.querySelector('input').focus(); });

    // Tillar qatorlari
    const langBox = document.getElementById('languages');
    function addLanguage(l = {}) {
        const row = document.createElement('div');
        row.className = 'lang-row';
        row.innerHTML = `
            <select class="select" data-l="id">${options(dict.languages, l.id || 'eng')}</select>
            <select class="select" data-l="level">${options(dict.language_level, l.level || 'b1')}</select>
            <button type="button" class="btn ghost icon-only" data-remove aria-label="O‘chirish">${icon('x')}</button>`;
        langBox.appendChild(row);
    }
    document.getElementById('add-language').addEventListener('click', () => { addLanguage(); refreshPreview(); });

    form.addEventListener('click', (e) => {
        const rm = e.target.closest('[data-remove]');
        if (rm) { rm.parentElement.remove(); refreshPreview(); }
    });

    // ---------- Tanlangan elementlar (chip) ----------
    const roleName = (id) => dict.roles.flatMap((c) => c.roles).find((r) => r.id === id)?.name || id;
    const areaName = (id) => dict.areas.find((a) => a.id === id)?.name || id;

    function renderChips() {
        const chip = (key, id, text) => `<span class="chip">${esc(text)}<button type="button" data-unchip="${key}" data-id="${esc(id)}" aria-label="Olib tashlash">${icon('x')}</button></span>`;
        document.getElementById('role-chips').innerHTML = state.professional_role.map((id) => chip('professional_role', id, roleName(id))).join('') || '<span class="muted">Barcha sohalar</span>';
        document.getElementById('area-chips').innerHTML = state.area.map((id) => chip('area', id, areaName(id))).join('') || '<span class="muted">Butun O‘zbekiston</span>';
        document.getElementById('skill-chips').innerHTML = state.skill.map((s) => chip('skill', s.id, s.text)).join('');
        document.getElementById('relocation').hidden = !state.area.length;
    }
    form.addEventListener('click', (e) => {
        const b = e.target.closest('[data-unchip]');
        if (!b) return;
        const key = b.dataset.unchip;
        state[key] = key === 'skill' ? state.skill.filter((s) => s.id !== b.dataset.id) : state[key].filter((id) => id !== b.dataset.id);
        renderChips();
        refreshPreview();
    });

    // ---------- Tanlash oynasi (soha / hudud) ----------
    let picker = null;
    const pickerList = document.getElementById('picker-list');
    const pickerSearch = document.getElementById('picker-search');

    function renderPicker() {
        const q = pickerSearch.value.trim().toLowerCase();
        const match = (i) => !q || i.name.toLowerCase().includes(q);
        pickerList.innerHTML = picker.groups.map((g) => {
            const items = g.items.filter(match);
            if (!items.length) return '';
            return `<div class="picker-group">${g.name ? `<div class="picker-title">${esc(g.name)}</div>` : ''}
                ${items.map((i) => `<label class="picker-item"><input type="checkbox" value="${esc(i.id)}" ${picker.selected.has(i.id) ? 'checked' : ''}> ${esc(i.name)}</label>`).join('')}</div>`;
        }).join('') || '<div class="state">Hech narsa topilmadi</div>';
        document.getElementById('picker-count').textContent = picker.selected.size ? `Tanlandi: ${picker.selected.size}` : '';
    }

    form.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-picker]');
        if (!btn) return;
        const key = btn.dataset.picker;
        picker = {
            key,
            selected: new Set(state[key]),
            groups: key === 'area'
                ? [{ name: '', items: dict.areas }]
                : dict.roles.map((c) => ({ name: c.name, items: c.roles })),
        };
        document.getElementById('picker-title').textContent = key === 'area' ? 'Hududni tanlang' : 'Kasbiy sohani tanlang';
        pickerSearch.value = '';
        renderPicker();
        openModal('picker-modal');
    });
    pickerSearch.addEventListener('input', renderPicker);
    pickerList.addEventListener('change', (e) => {
        const cb = e.target;
        cb.checked ? picker.selected.add(cb.value) : picker.selected.delete(cb.value);
        document.getElementById('picker-count').textContent = picker.selected.size ? `Tanlandi: ${picker.selected.size}` : '';
    });
    document.getElementById('picker-clear').addEventListener('click', () => { picker.selected.clear(); renderPicker(); });
    document.getElementById('picker-done').addEventListener('click', () => {
        state[picker.key] = [...picker.selected];
        renderChips();
        closeModal('picker-modal');
        refreshPreview();
    });

    // ---------- Ko'nikmalar (hh taklif qiladigan ro'yxatdan) ----------
    const skillInput = document.getElementById('skill-input');
    const skillList = document.getElementById('skill-list');
    const suggestSkills = debounce(async () => {
        const text = skillInput.value.trim();
        if (text.length < 2) { skillList.hidden = true; return; }
        try {
            const items = (await api('/hh/skills', { query: { text } })).filter((s) => !state.skill.some((x) => x.id === s.id));
            skillList.innerHTML = items.map((s) => `<button type="button" data-skill="${esc(s.id)}" data-text="${esc(s.text)}">${esc(s.text)}</button>`).join('');
            skillList.hidden = !items.length;
        } catch (e) { skillList.hidden = true; }
    }, 300);
    skillInput.addEventListener('input', suggestSkills);
    skillInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); skillList.querySelector('button')?.click(); } });
    skillList.addEventListener('click', (e) => {
        const b = e.target.closest('[data-skill]');
        if (!b) return;
        state.skill.push({ id: b.dataset.skill, text: b.dataset.text });
        skillInput.value = '';
        skillList.hidden = true;
        renderChips();
        refreshPreview();
    });
    document.addEventListener('click', (e) => { if (!e.target.closest('.suggest')) skillList.hidden = true; });

    // ---------- Qiymatlarni yig'ish ----------
    const checked = (name) => [...form.querySelectorAll(`[name="${name}"]:checked`)].map((i) => i.value);
    const num = (name) => form.elements[name].value === '' ? null : Number(form.elements[name].value);

    function collect() {
        return {
            keywords: [...keywordsBox.children].map((r) => ({
                text: r.querySelector('[data-k=text]').value.trim(),
                field: r.querySelector('[data-k=field]').value,
                logic: r.querySelector('[data-k=logic]').value,
                period: r.querySelector('[data-k=period]').value,
            })).filter((k) => k.text),
            professional_role: state.professional_role,
            area: state.area,
            relocation: state.area.length ? form.elements.relocation.value : null,
            salary_from: num('salary_from'),
            salary_to: num('salary_to'),
            currency: form.elements.currency.value,
            age_from: num('age_from'),
            age_to: num('age_to'),
            gender: checked('gender')[0] || null,
            experience: checked('experience'),
            education_level: checked('education_level'),
            language: [...langBox.children].map((r) => ({ id: r.querySelector('[data-l=id]').value, level: r.querySelector('[data-l=level]').value })),
            skill: state.skill,
            employment_form: checked('employment_form'),
            work_format: checked('work_format'),
            driver_license_types: checked('driver_license_types'),
            job_search_status: checked('job_search_status'),
            label: checked('label'),
        };
    }

    // ---------- Jonli hisob: hh.uz da nechta rezyume ----------
    const foundBox = document.getElementById('found');
    let previewSeq = 0;
    const refreshPreview = debounce(async () => {
        if (!dict.connected) { foundBox.innerHTML = 'hh.uz ulanmagan'; return; }
        const seq = ++previewSeq;
        foundBox.innerHTML = 'hh.uz da: <span class="spinner" style="width:14px;height:14px;vertical-align:middle"></span>';
        try {
            const r = await api('/hh/preview', { method: 'POST', body: { params: collect() } });
            if (seq === previewSeq) foundBox.innerHTML = `hh.uz da: <strong>${fmt.number(r.found)}</strong> ta rezyume`;
        } catch (e) {
            if (seq === previewSeq) foundBox.innerHTML = `<span style="color:var(--danger)">${esc(e.message)}</span>`;
        }
    }, 600);
    form.addEventListener('input', (e) => { if (e.target.id !== 'skill-input' && e.target.name !== 'name') refreshPreview(); });
    form.addEventListener('change', (e) => { if (e.target.name !== 'name' && e.target.name !== 'is_active') refreshPreview(); });

    // ---------- Tahrirlash rejimi ----------
    if (editId) {
        document.getElementById('form-title').textContent = 'Qidiruvni tahrirlash';
        try {
            const s = await api(`/hh/searches/${editId}`);
            const p = s.params;
            form.elements.name.value = s.name;
            form.elements.is_active.checked = s.is_active;
            (p.keywords || []).forEach(addKeyword);
            (p.language || []).forEach(addLanguage);
            state.area = p.area || [];
            state.professional_role = p.professional_role || [];
            state.skill = p.skill || [];
            ['salary_from', 'salary_to', 'age_from', 'age_to'].forEach((k) => { form.elements[k].value = p[k] ?? ''; });
            form.elements.currency.value = p.currency || 'UZS';
            if (p.relocation) form.elements.relocation.value = p.relocation;
            form.querySelector(`[name=gender][value="${p.gender || ''}"]`).checked = true;
            ['experience', 'education_level', 'employment_form', 'work_format', 'driver_license_types', 'job_search_status', 'label'].forEach((name) => {
                (p[name] || []).forEach((v) => { const el = form.querySelector(`[name="${name}"][value="${v}"]`); if (el) el.checked = true; });
            });
        } catch (e) {
            toast(e.message, 'error');
        }
    }
    if (!keywordsBox.children.length) addKeyword();
    renderChips();
    refreshPreview();

    // ---------- Saqlash ----------
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        showFieldErrors(form, {});
        if (!form.elements.name.value.trim()) {
            showFieldErrors(form, { name: ['Qidiruvga nom bering.'] });
            form.elements.name.focus();
            return;
        }

        const btn = document.getElementById('save');
        setLoading(btn, true);
        try {
            const body = { name: form.elements.name.value.trim(), is_active: form.elements.is_active.checked, params: collect() };
            const s = await api(editId ? `/hh/searches/${editId}` : '/hh/searches', { method: editId ? 'PUT' : 'POST', body });

            // Yangi yoki o'zgargan filtr — darhol birinchi natijalarni olamiz
            if (dict.connected && !s.last_run_at) {
                foundBox.innerHTML = '<span class="spinner" style="width:14px;height:14px;vertical-align:middle"></span> Nomzodlar hh.uz dan olinmoqda…';
                try { await api(`/hh/searches/${s.id}/run`, { method: 'POST' }); } catch (err) { toast(err.message, 'error'); }
            }
            location.href = `/panel/hh/searches/${s.id}`;
        } catch (err) {
            showFieldErrors(form, err.errors);
            toast(err.message, 'error');
            setLoading(btn, false);
        }
    });
});
</script>
@endpush
