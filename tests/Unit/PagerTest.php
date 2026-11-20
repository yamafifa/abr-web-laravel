<?php

use App\Support\Pager;

it('ページ番号を、今のページの前後 2 つと最初・最後にする（間は null）', function (int $page, int $total, array $expected) {
    expect(Pager::items($page, $total))->toBe($expected);
})->with([
    '1 ページだけ' => [1, 1, [1]],
    '少ない' => [2, 5, [1, 2, 3, 4, 5]],
    '最初' => [1, 100, [1, 2, 3, null, 100]],
    '真ん中' => [50, 100, [1, null, 48, 49, 50, 51, 52, null, 100]],
    '最後' => [100, 100, [1, null, 98, 99, 100]],
    '1 つだけ飛ぶところも null' => [5, 10, [1, null, 3, 4, 5, 6, 7, null, 10]],
    'すき間が無ければ null を入れない' => [4, 10, [1, 2, 3, 4, 5, 6, null, 10]],
]);
