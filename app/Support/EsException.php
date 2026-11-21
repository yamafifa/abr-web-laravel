<?php

namespace App\Support;

use RuntimeException;

/** Elasticsearch に接続できない・エラーが返ったとき。notFound は 404（インデックスが無いなど） */
class EsException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $notFound = false)
    {
        parent::__construct($message);
    }
}
