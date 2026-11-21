<?php

use App\Models\RsdtSearchCriteria;
use App\Support\EsClient;
use App\UseCases\SearchRsdt;
use App\UseCases\SearchRsdtEs;

// 本物の Elasticsearch（abr-batch の npm run es:index で作ったインデックス）を使う

/** 世田谷区 上北沢３丁目 の blk_num=1（21 件） */
const KAMIKITAZAWA_3_1 = ['town' => '上北沢', 'chome' => '3', 'blkNum' => '1'];

function esSearch(array $criteria, int $perPage, ...$opts)
{
    return app(SearchRsdtEs::class)->call(new RsdtSearchCriteria(...$criteria), $perPage, ...$opts);
}

function ids($rows): array
{
    return $rows->map->routeId()->all();
}

it('DB 検索と同じ件数・同じ順で返す', function () {
    $r = esSearch(KAMIKITAZAWA_3_1, 100);
    $db = (new SearchRsdt)->call(new RsdtSearchCriteria(...KAMIKITAZAWA_3_1), 1, 100);

    expect($r->total)->toBe(21)->and(ids($r->rows))->toBe(ids($db->rows));
});

it('画面に出す値は DB と同じ（住所・日付）', function () {
    $row = esSearch(KAMIKITAZAWA_3_1, 1)->rows->first();
    $db = findRsdt($row->routeId());

    expect([$row->address(), $row->formValue('efct_date'), $row->formValue('ablt_date')])
        ->toBe([$db->address(), $db->formValue('efct_date'), $db->formValue('ablt_date')]);
});

it('次へ・前へで、重ならずに行き来できる', function () {
    $p1 = esSearch(KAMIKITAZAWA_3_1, 10);
    $p2 = esSearch(KAMIKITAZAWA_3_1, 10, after: $p1->nextKey);
    $p3 = esSearch(KAMIKITAZAWA_3_1, 10, after: $p2->nextKey);

    expect([$p1->from, $p2->from, $p3->from])->toBe([1, 11, 21])
        ->and([$p1->prevKey, $p3->nextKey])->toBe([null, null])
        ->and([...ids($p1->rows), ...ids($p2->rows), ...ids($p3->rows)])->toBe(ids(esSearch(KAMIKITAZAWA_3_1, 100)->rows))
        ->and(ids(esSearch(KAMIKITAZAWA_3_1, 10, before: $p3->prevKey)->rows))->toBe(ids($p2->rows));
});

it('前へで 1 ページに満たないときは、先頭の 1 ページを出す', function () {
    $p2 = esSearch(KAMIKITAZAWA_3_1, 10, after: esSearch(KAMIKITAZAWA_3_1, 5)->nextKey);
    $back = esSearch(KAMIKITAZAWA_3_1, 10, before: $p2->prevKey);

    expect([$back->from, $back->rows->count(), $back->prevKey])->toBe([1, 10, null]);
});

it('最後へは、最後の 1 ページ分を出す', function () {
    $r = esSearch(KAMIKITAZAWA_3_1, 10, last: true);

    expect([$r->from, $r->rows->count(), $r->nextKey])->toBe([12, 10, null]);
});

it('MySQL と同じく、全角と半角を同じ文字として探す（「3」で「３丁目」）', function () {
    $c = ['town' => '上北沢', 'chome' => '3'];

    expect(esSearch($c, 1)->total)->toBe((new SearchRsdt)->call(new RsdtSearchCriteria(...$c), 1, 1)->total);
});

it('* ? \\ は、ただの文字として探す', function () {
    expect(esSearch(['city' => '*'], 1)->total)->toBe(0)
        ->and(esSearch(['town' => '?'], 1)->total)->toBe(0);
});

it('インデックスの名前と作成日時を返す', function () {
    $r = esSearch(['city' => '存在しない市'], 1);

    expect($r->index)->toMatch('/\Arsdt_\d{8}-\d{6}\z/')
        ->and($r->builtAt())->toMatch('/\A\d{4}-\d\d-\d\d \d\d:\d\d:\d\d\z/');
});

it('Elasticsearch に接続できないときは、エラーの文を返す', function () {
    $r = (new SearchRsdtEs(new EsClient('http://127.0.0.1:1')))->call(new RsdtSearchCriteria(...KAMIKITAZAWA_3_1), 10);

    expect([$r->rows->all(), $r->total])->toBe([[], 0])->and($r->error)->toContain('接続できません');
});
