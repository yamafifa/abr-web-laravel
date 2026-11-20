{{-- 削除ボタン。押すとモーダルで確認してから送る --}}
@props(['rsdt', 'back', 'variant' => 'button'])
<div x-data="confirm" @submit="open" class="inline-block">
    <form method="POST" action="{{ route('rsdts.destroy', ['id' => $rsdt->routeId(), 'back' => $back]) }}">
        @csrf
        @method('DELETE')
        <button type="submit" @class([
            'cursor-pointer text-rose-300 hover:bg-rose-500/10',
            'rounded-md px-2.5 py-1 text-xs ring-1 ring-rose-400/30' => $variant === 'icon',
            'rounded-lg px-4 py-2 text-sm ring-1 ring-rose-400/40' => $variant !== 'icon',
        ])>削除</button>
    </form>
    <x-confirm-dialog title="この行を削除しますか？" confirm-label="削除する" :danger="true">
        <p class="rounded-lg bg-slate-950/60 px-3 py-2 font-medium text-white">{{ $rsdt->address() }}</p>
        <p class="mt-2 font-mono text-xs text-slate-500">{{ $rsdt->routeId() }}</p>
        <p class="mt-3">削除すると元に戻せません。</p>
    </x-confirm-dialog>
</div>
