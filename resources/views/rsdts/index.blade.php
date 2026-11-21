@php
    $r = $result;
    $from = $r->total === 0 ? 0 : ($r->page - 1) * $query->perPage + 1;
    $to = ($r->page - 1) * $query->perPage + $r->rows->count();
@endphp
<x-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">住居の検索 <span class="ml-1 rounded-full bg-cyan-400/15 px-2.5 py-0.5 align-middle text-xs font-semibold text-cyan-300 ring-1 ring-cyan-400/40">DB</span></h1>
                <p class="mt-1 text-sm text-slate-400">住居表示-住居マスター（東京都）を検索して、編集・削除します。</p>
            </div>
            <a href="{{ route('rsdts.es', (new \App\Models\RsdtEsListQuery($query->criteria, $query->perPage))->toParams(), false) }}" class="text-sm text-amber-300 hover:underline">同じ条件で Elasticsearch 検索 →</a>
        </div>

        <section class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 shadow-xl shadow-black/20 backdrop-blur">
            @include('rsdts._search-form', ['query' => $query])
        </section>

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/50 shadow-xl shadow-black/20 backdrop-blur">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/10 px-5 py-3 text-sm">
                <p class="text-slate-400"><span class="font-mono text-lg font-semibold text-white">{{ number_format($r->total) }}</span> 件</p>
                <p class="font-mono text-xs text-slate-500">
                    {{ number_format($from) }}–{{ number_format($to) }} 件目 ／ {{ number_format($r->page) }} / {{ number_format($r->totalPages) }} ページ
                </p>
            </div>

            @if ($r->rows->isEmpty())
                <p class="px-5 py-16 text-center text-slate-500">該当する住居がありません</p>
            @else
                @include('rsdts._table', ['rows' => $r->rows, 'back' => $back])
            @endif

            @if ($r->totalPages > 1)
                <div class="border-t border-white/10 px-5 py-4">
                    <x-pager :query="$query" :page="$r->page" :total-pages="$r->totalPages" />
                </div>
            @endif
        </section>
    </div>
</x-layout>
