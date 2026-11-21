{{-- 検索条件のフォーム。GET で送るので、条件は URL に残る（ページ番号は 1 に戻る）。$action を渡すと、その画面に送る（Elasticsearch 検索） --}}
@php
    $inputClass = 'w-full rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2 text-sm text-white placeholder:text-slate-600 outline-none transition focus:border-cyan-400/60 focus:ring-2 focus:ring-cyan-400/20';
    $c = $query->criteria;
    // [ラベル, URL の名前, 値, 例, 数字だけか]
    $fields = [
        ['市区町村', 'city', $c->city, '例：世田谷', false],
        ['町字', 'town', $c->town, '例：上北沢', false],
        ['丁目', 'chome', $c->chome, '例：一丁目', false],
        ['blk_num', 'blk', $c->blkNum, '例：6', true],
        ['rsdt_num', 'rsdt', $c->rsdtNum, '例：1', true],
    ];
@endphp
<form method="GET" action="{{ $action ?? route('rsdts.index') }}"
      class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-[1fr_1fr_9rem_6rem_6rem_auto_auto] lg:items-end">
    @foreach ($fields as [$label, $name, $value, $placeholder, $numeric])
        <label class="block">
            <span @class(['mb-1.5 block text-xs font-medium tracking-wide text-slate-400', 'font-mono' => $numeric])>{{ $label }}</span>
            <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" class="{{ $inputClass }}"
                   @if ($numeric) inputmode="numeric" title="完全一致" @endif>
        </label>
    @endforeach
    <label class="block">
        <span class="mb-1.5 block text-xs font-medium tracking-wide text-slate-400">表示件数</span>
        {{-- 件数を変えたら、すぐに検索し直す --}}
        <select name="per" class="{{ $inputClass }} pr-8" x-data @change="$el.form.requestSubmit()">
            @foreach (\App\Models\RsdtListQuery::PER_PAGE_OPTIONS as $n)
                <option value="{{ $n }}" @selected($n === $query->perPage)>{{ $n }} 件</option>
            @endforeach
        </select>
    </label>
    <button type="submit" class="cursor-pointer rounded-lg bg-gradient-to-r from-cyan-400 to-sky-500 px-6 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110">検索</button>
</form>
