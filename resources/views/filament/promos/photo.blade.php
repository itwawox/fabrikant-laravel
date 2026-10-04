@php($promo = $getRecord())
<div>
    @if ($promo?->photo_path)
        <p style="font-size:.875rem;font-weight:500;margin-bottom:.5rem">Сейчас на сайте</p>
        {{-- На сайте фото чёрно-белое (CSS), здесь — как есть --}}
        <img src="{{ $promo->photoUrl('webp') ?? $promo->photoUrl('jpg') }}" alt="{{ $promo->alt }}" style="width:100%;max-width:360px;border-radius:.5rem;aspect-ratio:4/3;object-fit:cover">
    @else
        <p style="color:var(--gray-500);font-size:.875rem">Фото нет — карточка будет без картинки.</p>
    @endif
</div>
