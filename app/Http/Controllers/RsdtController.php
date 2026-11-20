<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRsdtRequest;
use App\Models\RsdtListQuery;
use App\UseCases\DeleteRsdt;
use App\UseCases\GetRsdt;
use App\UseCases\SearchRsdt;
use App\UseCases\UpdateRsdt;
use App\UseCases\UpdateRsdtStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 画面からの操作を受け取る
 * ここでやるのは「パラメーターを読む → ユースケースを呼ぶ → 表示・redirect」だけ
 * ユースケースは、メソッドの引数に書くと Laravel が作って渡してくれる（サービスコンテナ）
 */
class RsdtController extends Controller
{
    public function index(Request $request, SearchRsdt $search): View
    {
        $query = RsdtListQuery::fromParams($request->query());
        $result = $search->call($query->criteria, $query->page, $query->perPage);

        return view('rsdts.index', [
            'query' => $query,
            'result' => $result,
            // 編集・削除のあとに戻ってくる URL（今の検索条件とページ）
            'back' => route('rsdts.index', $query->withPage($result->page)->toParams(), false),
        ]);
    }

    public function edit(Request $request, string $id, GetRsdt $get): View|Response
    {
        $rsdt = $get->call($id);
        if (! $rsdt) {
            return response()->view('rsdts.not-found', status: 404);
        }

        return view('rsdts.edit', ['rsdt' => $rsdt, 'back' => $this->safeBack($request->query('back'))]);
    }

    public function update(UpdateRsdtRequest $request, string $id, UpdateRsdt $update): RedirectResponse
    {
        $back = $this->safeBack($request->query('back'));

        return match ($update->call($id, $request->validated())) {
            UpdateRsdtStatus::Updated => redirect($back)->with('notice', '更新しました'),
            UpdateRsdtStatus::NotFound => redirect($back)->with('alert', 'この行はすでに削除されています'),
        };
    }

    public function destroy(Request $request, string $id, DeleteRsdt $delete): RedirectResponse
    {
        $delete->call($id);

        return redirect($this->safeBack($request->query('back')))->with('notice', '削除しました');
    }

    /** 戻り先は、このサイトの中のパスだけを許す（外のサイトへ飛ばされないように） */
    private function safeBack(mixed $back): string
    {
        return is_string($back) && str_starts_with($back, '/') && ! str_starts_with($back, '//')
            ? $back
            : route('rsdts.index', absolute: false);
    }
}
