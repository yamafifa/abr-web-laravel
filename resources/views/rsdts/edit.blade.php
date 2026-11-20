<x-layout :title="$rsdt->address().' の編集'">
    <div class="space-y-6">
        <div>
            <a href="{{ $back }}" class="text-sm text-slate-400 hover:text-cyan-300">‹ 一覧に戻る</a>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-white">{{ $rsdt->address() }}</h1>
            <dl class="mt-3 flex flex-wrap gap-2">
                @foreach (\App\Models\Rsdt::KEY_COLUMNS as $col)
                    <div class="rounded-lg border border-white/10 bg-slate-900/50 px-3 py-1.5">
                        <dt class="font-mono text-[10px] text-slate-500">{{ $col }}</dt>
                        <dd class="font-mono text-sm text-slate-200">
                            @if ((string) $rsdt->$col !== ''){{ $rsdt->$col }}@else<span class="text-slate-600">（空）</span>@endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-2 text-xs text-slate-500">主キーの 5 列は変更できません。</p>
        </div>

        {{-- 「更新」を押すと、すぐには送らず、変わった項目をモーダルで見せる（resources/js/app.js の diff） --}}
        <div x-data="confirm({ diff: true })" @submit="open">
            <form method="POST" action="{{ route('rsdts.update', ['id' => $rsdt->routeId(), 'back' => $back]) }}"
                  class="rounded-2xl border border-white/10 bg-slate-900/50 shadow-xl shadow-black/20 backdrop-blur">
                @csrf
                @method('PATCH')
                <div class="grid gap-x-6 gap-y-5 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (\App\Models\Rsdt::ATTRIBUTE_RULES as $rule)
                        @php
                            $name = $rule['name'];
                            $isDate = ($rule['type'] ?? null) === 'date';
                            // 入力チェックに通らず戻ってきたときは、入力したままの値（old）を出す
                            $original = $rsdt->formValue($name);
                            $value = (string) old($name, $original);
                        @endphp
                        <label @class(['block', 'sm:col-span-2 lg:col-span-3' => $name === 'remarks'])>
                            <span class="mb-1.5 flex items-baseline justify-between font-mono text-xs text-slate-400">
                                <span>{{ $name }}@if ($rule['required'] ?? false)<span class="ml-1 text-rose-400">*</span>@endif</span>
                                @isset($rule['max'])<span class="text-[10px] text-slate-600">最大 {{ $rule['max'] }} 文字</span>@endisset
                            </span>
                            <input type="{{ $isDate ? 'date' : 'text' }}" name="{{ $name }}" value="{{ $value }}"
                                   data-column="{{ $name }}" data-original="{{ $original }}"
                                   @error($name) aria-invalid="true" @enderror
                                   @class([
                                       'w-full rounded-lg border bg-slate-950/60 px-3 py-2 text-sm text-white outline-none transition focus:ring-2',
                                       'border-rose-400/60 focus:ring-rose-400/20' => $errors->has($name),
                                       'border-white/10 focus:border-cyan-400/60 focus:ring-cyan-400/20' => ! $errors->has($name),
                                   ])>
                            @error($name)<span class="mt-1 block text-xs text-rose-400">{{ $message }}</span>@enderror
                        </label>
                    @endforeach
                </div>
                <div class="flex items-center gap-3 border-t border-white/10 bg-slate-950/30 px-6 py-4">
                    <a href="{{ $back }}" class="ml-auto rounded-lg px-4 py-2 text-sm text-slate-300 hover:bg-white/5">キャンセル</a>
                    <button type="submit" class="cursor-pointer rounded-lg bg-gradient-to-r from-cyan-400 to-sky-500 px-6 py-2 text-sm font-semibold text-slate-950 shadow-lg shadow-cyan-500/20 transition hover:brightness-110">更新</button>
                </div>
            </form>
            <x-confirm-dialog title="この内容で更新しますか？" confirm-label="更新する">
                <p x-show="changes.length === 0">変更された項目はありません。このまま保存しますか？</p>
                <table x-show="changes.length > 0" class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-slate-500">
                            <th class="py-1 pr-2 font-medium">列</th>
                            <th class="py-1 pr-2 font-medium">変更前</th>
                            <th class="py-1 font-medium">変更後</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 font-mono">
                        <template x-for="c in changes" :key="c.column">
                            <tr>
                                <td class="py-1.5 pr-2 text-slate-400" x-text="c.column"></td>
                                <td class="py-1.5 pr-2 text-slate-500 line-through" x-text="c.before || '（空）'"></td>
                                <td class="py-1.5 text-cyan-300" x-text="c.after || '（空）'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-confirm-dialog>
        </div>

        {{-- 削除ボタンは自分のフォームを持つので、編集フォームの外に置く（フォームは入れ子にできない） --}}
        <div class="flex justify-start">
            <x-delete-button :rsdt="$rsdt" :back="$back" />
        </div>
    </div>
</x-layout>
