<?php

use App\Models\RsdtSearchCriteria;
use App\UseCases\SearchRsdt;

function search(array $criteria = [], int $page = 1, int $perPage = 15)
{
    return (new SearchRsdt)->call(new RsdtSearchCriteria(...$criteria), $page, $perPage);
}

it('条件が無ければ全件を、主キーの順に 1 ページ分返す', function () {
    $r = search();

    expect($r->total)->toBe(TOTAL_ROWS)
        ->and($r->rows)->toHaveCount(15)
        ->and($r->totalPages)->toBe(intdiv(TOTAL_ROWS + 14, 15))
        ->and($r->rows->first()->routeId())->toBe(EXISTING_ID); // 元のデータの先頭の行
    $ids = $r->rows->map->routeId()->all();
    $sorted = $ids;
    sort($sorted);
    expect($ids)->toBe($sorted);
});

it('町字・丁目・blk_num で絞り込む（丁目は全角の「３丁目」も半角の 3 で見つかる）', function () {
    $r = search(['town' => '上北沢', 'chome' => '3', 'blkNum' => '1'], perPage: 100);

    expect($r->total)->toBe(21)
        ->and($r->rows->every(fn ($x) => $x->oaza_cho === '上北沢' && $x->chome === '３丁目' && $x->blk_num === '1'))->toBeTrue();
});

it('丁目の漢数字と数字は別の文字（「三丁目」では「３丁目」が出ない）', function () {
    expect(search(['town' => '上北沢', 'chome' => '三丁目'])->total)->toBe(0);
});

it('市区町村は部分一致', function () {
    $r = search(['city' => '世田谷']);

    expect($r->total)->toBeGreaterThan(0)
        ->and($r->rows->every(fn ($x) => str_contains($x->city, '世田谷')))->toBeTrue();
});

it('blk_num・rsdt_num は完全一致（「1」で 10・21 は出ない）', function () {
    $r = search(['town' => '上北沢', 'chome' => '3', 'blkNum' => '1', 'rsdtNum' => '1']);

    expect($r->rows->pluck('rsdt_num')->unique()->all())->toBe(['1']);
});

it('「%」「_」は、ただの文字として探す', function () {
    expect(search(['city' => '%'])->total)->toBe(0)
        ->and(search(['town' => '_'])->total)->toBe(0);
});

it('最後のページより後ろを指定されたら、最後のページを返す', function () {
    $r = search(['town' => '上北沢', 'chome' => '3', 'blkNum' => '1'], page: 99);

    expect($r->page)->toBe(2)->and($r->totalPages)->toBe(2)->and($r->rows)->toHaveCount(6);
});

it('0 件でも totalPages は 1', function () {
    $r = search(['city' => '存在しない市']);

    expect($r->total)->toBe(0)->and($r->totalPages)->toBe(1)->and($r->page)->toBe(1)->and($r->rows)->toBeEmpty();
});

it('perPage が 0 以下なら例外', function () {
    search(perPage: 0);
})->throws(InvalidArgumentException::class);
