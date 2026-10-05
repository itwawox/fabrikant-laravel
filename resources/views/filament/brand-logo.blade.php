@use('App\Support\Html')
{{-- Логотип в шапке админки — тот же вектор, что на сайте: краской логотипа в светлой теме, золотом в тёмной.
     У SVG нет своих размеров — высоту задаёт brandLogoHeight() в AdminPanelProvider --}}
<span style="display: block; height: 100%; color: {{ $color }};">{!! str_replace('<svg ', '<svg style="display: block; height: 100%; width: auto;" ', Html::svg('logo', 'fi-brand-logo-svg', 'ФабрикантЪ')) !!}</span>
