<?php

it('件数と 1 ページ分、次へ・最後へのリンクを表示する', function () {
    $res = $this->get('/es?town=上北沢&chome=3&blk=1')->assertOk()
        ->assertSee('<span class="font-mono text-lg font-semibold text-white">21</span>', false)
        ->assertSee('1–15 件目')
        ->assertSee('after=131121_0001003_001_015_', false)
        ->assertSee('last=1', false);

    expect(preg_match_all('#/rsdt/[0-9_]+/edit#', $res->getContent()))->toBe(15);
});

it('編集・削除のリンクに、今の URL を戻り先として付ける', function () {
    $back = route('rsdts.es', ['town' => '上北沢', 'chome' => '3', 'blk' => '1', 'after' => '131121_0001003_001_015_'], false);

    $this->get('/es?town=上北沢&chome=3&blk=1&after=131121_0001003_001_015_')->assertOk()
        ->assertSee('16–21 件目')
        ->assertSee(e(route('rsdts.edit', ['id' => '131121_0001003_001_016_', 'back' => $back])), false);
});

it('同じ条件の DB 検索へのリンクがあり、DB 検索からも戻れる', function () {
    $this->get('/es?town=上北沢')->assertSee('href="/?town=%E4%B8%8A%E5%8C%97%E6%B2%A2"', false);
    $this->get('/?town=上北沢')->assertSee('href="/es?town=%E4%B8%8A%E5%8C%97%E6%B2%A2"', false);
});
