{{-- Плашка-предупреждение в админке: видна и в светлой, и в тёмной теме --}}
@once
<style>
    .fab-alert { margin: .75rem 0 1rem; padding: .75rem 1rem; border: 1px solid var(--warning-500); border-left-width: 4px; border-radius: .5rem; background: var(--warning-50); color: var(--gray-900); font-size: .875rem; line-height: 1.5; }
    .fab-alert--danger { border-color: var(--danger-500); background: var(--danger-50); }
    .dark .fab-alert { background: color-mix(in oklab, var(--warning-500) 14%, transparent); color: var(--gray-100); }
    .dark .fab-alert--danger { background: color-mix(in oklab, var(--danger-500) 16%, transparent); }
    .fab-alert__cmd { display: block; margin-top: .5rem; padding: .4rem .6rem !important; white-space: nowrap; overflow-x: auto; user-select: all; }
    .fab-alert code { font-size: .8125rem; padding: .05rem .3rem; border-radius: .25rem; background: color-mix(in oklab, currentColor 10%, transparent); }
</style>
@endonce
