{{-- Вверху каждой страницы админки, пока задачи ждут в очереди дольше двух минут (App\Support\QueueHealth) --}}
@include('filament.alert-style')
<div role="alert" class="fab-alert fab-alert--danger">
    <strong>Обработка меню и фото стоит.</strong> {{ \App\Support\QueueHealth::advice() }}
@if (\App\Support\QueueHealth::command())
    <code class="fab-alert__cmd">{{ \App\Support\QueueHealth::command() }}</code>
@endif
</div>
