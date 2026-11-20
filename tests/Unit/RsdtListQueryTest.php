<?php

use App\Models\RsdtListQuery;
use App\Models\RsdtSearchCriteria;

it('URL のクエリを検索条件・件数・ページにする（前後の空白は取る）', function () {
    $q = RsdtListQuery::fromParams(['city' => ' 世田谷 ', 'town' => '上北沢', 'chome' => '3', 'blk' => '1', 'rsdt' => '2', 'per' => '50', 'page' => '3']);

    expect($q->criteria)->toEqual(new RsdtSearchCriteria('世田谷', '上北沢', '3', '1', '2'))
        ->and($q->perPage)->toBe(50)
        ->and($q->page)->toBe(3);
});

it('おかしな値は既定値にする', function (array $params) {
    $q = RsdtListQuery::fromParams($params);

    expect($q->perPage)->toBe(15)->and($q->page)->toBe(1);
})->with([
    '空' => [[]],
    '選べない件数' => [['per' => '20', 'page' => '0']],
    '数字でない' => [['per' => 'abc', 'page' => 'x']],
    '負のページ' => [['page' => '-1']],
    '小数' => [['per' => '15.5', 'page' => '1.5']],
]);

it('配列で来たら最初の値を使う', function () {
    $q = RsdtListQuery::fromParams(['city' => ['千代田', '港'], 'per' => ['30']]);

    expect($q->criteria->city)->toBe('千代田')->and($q->perPage)->toBe(30);
});

it('長すぎる条件は、上限の文字数で切る（文字数はマルチバイトでも 1 文字）', function () {
    $q = RsdtListQuery::fromParams(['city' => str_repeat('あ', 100), 'blk' => str_repeat('1', 30)]);

    expect(mb_strlen($q->criteria->city))->toBe(80)->and(strlen($q->criteria->blkNum))->toBe(20);
});

it('URL のクエリに戻すとき、空の条件と既定値は省く', function () {
    expect(RsdtListQuery::fromParams([])->toParams())->toBe([])
        ->and(RsdtListQuery::fromParams(['town' => '上北沢', 'blk' => '1', 'per' => '30', 'page' => '2'])->toParams())
        ->toBe(['town' => '上北沢', 'blk' => '1', 'per' => 30, 'page' => 2]);
});

it('withPage はページだけ変える', function () {
    $q = RsdtListQuery::fromParams(['city' => '港', 'page' => '5'])->withPage(1);

    expect($q->toParams())->toBe(['city' => '港']);
});
