@use('App\Support\Html')
@php($texts = \App\Support\PageTexts::for('home'))
<x-layouts.site :title="$texts->title()" :description="$texts->description()" :image="$texts->image()">
    <section class="section_welcome" id="section_welcome">
        <div class="container">
            <div class="row">
                <div class="col-sm-6 col-sm-offset-3 col-md-12">
                    <div class="welcome_content">
                        <h1 class="welcome-video_content_heading">
                            {!! Html::svg('logo-full', 'welcome-logo', 'ФабрикантЪ — ресторан с собственной пивоварней') !!}

                        </h1>
                        <span class="welcome-ornament" aria-hidden="true"></span>
                        <p class="welcome-actions">
                            <a class="welcome-action welcome-action--main" href="{{ $site->tel() }}">
                                <span>Забронировать стол</span>
                                <span class="welcome-action__note">{{ $site->phone() }}</span>
                            </a>
                            <a class="welcome-action" href="/menu">
                                <span>Меню ресторана</span>
                                <span class="welcome-action__note">кухня и своя пивоварня</span>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Фон: на телефоне — кадр из ролика, на широком экране site.js подгружает само видео -->
        <div class="welcome-video_bg">
            <video muted loop playsinline preload="none" data-src="/assets/video/video_bg-v2.mp4"></video>
        </div>
    </section>

</x-layouts.site>
