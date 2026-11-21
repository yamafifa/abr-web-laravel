<?php

namespace App\Http\Controllers;

use App\Models\RsdtEsListQuery;
use App\UseCases\SearchRsdtEs;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Elasticsearch で検索する一覧
 * 編集・削除は DB 検索と同じ画面（RsdtController）を使い、戻り先をこの画面にする
 */
class EsRsdtController extends Controller
{
    public function index(Request $request, SearchRsdtEs $search): View
    {
        $q = RsdtEsListQuery::fromParams($request->query());
        $result = $search->call($q->criteria, $q->perPage, $q->after, $q->before, $q->last);

        return view('es.index', [
            'query' => $q,
            'result' => $result,
            // 編集・削除のあとに戻ってくる URL（今の URL。同じ after・before なら同じページが出る）
            'back' => route('rsdts.es', $q->toParams(), false),
        ]);
    }
}
