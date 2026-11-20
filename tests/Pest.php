<?php

use App\Models\Rsdt;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/*
| Feature のテストは Laravel を起動し、本物の DB（abr-batch と共用）を使う。
| 1 件ごとにトランザクションで取り消す（DatabaseTransactions）。
| RefreshDatabase は、テーブルを作り直す（migrate:fresh）ので使わない。
| Unit のテストは Laravel を起動しない。
*/
pest()->extend(TestCase::class)
    ->use(DatabaseTransactions::class)
    ->in('Feature');

/** 元のデータにある行（千代田区 内幸町一丁目 6-1） */
const EXISTING_ID = '131016_0001001_006_001_';

/** 無い行 */
const MISSING_ID = '131016_0001001_006_999_99999';

/** 元のデータの全件数 */
const TOTAL_ROWS = 1_752_573;

/** 主キー以外の 17 列を、今の値で埋めた入力（$overrides で一部を変える） */
function inputOf(Rsdt $rsdt, array $overrides = []): array
{
    $input = [];
    foreach (Rsdt::editableAttributes() as $column) {
        $input[$column] = $rsdt->formValue($column);
    }

    return array_merge($input, $overrides);
}

function findRsdt(string $id): ?Rsdt
{
    return Rsdt::query()->where(Rsdt::keyFromParam($id))->first();
}
