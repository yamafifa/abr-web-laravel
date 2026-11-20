<?php

namespace App\UseCases;

use App\Models\Rsdt;
use App\Models\RsdtSearchCriteria;
use InvalidArgumentException;

/**
 * 住居を検索して、1 ページ分を返す
 */
class SearchRsdt
{
    public function call(RsdtSearchCriteria $criteria, int $page, int $perPage): SearchRsdtResult
    {
        if ($perPage <= 0) {
            throw new InvalidArgumentException("perPage が正しくありません: {$perPage}");
        }

        $total = Rsdt::query()->search($criteria)->count();
        $totalPages = max(intdiv($total + $perPage - 1, $perPage), 1);
        // 最後のページより後ろを指定されたら、最後のページを出す
        $current = min(max($page, 1), $totalPages);
        $rows = Rsdt::query()->search($criteria)->inKeyOrder()
            ->offset(($current - 1) * $perPage)->limit($perPage)->get();

        return new SearchRsdtResult($rows, $total, $current, $totalPages);
    }
}
