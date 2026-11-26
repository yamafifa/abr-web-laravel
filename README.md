# abr-web-laravel

ABR（アドレス・ベース・レジストリ）の住居表示-住居マスター（`mt_rsdtdsp_rsdt`、東京都 1,752,573 件）を検索して、一覧表示・編集・削除する Web アプリの PHP Laravel 版です。
[abr-batch](https://github.com/yamafifa/abr-batch) で確かめた「大量のデータを部分一致で探すなら、元の DB ではなく、検索用のデータを作る」という考え方の効果を、DB 検索と Elasticsearch 検索の画面で比べられるようにしています。

- PHP 8.5 ＋ Laravel 13.40.0 ＋ Eloquent ＋ Blade ＋ Tailwind CSS 4 ＋ Alpine.js ＋ Pest
- 構成は「Laravel 標準の MVC ＋ ユースケース」（`app/UseCases/`）
- DB は [abr-batch](https://github.com/yamafifa/abr-batch) の MySQL を共用します（テーブルも abr-batch で作ったもの）
- ほかの版：[TypeScript（Next.js）](https://github.com/yamafifa/abr-web-ts)・[Ruby on Rails](https://github.com/yamafifa/abr-web-rails)・[Symfony](https://github.com/yamafifa/abr-web-symfony)・[Spring Boot](https://github.com/yamafifa/abr-web-spring)

## 検索の速さ：DB 検索と Elasticsearch 検索

本番モードで画面全体を返すまでの時間（2026-11-23 に、3 回測った値）：

| 条件 | DB 検索（`/`） | Elasticsearch 検索（`/es`） |
|---|---|---|
| 市区町村「町田」＋町字「木曽」 | 2.04〜2.10 秒 | 0.026〜0.027 秒 |
| 町字「西」 | 0.82〜0.83 秒 | 0.029〜0.032 秒 |
| 条件なし | 0.27 秒 | 0.023〜0.024 秒 |

- 本番モードは、`APP_ENV=production APP_DEBUG=false php artisan optimize`（設定・ルート・画面を前もってまとめる）のあと、OPcache（PHP のコードを変換した結果をメモリに取っておく仕組み）を有効にした PHP の簡易サーバーで動かしました（`public` で `php -d opcache.enable_cli=1 -S 127.0.0.1:3002 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`）。測り終えたら `php artisan optimize:clear` で開発モードに戻します
- 開発モード（`php artisan serve`）では、Elasticsearch 検索が 0.031〜0.041 秒でした
- DB 検索は `city LIKE '%町田%'` のような部分一致です。前に `%` が付く LIKE は MySQL のインデックスが効かないので、毎回約 175 万行をすべて読みます
- Elasticsearch 検索は、abr-batch の `npm run es:index` で作った検索用のインデックスから探します。条件の意味と画面は DB 検索と同じです
- 詳しくは下の「[Elasticsearch 検索](#elasticsearch-検索)」を参照

## できること

- **検索**：市区町村・町字・丁目などで探して、一覧を出します。DB 検索（`/`）と Elasticsearch 検索（`/es`）の 2 つがあり、条件と画面は同じです
- **編集・削除**：abr-batch の差分更新バッチ（v4）のテストデータを作りやすくするために付けました

## 動かし方

1. abr-batch で DB を起動して、データを入れておく（abr-batch の README「動かし方」を参照）
2. Elasticsearch 検索を使うときは、abr-batch で `npm run es:index` を実行しておく（約 1 分）
3. このリポジトリで：

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

## Elasticsearch 検索

DB 検索（`/`）はそのまま残して、Elasticsearch で探す一覧（`/es`）を別に作りました。上のメニューで切り替えられます。それぞれの画面には、もう一方で同じ条件を検索するリンク（「同じ条件で DB 検索」「同じ条件で Elasticsearch 検索」）があります。

- **中身は `npm run es:index` を実行した時点の DB のコピー**です。画面に作成日時を出しています。アプリで編集・削除すると DB は変わりますが、Elasticsearch の中身は次に `es:index` を実行するまで古いままです。結果が食い違ったときは、DB 検索の画面と見比べてください
- **ページ送りは「最初へ・前へ・次へ・最後へ」**で、ページ番号はありません。Elasticsearch は、先頭から 10,000 件より先へページ番号で進めないため、前のページの最後の行の主キーを渡して、その続きから取る方法（search_after）にしました
- **文字の扱いは MySQL に合わせています**：MySQL（utf8mb4_unicode_ci）は全角と半角、ひらがなとカタカナ、濁点の有無などを同じ文字として扱います。Elasticsearch でも同じ結果になるよう、入れる側（abr-batch の `src/lib/fold.ts`）と探す側（[app/Support/RsdtFold.php](app/Support/RsdtFold.php)）で同じように文字をそろえています。DB にある値すべて（1,159 種類）で、abr-batch と結果が同じになることを確かめました。直すときは abr-batch と、ほかの版の同じ処理もすべて直します
- Elasticsearch には専用のパッケージを使わず、Laravel の HTTP クライアントでつなぎます（[app/Support/EsClient.php](app/Support/EsClient.php)）。接続先は `.env` の `ES_URL` です（書かなければ `http://127.0.0.1:9200`）。接続できないときは、画面にエラーメッセージを出します

## 構成

| 場所 | 内容 |
|---|---|
| `app/Models/Rsdt.php` | Eloquent のモデル。複合主キー、17 列の決まり、検索のスコープ、住所の表示 |
| `app/Models/RsdtSearchCriteria.php` | 検索条件（readonly class） |
| `app/Models/RsdtListQuery.php` | 一覧の URL ⇔ 検索条件・件数・ページ |
| `app/Models/RsdtEsListQuery.php` | Elasticsearch 検索の URL ⇔ 検索条件・件数・after・before・last |
| `app/Http/Requests/UpdateRsdtRequest.php` | 更新の入力チェック（FormRequest） |
| `app/UseCases/` | ユースケース：`SearchRsdt`（ページ番号の補正もここ）・`SearchRsdtEs`（Elasticsearch で検索）・`GetRsdt`・`UpdateRsdt`・`DeleteRsdt` |
| `app/Http/Controllers/RsdtController.php` | パラメーターを読む → ユースケースを呼ぶ → 表示・redirect。戻り先のチェックもここ |
| `app/Http/Controllers/EsRsdtController.php` | Elasticsearch 検索の一覧。編集・削除は `RsdtController` の画面を使う |
| `app/Support/Pager.php` | ページャーに出すページ番号 |
| `app/Support/RsdtFold.php` | Elasticsearch で探す前に、文字をそろえる（MySQL と同じ結果にするため） |
| `app/Support/EsClient.php` | Elasticsearch の REST API を呼ぶ |
| `resources/views/rsdts/` | 一覧・一覧の表・編集・検索フォーム・404 |
| `resources/views/es/` | Elasticsearch 検索の一覧 |
| `resources/views/components/` | Blade のコンポーネント：レイアウト・確認モーダル・削除ボタン・ページャー・Elasticsearch 用のページ送り・お知らせ |
| `resources/js/app.js` | Alpine.js：確認モーダル（`confirm`） |
| `app/Console/DbCommandGuard.php` | DB を作り直すコマンドを止める |

[Rails 版](https://github.com/yamafifa/abr-web-rails)との違い：

- 入力チェックはモデルではなく FormRequest に書きます（Laravel の決まった書き方）。チェックに通らないと、Laravel が編集画面に redirect して、入力した値とエラーを渡します。そのため `UpdateRsdt` は「入力エラー」を返しません
- 前後の空白を取る・空文字を NULL にするのは、Laravel が最初から入れているミドルウェア（`TrimStrings`・`ConvertEmptyStringsToNull`）がやります
- 画面の動きは Stimulus ではなく Alpine.js で書きます。Turbo が無いので、redirect は Laravel の既定の 302 のままです

## テスト

```bash
./vendor/bin/pest        # 84 件、約 26 秒
./vendor/bin/pint --test # 書き方のチェック（直すときは ./vendor/bin/pint）
```

- `tests/Unit` は Laravel を起動しないテスト、`tests/Feature` は本物の DB を使うテストです
- Feature のテストは 1 件ごとにトランザクションで取り消す（`DatabaseTransactions`）ので、DB の中身は変わりません
- テストも開発も同じ DB を使います（[phpunit.xml](phpunit.xml)）
- Elasticsearch 検索のテストは、本物の Elasticsearch を使います。先に abr-batch で `npm run es:index` を実行しておきます

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
- **データの性質**：丁目は「一丁目」と「３丁目」の書き方が混ざっていて、「3」で「３丁目」は見つかりますが、「三丁目」は見つかりません
- **時刻は日本時間**：`config/app.php` の `timezone` を `Asia/Tokyo` にしました（Laravel の既定は UTC。Laravel は起動のときにこの値で php.ini の設定を上書きする）。DB の日付の列は DATE 型（時刻を持たない）なので、今は時刻で結果が変わる処理はありません。DB（MySQL）の時刻は UTC のままです
