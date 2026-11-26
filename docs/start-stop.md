[← README に戻る](../README.md)

# サーバーの起動と停止

## 起動

先に Docker を起動しておきます。

### 1. DB を起動する（abr-batch のフォルダで）

```bash
cd ~/work/abr-batch
npm run db:up
```

すでに DB が動いていれば、この手順は不要です。Elasticsearch と Kibana も一緒に起動します。

Elasticsearch 検索（`/es`）を使うときは、インデックスを作っておきます（約 1 分）。DB の中身を変えたあとは、もう一度実行すると Elasticsearch に反映されます。

```bash
npm run es:index
```

### 2. アプリを起動する（abr-web-laravel のフォルダで）

```bash
cd ~/work/abr-web-laravel
npm run build
php artisan serve --host=127.0.0.1 --port=3000
```

ブラウザで http://127.0.0.1:3000 を開きます。

- PHP・Blade のコードを保存すると、ブラウザを再読み込みするだけで反映されます
- **画面に新しい Tailwind のクラスを書いたときや、`resources/js/app.js` を変えたとき**は、`npm run build` をもう一度実行します
  - 別のターミナルで `npm run dev`（Vite の開発サーバー）を動かしておくと、保存するたびに反映されます。止めたら、`public/hot` が残っていないか確かめます（残っていると、画面が CSS・JS を開発サーバーから読もうとして、見た目が崩れます）
- `composer dev` は、サーバーと Vite などをまとめて動かすコマンドですが、このリポジトリでは使っていません（試していません）

## 停止

**自分のターミナルで起動した場合**：そのターミナルで **Ctrl + C** を押します。

**バックグラウンドで動いているものや、どこで動いているか分からない場合**：

```bash
P=$(ss -ltnp | grep ':3000 ' | grep -o 'pid=[0-9]*' | cut -d= -f2); [ -n "$P" ] && kill $(ps -o ppid= -p $P) $P
```

`php artisan serve` は、中で `php -S`（PHP に付いている簡単な Web サーバー）を動かしています。3000 番を使っている `php -S` と、その親の `php artisan serve` の両方を止めます。

止まったかどうかは、次のコマンドで確かめます。何も表示されなければ止まっています。

```bash
ss -ltn | grep :3000
```

**DB も止めたい場合**（abr-batch のフォルダで）：

```bash
npm run db:down
```

DB を止めてもデータは消えません。

## 注意

- ほかの版（TypeScript・Rails・Symfony・Spring Boot）も 3000 番ポートを使います。ほかの版が動いていると「Failed to listen on 127.0.0.1:3000 (reason: Address already in use)」で起動できないので、先に止めてください（`--port` を付けないときは、空いている別のポートを探す作りになっています。こちらは試していません）
- DB が止まっていると、画面を開いたときに接続エラーになります
