<?php

/**
 * 動いているサーバー（http://127.0.0.1:3000）に、更新・削除を送って結果を見る（ブラウザが無い環境での確認用）
 *
 * 使い方:
 *   php call-action.php update <id> <back> [列=値 ...]   今の値に、指定した列だけを変えて送る
 *   php call-action.php delete <id> <back>
 *
 * - CSRF トークン（フォームの _token）とセッションの Cookie を、先に編集画面（無ければ一覧）から取る
 * - update は、編集画面の今の値を全部送る（指定しなかった列が NULL にならないように）
 * - redirect されたら、その画面を開いて、お知らせと入力エラーの文を出す
 * - 本物の DB を書き換える。確認のあとは、DB を元に戻すこと（SKILL.md「テストと確認」）
 */
const BASE = 'http://127.0.0.1:3000';

[$_, $action, $id, $back] = $argv + [null, null, null, null];
if (! in_array($action, ['update', 'delete'], true) || ! $id || $back === null) {
    fwrite(STDERR, "使い方: php call-action.php update|delete <id> <back> [列=値 ...]\n");
    exit(2);
}

$jar = tempnam(sys_get_temp_dir(), 'abr-cookie-');
register_shutdown_function(fn () => @unlink($jar));

function request(string $method, string $url, string $jar, ?array $form = null, ?string $referer = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HEADER => false,
    ]);
    if ($form !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form));
    }
    if ($referer) {
        curl_setopt($ch, CURLOPT_REFERER, $referer);
    }
    $body = curl_exec($ch);
    if ($body === false) {
        fwrite(STDERR, '接続できません: '.curl_error($ch)."\nサーバーが動いているか確かめてください（php artisan serve --host=127.0.0.1 --port=3000）\n");
        exit(1);
    }
    $info = curl_getinfo($ch);

    return [$info['http_code'], $info['redirect_url'], $body];
}

function decode(string $s): string
{
    return html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// 編集画面を開いて、_token と今の値を取る（行が無ければ一覧から _token を取る）
$editUrl = BASE.'/rsdt/'.rawurlencode($id).'/edit?back='.rawurlencode($back);
[$code, , $html] = request('GET', $editUrl, $jar);
if ($code !== 200) {
    echo "編集画面: HTTP {$code}（行が無いので、一覧から _token を取ります）\n";
    [, , $html] = request('GET', BASE.'/', $jar);
}
if (! preg_match('/name="_token" value="([^"]+)"/', $html, $m)) {
    fwrite(STDERR, "_token が見つかりません\n");
    exit(1);
}
$form = ['_token' => decode($m[1]), '_method' => $action === 'update' ? 'PATCH' : 'DELETE'];

if ($action === 'update') {
    // 編集画面の入力欄（data-column の付いたもの）の今の値
    preg_match_all('/name="([a-z0-9_]+)" value="([^"]*)"\s+data-column=/', $html, $fields, PREG_SET_ORDER);
    foreach ($fields as [, $name, $value]) {
        $form[$name] = decode($value);
    }
    foreach (array_slice($argv, 4) as $pair) {
        [$name, $value] = explode('=', $pair, 2) + [1 => ''];
        $form[$name] = $value;
    }
}

[$code, $location] = request('POST', BASE.'/rsdt/'.rawurlencode($id).'?back='.rawurlencode($back), $jar, $form, $editUrl);
echo "HTTP {$code}\n";
if ($location) {
    echo "redirect: {$location}\n";
    // redirect 先を開いて、お知らせ（flash）と入力エラーの文を出す
    [, , $next] = request('GET', $location, $jar);
    if (preg_match_all('/<div x-data="\{ show: true \}".*?<\/span>\s*(.*?)\s*<\/div>/s', $next, $flash)) {
        foreach ($flash[1] as $f) {
            echo 'お知らせ: '.decode(trim($f))."\n";
        }
    }
    if (preg_match_all('/name="([a-z0-9_]+)"[^>]*aria-invalid="true".*?<span class="mt-1 block text-xs text-rose-400">(.*?)<\/span>/s', $next, $errors, PREG_SET_ORDER)) {
        foreach ($errors as [, $name, $msg]) {
            echo "エラー: {$name}: ".decode($msg)."\n";
        }
    }
}
