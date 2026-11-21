<?php

use App\Support\RsdtFold;

it('全角と半角、大文字と小文字をそろえる', function () {
    expect(RsdtFold::call('３丁目ＡＢｃ'))->toBe('3丁目abc');
});

it('カタカナをひらがなにして、濁点・半濁点を消す', function () {
    expect(RsdtFold::call('パガあ'))->toBe('はかあ');
});

it('小さい仮名を大きくする（ヶ → け、ヵ → か）', function () {
    expect(RsdtFold::call('ヶヵっ'))->toBe('けかつ');
});

it('CJK 互換漢字はそのまま残す（MySQL は別の文字として扱う）', function () {
    $compat = mb_chr(0xFA10); // 塚（互換漢字）
    expect(RsdtFold::call($compat))->toBe($compat)
        ->and(RsdtFold::call('塚'))->toBe('塚');
});
