<?php

use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

// コマンドそのものは実行しない（止める仕組みが壊れていたら DB が消えるため）。
// 「コマンドが始まる」というイベントだけを送って、止まるかを見る
function startCommand(string $name): void
{
    event(new CommandStarting($name, new ArrayInput([]), new NullOutput));
}

it('DB を作り直すコマンドは、始まる前に止める', function (string $name) {
    expect(fn () => startCommand($name))->toThrow(RuntimeException::class, "{$name} は実行できません");
})->with(['migrate', 'migrate:fresh', 'db:wipe', 'db:seed', 'schema:dump']);

it('ほかのコマンドは止めない', function () {
    startCommand('route:list');

    expect(true)->toBeTrue();
});
