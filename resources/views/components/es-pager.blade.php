{{-- ページ送り（search_after）。ページ番号は無く、最初・前・次・最後へ進む。query はページの位置を外した検索条件 --}}
@props(['query', 'prevKey', 'nextKey'])
@php
    $base = 'grid h-9 min-w-9 place-items-center rounded-lg px-3 text-sm transition';
    $on = "{$base} text-slate-300 hover:bg-white/5";
    $off = "{$base} text-slate-600";
    $href = fn ($q) => route('rsdts.es', $q->toParams(), false);
@endphp
<nav aria-label="ページ送り" class="flex flex-wrap items-center justify-center gap-1">
    @if ($prevKey !== null)
        <a href="{{ $href($query) }}" class="{{ $on }}">« 最初へ</a>
        <a href="{{ $href($query->withBefore($prevKey)) }}" class="{{ $on }}">‹ 前へ</a>
    @else
        <span class="{{ $off }}">« 最初へ</span>
        <span class="{{ $off }}">‹ 前へ</span>
    @endif
    @if ($nextKey !== null)
        <a href="{{ $href($query->withAfter($nextKey)) }}" class="{{ $on }}">次へ ›</a>
        <a href="{{ $href($query->withLast()) }}" class="{{ $on }}">最後へ »</a>
    @else
        <span class="{{ $off }}">次へ ›</span>
        <span class="{{ $off }}">最後へ »</span>
    @endif
</nav>
