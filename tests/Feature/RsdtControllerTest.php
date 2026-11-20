<?php

use App\Models\Rsdt;

describe('一覧', function () {
    it('検索結果と件数を出す', function () {
        $this->get('/?town=上北沢&chome=3&blk=1')
            ->assertOk()
            ->assertSee('上北沢３丁目 1-1')
            ->assertSee('<span class="font-mono text-lg font-semibold text-white">21</span>', false);
    });

    it('編集・削除のリンクに、今の検索条件とページを付ける', function () {
        // 戻り先の URL（/?town=%E4%B8%8A...）を、さらに URL のクエリに入れるので、2 回エンコードされる
        $back = route('rsdts.index', ['town' => '上北沢', 'chome' => '3', 'blk' => '1', 'page' => 2], false);

        $this->get('/?town=上北沢&chome=3&blk=1&page=2')
            ->assertOk()
            ->assertSee(e(route('rsdts.edit', ['id' => '131121_0001003_001_016_', 'back' => $back])), false)
            ->assertSee(e(route('rsdts.destroy', ['id' => '131121_0001003_001_016_', 'back' => $back])), false);
    });

    it('0 件のときは、そう出す', function () {
        $this->get('/?city=存在しない市')->assertOk()->assertSee('該当する住居がありません');
    });

    it('入力値は HTML としてではなく文字として出す', function () {
        $this->get('/?city=<script>x</script>')
            ->assertOk()
            ->assertDontSee('<script>x</script>', false)
            ->assertSee('&lt;script&gt;x&lt;/script&gt;', false);
    });
});

describe('編集画面', function () {
    it('今の値と戻り先を出す', function () {
        $this->get('/rsdt/'.EXISTING_ID.'/edit?back=/?page=2')
            ->assertOk()
            ->assertSee('name="city" value="千代田区"', false)
            ->assertSee('href="/?page=2"', false);
    });

    it('外のサイトへの戻り先は使わない', function () {
        $this->get('/rsdt/'.EXISTING_ID.'/edit?back=//evil.example')
            ->assertOk()
            ->assertDontSee('evil.example');
    });

    it('無い行・正しくない id は 404', function () {
        $this->get('/rsdt/'.MISSING_ID.'/edit')->assertNotFound()->assertSee('この住居は見つかりません');
        $this->get('/rsdt/bad/edit')->assertNotFound();
    });
});

describe('更新', function () {
    it('更新して、戻り先へお知らせ付きで redirect する（前後の空白は取り、空文字は NULL）', function () {
        $input = inputOf(findRsdt(EXISTING_ID), ['remarks' => '  テスト  ', 'koaza' => '', 'efct_date' => '2026-01-02']);

        $this->patch('/rsdt/'.EXISTING_ID.'?back=/?page=2', $input)
            ->assertRedirect('/?page=2')
            ->assertSessionHas('notice', '更新しました');
        $after = findRsdt(EXISTING_ID);
        expect($after->remarks)->toBe('テスト')->and($after->koaza)->toBeNull()->and($after->formValue('efct_date'))->toBe('2026-01-02');
    });

    it('入力エラーなら、編集画面に戻してエラーを出す（DB は変えない）', function (array $overrides, string $column, string $message) {
        $before = findRsdt(EXISTING_ID)->getAttributes();
        $edit = '/rsdt/'.EXISTING_ID.'/edit?back=/';

        $this->from($edit)
            ->patch('/rsdt/'.EXISTING_ID.'?back=/', inputOf(findRsdt(EXISTING_ID), $overrides))
            ->assertRedirect($edit)
            ->assertSessionHasErrors([$column => $message]);
        expect(findRsdt(EXISTING_ID)->getAttributes())->toBe($before);
    })->with([
        'city が空' => [['city' => '  '], 'city', '必須です'],
        'city が長すぎる（21 文字）' => [['city' => str_repeat('あ', 21)], 'city', '20 文字以内で入力してください'],
        '無い日付' => [['efct_date' => '2026-02-30'], 'efct_date', '日付の形式が正しくありません（YYYY-MM-DD）'],
        '別の書き方の日付' => [['ablt_date' => '2026/01/01'], 'ablt_date', '日付の形式が正しくありません（YYYY-MM-DD）'],
    ]);

    it('入力エラーで戻った画面に、入力した値とエラーと「入力内容を確認してください」を出す', function () {
        $edit = '/rsdt/'.EXISTING_ID.'/edit?back=/';

        $this->from($edit)
            ->followingRedirects()
            ->patch('/rsdt/'.EXISTING_ID.'?back=/', inputOf(findRsdt(EXISTING_ID), ['city' => '', 'remarks' => '入力した値']))
            ->assertOk()
            ->assertSee('必須です')
            ->assertSee('入力内容を確認してください')
            ->assertSee('name="city" value=""', false)
            ->assertSee('name="remarks" value="入力した値"', false);
    });

    it('削除された行の更新は、お知らせを出して一覧に戻す', function () {
        $this->patch('/rsdt/'.MISSING_ID.'?back=/?page=3', ['city' => '千代田区'])
            ->assertRedirect('/?page=3')
            ->assertSessionHas('alert', 'この行はすでに削除されています');
        expect(Rsdt::query()->count())->toBe(TOTAL_ROWS);
    });
});

describe('削除', function () {
    it('削除して、戻り先へお知らせ付きで redirect する', function () {
        $this->delete('/rsdt/'.EXISTING_ID.'?back=/?page=2')
            ->assertRedirect('/?page=2')
            ->assertSessionHas('notice', '削除しました');
        expect(findRsdt(EXISTING_ID))->toBeNull();
    });

    it('外のサイトへの戻り先は、一覧に変える', function () {
        $this->delete('/rsdt/'.MISSING_ID.'?back=//evil.example')->assertRedirect('/');
    });
});
