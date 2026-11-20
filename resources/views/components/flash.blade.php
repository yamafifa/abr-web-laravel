{{-- 更新・削除のあとのお知らせ。3 秒で消える --}}
@php
    $messages = array_filter([
        'notice' => session('notice'),
        // 入力チェックに通らず、編集画面に戻ってきたとき
        'alert' => session('alert') ?? ($errors->any() ? '入力内容を確認してください' : null),
    ]);
@endphp
@foreach ($messages as $type => $message)
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
         x-transition:leave="transition-opacity duration-300" x-transition:leave-end="opacity-0"
         @class([
             'fixed right-4 top-20 z-50 flex items-center gap-2 rounded-xl border px-4 py-3 text-sm shadow-lg shadow-black/30 backdrop-blur',
             'border-emerald-400/30 bg-emerald-500/15 text-emerald-200' => $type === 'notice',
             'border-rose-400/30 bg-rose-500/15 text-rose-200' => $type === 'alert',
         ])>
        <span @class(['size-2 rounded-full', 'bg-emerald-400' => $type === 'notice', 'bg-rose-400' => $type === 'alert'])></span>
        {{ $message }}
    </div>
@endforeach
