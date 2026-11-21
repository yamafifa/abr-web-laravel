<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Elasticsearch の REST API を呼ぶ（専用のパッケージは入れず、Laravel の HTTP クライアントを使う）
 * 接続先は .env の ES_URL。無ければ abr-batch の compose.yaml の http://127.0.0.1:9200
 * 接続できない・エラーが返ったときは EsException（404 のときは $notFound が true）
 */
class EsClient
{
    private string $url;

    public function __construct(?string $url = null)
    {
        $this->url = rtrim($url ?? (string) env('ES_URL', 'http://127.0.0.1:9200'), '/');
    }

    public function post(string $path, array $body): array
    {
        return $this->send('post', $path, $body);
    }

    public function get(string $path): array
    {
        return $this->send('get', $path);
    }

    private function send(string $method, string $path, ?array $body = null): array
    {
        try {
            $req = Http::connectTimeout(2)->timeout(10)->acceptJson();
            $res = $method === 'post' ? $req->post($this->url.$path, $body) : $req->get($this->url.$path);
        } catch (ConnectionException $e) {
            throw new EsException("Elasticsearch に接続できません（{$this->url}）: {$e->getMessage()}");
        }
        if ($res->status() === 404) {
            throw new EsException("Elasticsearch が 404 を返しました（{$path}）", notFound: true);
        }
        if (! $res->successful()) {
            throw new EsException("Elasticsearch が {$res->status()} を返しました: ".mb_substr($res->body(), 0, 300));
        }

        return $res->json();
    }
}
