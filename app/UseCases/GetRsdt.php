<?php

namespace App\UseCases;

use App\Models\Rsdt;

/**
 * 住居を 1 件取る。無ければ null
 */
class GetRsdt
{
    public function call(string $id): ?Rsdt
    {
        $key = Rsdt::keyFromParam($id);

        return $key ? Rsdt::query()->where($key)->first() : null;
    }
}
