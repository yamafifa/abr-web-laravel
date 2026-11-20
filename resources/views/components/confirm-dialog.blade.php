{{--
    確認用のモーダル。x-data="confirm" の要素の中に置く（resources/js/app.js）
    使い方: <x-confirm-dialog title="..." confirm-label="..." :danger="true"> 本文 </x-confirm-dialog>
    ブラウザの <dialog> を使うので、Esc で閉じる・後ろを操作できない、が最初から付いている
--}}
@props(['title', 'confirmLabel', 'danger' => false])
<dialog x-ref="dialog" @click="backdrop"
        class="m-auto w-[min(32rem,calc(100vw-2rem))] rounded-2xl border border-white/10 bg-slate-900/95 p-0 text-slate-200 shadow-2xl shadow-black/50">
    <div class="p-6">
        <div class="flex items-center gap-3">
            <span @class(['grid size-9 place-items-center rounded-full', 'bg-rose-500/15 text-rose-400' => $danger, 'bg-cyan-500/15 text-cyan-300' => ! $danger])>{{ $danger ? '!' : '?' }}</span>
            <h2 class="text-lg font-semibold text-white">{{ $title }}</h2>
        </div>
        <div class="mt-4 text-sm text-slate-300">{{ $slot }}</div>
    </div>
    <div class="flex justify-end gap-2 border-t border-white/10 bg-slate-950/40 px-6 py-4">
        <button type="button" @click="cancel" class="rounded-lg px-4 py-2 text-sm text-slate-300 hover:bg-white/5">キャンセル</button>
        <button type="button" @click="accept" :disabled="busy" x-text="busy ? '処理中…' : @js($confirmLabel)" autofocus
                @class([
                    'rounded-lg px-4 py-2 text-sm font-medium text-slate-950 shadow-lg disabled:opacity-60',
                    'bg-rose-400 shadow-rose-500/20 hover:bg-rose-300' => $danger,
                    'bg-cyan-400 shadow-cyan-500/20 hover:bg-cyan-300' => ! $danger,
                ])>{{ $confirmLabel }}</button>
    </div>
</dialog>
