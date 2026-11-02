{{-- JS uchun tarjimalar: matnlar o'zbekcha, ruscha tarjima resources/lang/ru.json da (Panel.t) --}}
@php($panelT = app()->getLocale() === 'ru' ? (json_decode(@file_get_contents(lang_path('ru.json')) ?: '{}', true) ?: []) : [])
<script>window.PANEL_LANG = @json(app()->getLocale()); window.PANEL_T = @json((object) $panelT);</script>
