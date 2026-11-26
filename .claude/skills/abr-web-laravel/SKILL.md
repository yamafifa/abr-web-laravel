---
name: abr-web-laravel
description: abr-web-laravel（住居表示-住居マスターを検索・一覧・編集・削除する PHP Laravel のアプリ）で、機能の追加・修正・テスト・動作確認・ドキュメント更新をするときの手順と、これまでに分かった落とし穴。検索条件や画面の追加、モデル・FormRequest・ユースケース・コントローラーのどこに書くかの判断、composer・npm のパッケージの追加、DB に関わる artisan コマンドを使うときに使う。
---

# abr-web-laravel の作業手順と知見

## 前提

- PHP 8.5.13（phpenv の global）＋ Composer 2.11.2 ＋ Laravel 13.40.0 ＋ Eloquent（MySQL）＋ Blade ＋ Tailwind CSS 4（Vite）＋ Alpine.js 3.17.5 ＋ Pest 5.3.5
- DB は **abr-batch の MySQL を共用**（ポート 3307、テーブル `mt_rsdtdsp_rsdt`、1,752,573 件）。開発とテストは同じ DB
- 勉強用に、同じものを TypeScript（abr-web-ts）・Rails（abr-web-rails）・Symfony（abr-web-symfony）・Spring Boot（abr-web-spring）でも作った。機能・画面・デザイン・ユースケースの名前（`SearchRsdt`・`GetRsdt`・`UpdateRsdt`・`DeleteRsdt`）をそろえる
- サーバーは 3000 番ポート（ほかの版と同じ）。`127.0.0.1` で待ち受ける
- 時刻は日本時間（`config/app.php` の `timezone` が `Asia/Tokyo`）。php.ini の `date.timezone` は UTC のままで、Laravel が起動のときに上書きする

## してはいけないこと（DB を共用しているため）

- **DB を作り直す artisan コマンドを実行しない。** `migrate`・`migrate:*`・`db:wipe`・`db:seed`・`schema:dump` など
  - 2 重に止めてある：[app/Console/DbCommandGuard.php](../../../app/Console/DbCommandGuard.php)（コマンドが始まる前に名前を見て止める）と、`DB::prohibitDestructiveCommands()`（[AppServiceProvider](../../../app/Providers/AppServiceProvider.php)）
  - 止まるかを確かめるために実行してはいけない（止める仕組みが壊れていれば DB が消える）。確かめるときは、コマンドを実行せずに調べる：

```bash
./vendor/bin/pest --filter=DbCommandGuard   # 「コマンドが始まる」イベントだけを送って、止まるかを見る
php artisan tinker --execute 'foreach ([Illuminate\Database\Console\Migrations\FreshCommand::class, Illuminate\Database\Console\WipeCommand::class] as $c) echo class_basename($c), ": ", var_export((new ReflectionProperty($c, "prohibitedFromRunning"))->getValue(), true), "\n";'
# どちらも true なら止まる
```

- テストで **`RefreshDatabase` を使わない**（`migrate:fresh` でテーブルを作り直す）。`DatabaseTransactions` を使う（[tests/Pest.php](../../../tests/Pest.php) で Feature 全体に付けてある）
- マイグレーションファイルを作らない（`make:model` に `-m` を付けない）
- `.env` の `SESSION_DRIVER=file`・`CACHE_STORE=file`・`QUEUE_CONNECTION=sync` を `database` に戻さない（共用 DB にテーブルが要るようになる）
- `phpunit.xml` の DB を SQLite に戻さない。`composer.json` の `setup` に `migrate` を戻さない
- **Laravel Boost を勝手に入れない。** 雛形の `CLAUDE.md`・`AGENTS.md` に「入れる」と書いてあるが、入れるならユーザーの了解を取る

## 書く場所の決まり（MVC ＋ ユースケース）

| 場所 | 書くもの |
|---|---|
| `app/Models/Rsdt.php` | 複合主キー（`KEY_COLUMNS`・`keyFromParam`・`routeId`・`setKeysForSaveQuery`）、17 列の決まり（`ATTRIBUTE_RULES`）、検索（`#[Scope] search`）、住所の表示（`address()`）、フォームに出す値（`formValue()`） |
| `app/Models/RsdtSearchCriteria.php`・`RsdtListQuery.php` | 検索条件と、一覧の URL ⇔ 状態の変換（readonly class） |
| `app/Models/RsdtEsListQuery.php`・`app/Support/RsdtFold.php`・`app/Support/EsClient.php` | Elasticsearch 検索の URL ⇔ 状態の変換、文字をそろえる処理（abr-batch の `fold.ts` と同じ）、REST API の呼び出し（Laravel の HTTP クライアント） |
| `app/Http/Requests/UpdateRsdtRequest.php` | 更新の入力チェック（規則は `Rsdt::ATTRIBUTE_RULES` から作る）とエラーの文 |
| `app/UseCases/` | ユースケース（`call` を持つクラス）。Eloquent のモデルを直接使う（リポジトリのインターフェースは作らない） |
| `app/Http/Controllers/RsdtController.php` | パラメーターを読む → ユースケースを呼ぶ → 表示・redirect だけ。ユースケースはメソッドの引数で受け取る |
| `resources/views/`・`resources/js/app.js` | Blade（画面とコンポーネント）と Alpine.js |

