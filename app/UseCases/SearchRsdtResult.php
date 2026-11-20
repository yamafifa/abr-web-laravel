<?php

namespace App\UseCases;

use App\Models\Rsdt;
use Illuminate\Support\Collection;

/**
 * page は実際に表示したページ。totalPages は 0 件でも 1
 */
final readonly class SearchRsdtResult
{
    /** @param Collection<int, Rsdt> $rows */
    public function __construct(
        public Collection $rows,
        public int $total,
        public int $page,
        public int $totalPages,
    ) {}
}
