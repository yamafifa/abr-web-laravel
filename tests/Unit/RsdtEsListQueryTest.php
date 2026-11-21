<?php

use App\Models\RsdtEsListQuery;

it('検索条件・件数・after を URL から読み、URL に戻せる', function () {
    $q = RsdtEsListQuery::fromParams(['town' => '上北沢', 'per' => '30', 'after' => EXISTING_ID]);

    expect([$q->criteria->town, $q->perPage, $q->after, $q->before, $q->last])->toBe(['上北沢', 30, EXISTING_ID, null, false])
        ->and($q->toParams())->toBe(['town' => '上北沢', 'per' => 30, 'after' => EXISTING_ID]);
});

it('キーの形でない after・before は無視する', function () {
    $q = RsdtEsListQuery::fromParams(['after' => '1 OR 1=1', 'before' => ['x']]);

    expect([$q->after, $q->before])->toBe([null, null]);
});

it('firstPage はページの位置だけを外す', function () {
    $q = RsdtEsListQuery::fromParams(['chome' => '3', 'before' => EXISTING_ID, 'last' => '1']);

    expect($q->firstPage()->toParams())->toBe(['chome' => '3']);
});
