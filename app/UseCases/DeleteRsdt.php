<?php

namespace App\UseCases;

use App\Models\Rsdt;

/**
 * 住居を削除する。すでに無ければ、消せたものとして扱う（同じ削除を 2 回送られても失敗にしない）
 */
class DeleteRsdt
{
    public function call(string $id): void
    {
        $key = Rsdt::keyFromParam($id);
        if ($key) {
            Rsdt::query()->where($key)->delete();
        }
    }
}
