@php
    $r = $result;
    $to = $r->from === 0 ? 0 : $r->from + $r->rows->count() - 1;
@endphp
<x-layout>
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">住居の検索 <span class="ml-1 rounded-full bg-amber-400/15 px-2.5 py-0.5 align-middle text-xs font-semibold text-amber-300 ring-1 ring-amber-400/40">Elasticsearch</span></h1>
                <p class="mt-1 text-sm text-slate-400">
                    Elasticsearch のインデックスから検索します。中身は abr-batch の <code class="font-mono text-slate-300">npm run es:index</code> を実行した時点の DB のコピーです。
                    @if ($r->builtAt())（作成：<span class="font-mono text-slate-300">{{ $r->builtAt() }}</span>）@endif
                </p>
            </div>
            <a href="{{ route('rsdts.index', $query->toDbQuery()->toParams(), false) }}" class="text-sm text-cyan-300 hover:underline">同じ条件で DB 検索 →</a>
        </div>

        <section class="rounded-2xl border border-white/10 bg-slate-900/50 p-5 shadow-xl shadow-black/20 backdrop-blur">
            @include('rsdts._search-form', ['query' => $query, 'action' => route('rsdts.es')])
        </section>

        @if ($r->error)
            <p class="rounded-xl border border-rose-400/40 bg-rose-500/10 px-5 py-4 text-sm text-rose-200">{{ $r->error }}</p>
        @endif

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/50 shadow-xl shadow-black/20 backdrop-blur">
            <div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-white/10 px-5 py-3 text-sm">
                <p class="text-slate-400"><span class="font-mono text-lg font-semibold text-white">{{ number_format($r->total) }}</span> 件</p>
                <p class="font-mono text-xs text-slate-500">{{ number_format($r->from) }}–{{ number_format($to) }} 件目</p>
            </div>

            @if ($r->rows->isEmpty())
                <p class="px-5 py-16 text-center text-slate-500">該当する住居がありません</p>
            @else
                @include('rsdts._table', ['rows' => $r->rows, 'back' => $back])
            @endif

            @if ($r->prevKey !== null || $r->nextKey !== null)
                <div class="border-t border-white/10 px-5 py-4">
                    <x-es-pager :query="$query->firstPage()" :prev-key="$r->prevKey" :next-key="$r->nextKey" />
                </div>
            @endif
        </section>

        <p class="text-xs text-slate-500">
            編集・削除は DB に対して行います。Elasticsearch には次に <code class="font-mono">npm run es:index</code> を実行するまで反映されないので、この一覧には古い内容が残ります。
        </p>
    </div>
</x-layout>