- Rails 版と同じ形だが、入力チェックはモデルではなく FormRequest に書く（Laravel の決まった書き方）
- 前後の空白を取る・空文字 → NULL は、Laravel のミドルウェア（`TrimStrings`・`ConvertEmptyStringsToNull`）がやる。ユースケースを直接呼ぶときのために、`UpdateRsdt` でも空文字は NULL にしている
- 1 ファイルに 1 クラス（PSR-4。ファイル名からクラスを読み込む仕組み）。結果のクラス（`SearchRsdtResult`・`UpdateRsdtStatus`）も別ファイル

### 検索条件を足すとき

1. `app/Models/RsdtSearchCriteria.php` の項目
2. `app/Models/Rsdt.php` の `search`（部分一致は `self::escapeLike` でエスケープ）
3. `app/UseCases/SearchRsdtEs.php` の `WILDCARD_FIELDS`（部分一致）か `EXACT_FIELDS`（完全一致）
4. `app/Models/RsdtListQuery.php` の `PARAM_NAMES`（URL の名前）と `MAX_LENGTH`
5. `resources/views/rsdts/_search-form.blade.php` の `$fields`
6. テスト（`tests/Unit/RsdtListQueryTest.php`・`tests/Feature/SearchRsdtTest.php`・`RsdtControllerTest.php`）と README

部分一致か完全一致かは、値を見てから決める（`blk_num`・`rsdt_num` は全件が半角数字なので完全一致）。

## テストと確認

```bash
./vendor/bin/pest          # 84 件、約 26 秒。Feature は本物の DB と Elasticsearch を使い、1 件ごとにトランザクションで取り消す
./vendor/bin/pint --test   # 指摘 0 件を保つ。直すときは ./vendor/bin/pint
```

- **Pest の出力は JSON**（laravel/pao が、AI 向けに短くまとめた形にする）。失敗の文に画面の HTML 全体が入って長くなるので、`php -r` などで `failures`・`error_details` の `test` と `message` だけを取り出して読む
- テストで使う行は [tests/Pest.php](../../../tests/Pest.php) の定数（`EXISTING_ID`・`MISSING_ID`・`TOTAL_ROWS`）と関数（`inputOf`・`findRsdt`）。元のデータにある行を使う
- テストは「全件 1,752,573 件」を前提にしている。件数が違うと落ちるので、先に DB を元に戻す
- 確認のあとは、DB が元のままか必ず確かめる。変わっていたら abr-batch で `npm run import:v3`（約 15 秒）で戻す

```bash
docker compose -f ../abr-batch/compose.yaml exec -T db mysql -uabr -pabr abr -e 'select count(*) from mt_rsdtdsp_rsdt; checksum table mt_rsdtdsp_rsdt'
# 元のデータ：1752573 件、チェックサム 967527444
```

- 書き換えを試したいときは、tinker で `DB::beginTransaction()` → 試す → `finally { DB::rollBack(); }` にする

### 画面の確認（ブラウザが無い環境）

- 一覧・編集画面は `curl` で HTML を取って、件数や値を `grep` する
- 更新・削除は [scripts/call-action.php](scripts/call-action.php) で送る。編集画面から `_token`（CSRF トークン）と今の値を取り、指定した列だけを変えて送る。redirect 先のお知らせと入力エラーの文も出す

```bash
php .claude/skills/abr-web-laravel/scripts/call-action.php update 131016_0001001_006_002_ '/?page=2' 'city=' 'efct_date=2026-02-30'
# → HTTP 302、編集画面へ redirect、「入力内容を確認してください」と各列のエラー（DB は変わらない）
php .claude/skills/abr-web-laravel/scripts/call-action.php update 131016_0001001_006_999_99999 '/?page=3' 'city=千代田区'
# → 無い行なので「この行はすでに削除されています」（DB は変わらない）
```

- 成功する更新・削除は本物の DB を書き換える。送ったら DB を元に戻す
- 見た目・モーダルの動きは確かめられないので、「確かめていない」とユーザーに伝える

### サーバーの起動と停止

```bash
npm run build                                   # Tailwind のクラスや app.js を変えたら、実行し直す
php artisan serve --host=127.0.0.1 --port=3000
P=$(ss -ltnp | grep ':3000 ' | grep -o 'pid=[0-9]*' | cut -d= -f2); [ -n "$P" ] && kill $(ps -o ppid= -p $P) $P   # 止める
```

