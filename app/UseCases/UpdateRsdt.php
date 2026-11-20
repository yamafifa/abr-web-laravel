<?php

namespace App\UseCases;

use App\Models\Rsdt;
use Illuminate\Support\Facades\DB;

/**
 * 住居の主キー以外の列を更新する
 *
 * 入力のチェックは、ここに来る前に UpdateRsdtRequest（FormRequest）で済ませてある。
 * Rails 版はモデルで入力をチェックするので、ユースケースが「入力エラー」も返すが、Laravel 版は返さない。
 */
class UpdateRsdt
{
    /**
     * @param  array<string, string|null>  $input  列名 → 値。無い列・空文字は NULL として扱う
     */
    public function call(string $id, array $input): UpdateRsdtStatus
    {
        $key = Rsdt::keyFromParam($id);
        if (! $key) {
            return UpdateRsdtStatus::NotFound;
        }

        return DB::transaction(function () use ($key, $input) {
            // 読んでから更新するまでの間に、ほかの画面で消されないように行をロックする
            // （Eloquent の save は、行が消えていて 0 行の更新になっても成功扱いになるため）
            $rsdt = Rsdt::query()->where($key)->lockForUpdate()->first();
            if (! $rsdt) {
                return UpdateRsdtStatus::NotFound;
            }

            // 画面から来るときは、ミドルウェアが空文字を NULL にしてあるが、
            // ほかから呼ばれても同じになるように、ここでも空文字は NULL にする（空文字は DATE 型の列に入らない）
            $values = [];
            foreach (Rsdt::editableAttributes() as $column) {
                $v = $input[$column] ?? null;
                $values[$column] = $v === null || trim($v) === '' ? null : $v;
            }
            $rsdt->fill($values)->save();

            return UpdateRsdtStatus::Updated;
        });
    }
}
