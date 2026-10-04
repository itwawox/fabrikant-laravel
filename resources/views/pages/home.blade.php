@use('App\Support\Html')
<x-layouts.site title="ФабрикантЪ - Ресторан с собственной пивоварней | Симферополь">
    <section class="section_welcome" id="section_welcome">
        <div class="container">
            <div class="row">
                <div class="col-sm-6 col-sm-offset-3 col-md-12">
                    <div class="welcome_content">
                        <h1 class="welcome-video_content_heading">
                            {!! Html::picture('/assets/img/logo-fabrikant.webp', '/assets/img/logo-fabrikant.png', 'class="img-responsive" width="541" height="201" alt="ФабрикантЪ — ресторан с собственной пивоварней"') !!}

                        </h1>
                        <ul class="welcome_content_logo">
                            <li>{!! Html::icon('dash') !!}</li>
                            <li>{!! Html::icon('fork') !!}</li>
                            <li>{!! Html::icon('wineglass') !!}</li>
                            <li>{!! Html::icon('knife') !!}</li>
                            <li>{!! Html::icon('dash') !!}</li>
                        </ul>
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
