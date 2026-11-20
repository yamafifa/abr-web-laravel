<?php

namespace App\Console;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DB を作り直す artisan コマンドを止める
 *
 * DB は abr-batch の MySQL を共用しているので、マイグレーション・シード・全テーブル削除などを実行させない。
 * コマンドが始まる前（CommandStarting）に名前を見て、例外を投げて止める。
 */
class DbCommandGuard
{
    /** 止めるコマンド（* はワイルドカード） */
    public const BLOCKED = [
        'migrate',
        'migrate:*',
        'db:wipe',
        'db:seed',
        'schema:dump',
    ];

    public static function blocks(string $command): bool
    {
        return Str::is(self::BLOCKED, $command);
    }

    public function handle(CommandStarting $event): void
    {
        if (self::blocks((string) $event->command)) {
            throw new RuntimeException(
                "{$event->command} は実行できません（DB は abr-batch と共用しているため。DB を戻すときは abr-batch で npm run import:v3）"
            );
        }
    }
}
