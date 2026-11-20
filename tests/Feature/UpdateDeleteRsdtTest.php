<?php

use App\Models\Rsdt;
use App\UseCases\DeleteRsdt;
use App\UseCases\GetRsdt;
use App\UseCases\UpdateRsdt;
use App\UseCases\UpdateRsdtStatus;

describe('GetRsdt', function () {
    it('id で 1 件取る', function () {
        expect((new GetRsdt)->call(EXISTING_ID)->address())->toBe('千代田区 内幸町一丁目 6-1');
    });

    it('無い行・正しくない id は null', function () {
        expect((new GetRsdt)->call(MISSING_ID))->toBeNull()
            ->and((new GetRsdt)->call('bad'))->toBeNull();
    });
});

describe('UpdateRsdt', function () {
    it('主キー以外の列を更新する', function () {
        $input = inputOf(findRsdt(EXISTING_ID), ['remarks' => 'テスト', 'efct_date' => '2026-01-02']);

        expect((new UpdateRsdt)->call(EXISTING_ID, $input))->toBe(UpdateRsdtStatus::Updated);
        $after = findRsdt(EXISTING_ID);
        expect($after->remarks)->toBe('テスト')
            ->and($after->formValue('efct_date'))->toBe('2026-01-02')
            ->and($after->city)->toBe('千代田区');
    });

    it('入力に無い列・null・空文字の列は NULL にする', function () {
        (new UpdateRsdt)->call(EXISTING_ID, ['city' => '千代田区', 'blk_num' => null, 'rsdt_num' => '', 'ablt_date' => ' ']);
        $after = findRsdt(EXISTING_ID);

        expect($after->blk_num)->toBeNull()->and($after->rsdt_num)->toBeNull()->and($after->ablt_date)->toBeNull()
            ->and($after->oaza_cho)->toBeNull()->and($after->efct_date)->toBeNull();
    });

    it('無い行・正しくない id は NotFound', function () {
        expect((new UpdateRsdt)->call(MISSING_ID, ['city' => 'x']))->toBe(UpdateRsdtStatus::NotFound)
            ->and((new UpdateRsdt)->call('bad', ['city' => 'x']))->toBe(UpdateRsdtStatus::NotFound)
            ->and(Rsdt::query()->count())->toBe(TOTAL_ROWS);
    });

    it('ほかの行は変えない', function () {
        $neighbor = findRsdt('131016_0001001_006_002_')->getAttributes();
        (new UpdateRsdt)->call(EXISTING_ID, inputOf(findRsdt(EXISTING_ID), ['remarks' => 'テスト']));

        expect(findRsdt('131016_0001001_006_002_')->getAttributes())->toBe($neighbor);
    });
});

describe('DeleteRsdt', function () {
    it('1 行だけ削除する', function () {
        (new DeleteRsdt)->call(EXISTING_ID);

        expect(findRsdt(EXISTING_ID))->toBeNull()->and(Rsdt::query()->count())->toBe(TOTAL_ROWS - 1);
    });

    it('無い行・正しくない id でも失敗しない（何も消さない）', function () {
        (new DeleteRsdt)->call(MISSING_ID);
        (new DeleteRsdt)->call('bad');
        (new DeleteRsdt)->call('');

        expect(Rsdt::query()->count())->toBe(TOTAL_ROWS);
    });
});
