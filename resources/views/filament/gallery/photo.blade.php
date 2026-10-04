@php
    /** @var \App\Models\GalleryPhoto|null $photo */
    $photo = $getRecord();
    $preview = $photo?->variants['webp'][480] ?? ($photo?->variants['webp'] ? reset($photo->variants['webp']) : null);
@endphp
<div>
    @if ($preview)
        <p style="font-size:.875rem;font-weight:500;margin-bottom:.5rem">Сейчас на сайте ({{ $photo->width }}×{{ $photo->height }})</p>
        <img src="{{ Storage::disk('public')->url($preview) }}" alt="{{ $photo->alt }}" style="width:100%;max-width:360px;border-radius:.5rem">
    @else
        {{-- Пока копии режутся, страница сама обновится --}}
        <p wire:poll.3s="refreshPhoto" style="color:var(--warning-600);font-size:.875rem">Готовим копии для сайта… Фото появится на сайте через полминуты.</p>
    @endif
</div>
