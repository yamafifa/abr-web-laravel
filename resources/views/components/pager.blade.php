@props(['query', 'page', 'totalPages'])
@php
    $base = 'grid h-9 min-w-9 place-items-center rounded-lg px-3 text-sm transition';
    $href = fn (int $p) => route('rsdts.index', $query->withPage($p)->toParams(), false);
@endphp
<nav aria-label="ページ送り" class="flex flex-wrap items-center justify-center gap-1">
    @if ($page > 1)
        <a href="{{ $href($page - 1) }}" class="{{ $base }} text-slate-300 hover:bg-white/5">‹ 前へ</a>
    @else
        <span class="{{ $base }} text-slate-600">‹ 前へ</span>
    @endif
    @foreach (\App\Support\Pager::items($page, $totalPages) as $p)
        @if ($p === null)
            <span class="px-1 text-slate-600">…</span>
        @elseif ($p === $page)
            <span aria-current="page" class="{{ $base }} bg-cyan-400/15 font-semibold text-cyan-300 ring-1 ring-cyan-400/40">{{ number_format($p) }}</span>
        @else
            <a href="{{ $href($p) }}" class="{{ $base }} text-slate-400 hover:bg-white/5 hover:text-white">{{ number_format($p) }}</a>
        @endif
    @endforeach
    @if ($page < $totalPages)
        <a href="{{ $href($page + 1) }}" class="{{ $base }} text-slate-300 hover:bg-white/5">次へ ›</a>
    @else
        <span class="{{ $base }} text-slate-600">次へ ›</span>
    @endif
</nav>
