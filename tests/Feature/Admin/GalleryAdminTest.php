<?php

use App\Enums\PhotoSize;
use App\Filament\Resources\GalleryPhotos\Pages\CreateGalleryPhoto;
use App\Filament\Resources\GalleryPhotos\Pages\EditGalleryPhoto;
use App\Filament\Resources\GalleryPhotos\Pages\ListGalleryPhotos;
use App\Filament\Resources\GalleryRubrics\Pages\ManageGalleryRubrics;
use App\Models\GalleryPhoto;
use App\Models\GalleryRubric;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Storage::fake('local');
    Storage::fake('public');
    $this->rubric = GalleryRubric::create(['key' => 'sad', 'title' => 'Летний сад', 'position' => 0]);
});

it('uploads a photo, cuts web copies and shows it in the gallery', function () {
    Livewire::test(CreateGalleryPhoto::class)
        ->fillForm([
            'upload' => UploadedFile::fake()->image('garden.jpg', 1200, 800),
            'rubric_id' => $this->rubric->id,
            'size' => PhotoSize::Lead->value,
            'caption' => 'Фонтан в саду',
            'alt' => 'Фонтан среди зелени',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $photo = GalleryPhoto::sole();
    expect($photo->slug)->toBe('fontan-v-sadu')
        ->and([$photo->width, $photo->height])->toBe([1200, 800])
        ->and(array_keys($photo->variants['webp']))->toBe([480, 720, 1200])
        ->and(Storage::disk('public')->exists($photo->variants['jpg']))->toBeTrue()
        // Исходник гостям не отдаётся
        ->and(Storage::disk('local')->exists($photo->source_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($photo->source_path))->toBeFalse();

    $this->get('/gallery')->assertSee('id="foto-fontan-v-sadu"', false)->assertSee('Фонтан в саду');
    Livewire::test(ListGalleryPhotos::class)->assertCanSeeTableRecords([$photo]);
});

it('replaces the photo and removes the old copies and source', function () {
    Livewire::test(CreateGalleryPhoto::class)
        ->fillForm(['upload' => UploadedFile::fake()->image('a.jpg', 900, 600), 'rubric_id' => $this->rubric->id, 'size' => 'std', 'caption' => 'Зал', 'alt' => 'Зал'])
        ->call('create');
    $photo = GalleryPhoto::sole();
    $old = [$photo->variants['jpg'], $photo->source_path];

    Livewire::test(EditGalleryPhoto::class, ['record' => $photo->getRouteKey()])
        ->fillForm(['upload' => UploadedFile::fake()->image('b.png', 1000, 1500)])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($photo->refresh()->height)->toBe(1500)
        ->and(Storage::disk('public')->exists($old[0]))->toBeFalse()
        ->and(Storage::disk('local')->exists($old[1]))->toBeFalse()
        ->and(Storage::disk('public')->exists($photo->variants['jpg']))->toBeTrue();

    Livewire::test(EditGalleryPhoto::class, ['record' => $photo->getRouteKey()])->callAction('delete');
    expect(GalleryPhoto::count())->toBe(0)->and(Storage::disk('public')->allFiles('gallery'))->toBe([]);
});

it('manages rubrics and does not delete one with photos', function () {
    GalleryPhoto::factory()->create(['rubric_id' => $this->rubric->id]);

    Livewire::test(ManageGalleryRubrics::class)
        ->callAction('create', ['title' => 'Пивоварня', 'lede' => 'Медная башня'])
        ->assertHasNoActionErrors()
        ->assertTableActionHidden('delete', $this->rubric);

    expect(GalleryRubric::firstWhere('title', 'Пивоварня')->key)->toBe('pivovarnya');
});
