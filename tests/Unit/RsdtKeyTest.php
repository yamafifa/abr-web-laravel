<?php

use App\Console\DbCommandGuard;
use App\Models\Rsdt;

it('URL の id を主キーの 5 列にする（rsdt2_id が空なら末尾が _）', function () {
    expect(Rsdt::keyFromParam('131016_0001001_006_001_'))->toBe([
        'lg_code' => '131016', 'machiaza_id' => '0001001', 'blk_id' => '006', 'rsdt_id' => '001', 'rsdt2_id' => '',
    ])->and(Rsdt::keyFromParam('131016_0001001_006_999_99999')['rsdt2_id'])->toBe('99999');
});

it('正しくない id は null', function (?string $id) {
    expect(Rsdt::keyFromParam($id))->toBeNull();
})->with([null, '', 'bad', '131016_0001001_006_001', '131016_0001001_006_001__x', '131016__006_001_']);

it('LIKE の特別な文字をエスケープする', function () {
    expect(Rsdt::escapeLike('50%_a\\b'))->toBe('50\\%\\_a\\\\b');
});

it('DB を作り直すコマンドの名前を見分ける', function () {
    foreach (['migrate', 'migrate:fresh', 'migrate:install', 'migrate:status', 'db:wipe', 'db:seed', 'schema:dump'] as $c) {
        expect(DbCommandGuard::blocks($c))->toBeTrue("{$c} を止めていない");
    }
    foreach (['serve', 'route:list', 'db:show', 'tinker', 'make:model'] as $c) {
        expect(DbCommandGuard::blocks($c))->toBeFalse("{$c} を止めている");
    }
});
