# abr-web-laravel

ABR（アドレス・ベース・レジストリ）の住居表示-住居マスター（`mt_rsdtdsp_rsdt`、東京都 1,752,573 件）を検索して、一覧表示・編集・削除する Web アプリです。
勉強用に、同じものを TypeScript・Ruby on Rails・PHP Laravel で作るうちの PHP Laravel 版です。

- PHP 8.4 ＋ Laravel 13.40.0 ＋ Eloquent ＋ Blade ＋ Tailwind CSS 4 ＋ Alpine.js ＋ Pest
- 構成は「Laravel 標準の MVC ＋ ユースケース」（`app/UseCases/`）。Rails 版と同じ形です
- DB は [abr-batch](https://github.com/yamafifa/abr-batch) の MySQL を共用します（テーブルも abr-batch で作ったもの）

## できること

TS 版・Rails 版と同じです。

- **検索**：市区町村（`city`・`ward`）、町字（`oaza_cho`・`koaza`）、丁目（`chome`）の部分一致と、`blk_num`・`rsdt_num` の完全一致。条件は URL に残ります
- **一覧**：主キー順。1 ページ 15 件（30・50・100 件も選べます）。ページャー付き
- **編集**：主キー以外の 17 列。「更新」を押すと、変わった項目をモーダルで見せてから保存します
- **削除**：一覧と編集画面から。モーダルで確認してから削除します

## 動かし方

1. abr-batch で DB を起動して、データを入れておく（abr-batch の README「動かし方」を参照）
2. このリポジトリで：

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=3000   # http://127.0.0.1:3000
```

- 起動と停止の詳しいやり方は [docs/start-stop.md](docs/start-stop.md) にあります
- 編集・削除したデータは、abr-batch で `npm run import:v3`（約 15 秒）を実行すると元に戻せます
- **`php artisan migrate` などの DB のコマンドは使えません。** DB を abr-batch と共用しているので、[app/Console/DbCommandGuard.php](app/Console/DbCommandGuard.php) と Laravel の `DB::prohibitDestructiveCommands()` で止めています（`migrate:fresh`・`db:wipe` で abr の DB のテーブルが消える、などを防ぐため）

## 構成

| 場所 | 内容 |
|---|---|
| `app/Models/Rsdt.php` | Eloquent のモデル。複合主キー、17 列の決まり、検索のスコープ、住所の表示 |
| `app/Models/RsdtSearchCriteria.php` | 検索条件（readonly class） |
| `app/Models/RsdtListQuery.php` | 一覧の URL ⇔ 検索条件・件数・ページ |
| `app/Http/Requests/UpdateRsdtRequest.php` | 更新の入力チェック（FormRequest） |
| `app/UseCases/` | ユースケース：`SearchRsdt`（ページ番号の補正もここ）・`GetRsdt`・`UpdateRsdt`・`DeleteRsdt` |
| `app/Http/Controllers/RsdtController.php` | パラメーターを読む → ユースケースを呼ぶ → 表示・redirect。戻り先のチェックもここ |
| `app/Support/Pager.php` | ページャーに出すページ番号 |
| `resources/views/rsdts/` | 一覧・編集・検索フォーム・404 |
| `resources/views/components/` | Blade のコンポーネント：レイアウト・確認モーダル・削除ボタン・ページャー・お知らせ |
| `resources/js/app.js` | Alpine.js：確認モーダル（`confirm`） |
| `app/Console/DbCommandGuard.php` | DB を作り直すコマンドを止める |

Rails 版との違い：

- 入力チェックはモデルではなく FormRequest に書きます（Laravel の決まった書き方）。チェックに通らないと、Laravel が編集画面に redirect して、入力した値とエラーを渡します。そのため `UpdateRsdt` は「入力エラー」を返しません
- 前後の空白を取る・空文字を NULL にするのは、Laravel が最初から入れているミドルウェア（`TrimStrings`・`ConvertEmptyStringsToNull`）がやります
- 画面の動きは Stimulus ではなく Alpine.js で書きます。Turbo が無いので、redirect は Laravel の既定の 302 のままです

## テスト

```bash
./vendor/bin/pest        # 65 件、約 25 秒
./vendor/bin/pint --test # 書き方のチェック（直すときは ./vendor/bin/pint）
```

- `tests/Unit` は Laravel を起動しないテスト、`tests/Feature` は本物の DB を使うテストです
- Feature のテストは 1 件ごとにトランザクションで取り消す（`DatabaseTransactions`）ので、DB の中身は変わりません
- テストも開発も同じ DB を使います（[phpunit.xml](phpunit.xml)）

## 作ってみて分かったこと

- **Eloquent は複合主キーに対応していない**：主キーは 1 列の前提なので、保存・削除の WHERE を作る `setKeysForSaveQuery` を上書きして、5 列を入れました。URL の id（`131016_0001001_006_001_`）との変換も自分で書きました（Rails 8 は複合主キーに対応している）
- **LIKE のエスケープは Laravel に無い**：`%`・`_`・`\` のエスケープを自分で書きました（Rails には `sanitize_sql_like` がある）
- **日付のチェック**：`date_format:Y-m-d` は「2026-02-30」のような無い日付も、「2026/01/01」のような別の書き方も通しません。Rails 版のように、型変換の前の値を自分で検査する必要はありませんでした
- **Eloquent の `save` は、行が消えていても成功扱いになる**：読んだあとにほかで削除されると、0 行の更新でも `true` が返りました（試して確かめた）。更新は `lockForUpdate`（SELECT ... FOR UPDATE）で行をロックしてから行います
- **空文字は DATE 型の列に入らない**：画面から来るときはミドルウェアが空文字を NULL にしますが、ユースケースを直接呼ぶと空文字のまま届いて、MySQL がエラーにしました。ユースケースでも空文字を NULL にしています
- **新しく作ると、DB にテーブルを作る設定になっている**：雛形はセッション・キャッシュ・キューを DB に置き、`composer create-project` の最後に `migrate` を実行します。共用 DB にテーブルを作らないように、`--no-scripts` で作り、セッションとキャッシュはファイル、キューは `sync` にして、雛形のマイグレーションは消しました
- **テストの雛形は SQLite のメモリ DB を使う設定**：`phpunit.xml` を MySQL に変えました。テーブルを作り直す `RefreshDatabase` は使いません
- **戻り先の URL は 2 回エンコードされる**：一覧の URL（`/?town=%E4%B8%8A...`）を編集画面の URL のクエリに入れるので、`%` がさらに `%25` になります。ブラウザで開くと 1 回分が戻るので、正しく戻れます
- **項目名**：画面の項目名は CSV の列名のままです（日本語名の公式資料を確かめられていないため）
- **データの性質**（TS 版・Rails 版と同じ）：丁目は「一丁目」と「３丁目」の書き方が混ざっていて、「3」で「３丁目」は見つかりますが、「三丁目」は見つかりません