- `php artisan serve` は中で `php -S` を動かす。止めるときは両方止める（上のコマンド）
- `pkill -f "artisan serve"` は使わない。そのコマンドを実行しているシェル自身にも当たる（TS 版で、シェルごと止まった）
- 3000 番がほかの版に使われていると「Failed to listen on 127.0.0.1:3000 (reason: Address already in use)」で起動できない。先に `ss -ltn | grep :3000` で確かめる
- PHP・Blade の変更は、サーバーを起動し直さなくても反映される

## 分かっている落とし穴

| 症状 | 原因と対処 |
|---|---|
| 雛形のままだと、共用 DB にテーブルを作る | `composer create-project` の最後の処理（`post-create-project-cmd`）が `migrate` を実行し、セッション・キャッシュ・キューも DB に置く設定。`--no-scripts` で作って `.env` と `key:generate` を手でやり、セッション・キャッシュはファイル、キューは `sync` にした。その処理は `composer.json` から消してある |
| 保存・削除の WHERE が `id` になる | Eloquent は主キーが 1 列の前提。`setKeysForSaveQuery` を上書きして 5 列を入れた。取るときは `where($key)->first()` を使い、`find()` は使わない |
| 削除された行の更新が「成功」になる | Eloquent の `save` は、0 行の更新でも `true` を返す（試して確かめた）。`UpdateRsdt` は `lockForUpdate()` で行をロックしてから更新する |
| `Incorrect date value: '' for column 'ablt_date'` | 空文字は DATE 型の列に入らない。画面からはミドルウェアが NULL にするが、ユースケースを直接呼ぶと空文字のまま。`UpdateRsdt` でも空文字を NULL にした |
| 「%」で検索すると全件が出る | Laravel の `where(..., 'like', ...)` は `%`・`_` をそのまま渡す。`Rsdt::escapeLike` でエスケープする（Laravel には無い） |
| 入力エラーのあと、空にした欄に元の値が出る（ように見える） | 出ない。`old('city', 既定値)` は、送った値が NULL でも NULL を返す（既定値にならない）。テストで確かめてある |
| テストの戻り先 URL が合わない | 戻り先（`/?town=%E4...`）をさらにクエリに入れるので 2 回エンコードされる。期待値も `route()` で作る |
| 削除ボタンを編集フォームの中に置けない | 削除ボタンは自分のフォームを持つので、入れ子になる。編集フォームの外に置いた |
| 確認モーダルが出ずに送信される | `x-data="confirm"` と `@submit="open"` を、フォームを囲む要素に付ける。`<x-confirm-dialog>` はその中に置く（`x-ref="dialog"` を使うため） |
| curl で更新・削除を送ると 419 | CSRF トークンが無いため。`call-action.php` で送る |
| 画面の見た目が崩れる（CSS・JS が読めない） | `npm run dev` を止めたあとに `public/hot` が残ると、開発サーバーから読もうとする（Laravel の Vite の仕組み。試していない）。`public/hot` を消すか、`npm run build` する |

## データの性質

- 主キーの URL の形は、5 列を `_` でつないだもの（例：`131016_0001001_006_001_`）。`rsdt2_id` が空なので末尾が `_`。`Rsdt::keyFromParam` で元に戻す
- 主キー順で全件の先頭の行は `131016_0001001_006_001_`（`EXISTING_ID`）
- `chome` は「一丁目」と「３丁目」が混ざっている。照合順序で全角と半角は同じになるが、漢数字は別
- `ward` は全件 NULL
- 部分一致はインデックスが効かず、件数を数えるのに全件を読む（約 1 秒）

## 書いてはいけないこと

- **列の日本語名を書かない。** 公式の仕様書で確かめられていないので、画面の項目名は CSV の列名のまま。ユーザーが言った呼び方（「市区町村」「町字」「丁目」）は使ってよい
- 実際に測っていない数字や、試していない動きを、確かめたこととして書かない

## パッケージの追加

- 何を入れるか伝えて、了解を取ってから入れる
- composer は `composer require <pkg>:<版>` で版を固定する。npm は `npm install -E <pkg>@<版>`
- できれば出てから 2 週間以上たった版にする。npm は `--before=<日付>` で、その日より前に出た版から選べる

## ドキュメントとコミット

- README は入口（できること・動かし方・構成・テスト・作ってみて分かったこと）。起動と停止は `docs/start-stop.md`
- 仕様を変えたら、README の「できること」「作ってみて分かったこと」も直す。ほかの版とそろえる機能なら、違いも書く
- コミットと push は、ユーザーの了解を取ってから。メッセージは日本語で、最後に `Co-Authored-By` の行を付ける
- GitHub は**公開**リポジトリ（TS 版・Rails 版も公開）。abr-batch 側からは触れない
- `vendor/`・`node_modules/`・`public/build/`・`.env`・`storage` の中のログなどは Git に入れない（`.gitignore` 済み）。コミットの前に、個人情報（本名・メールアドレスなど）が入っていないか確かめる
