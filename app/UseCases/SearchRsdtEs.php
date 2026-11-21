<?php

namespace App\UseCases;

use App\Models\Rsdt;
use App\Models\RsdtSearchCriteria;
use App\Support\EsClient;
use App\Support\EsException;
use App\Support\RsdtFold;
use InvalidArgumentException;

/**
 * Elasticsearch（abr-batch の es:index で作った別名 rsdt）で住居を検索して、1 ページ分を返す
 * - 条件の意味は DB 検索（Rsdt の search）と同じ。部分一致は wildcard、完全一致は term で探す
 * - 文字は RsdtFold でそろえてから探す（MySQL の utf8mb4_unicode_ci と同じように見つけるため）
 * - 並びは主キー順。ページは search_after で送る（Elasticsearch は先頭から 10,000 件より先へ from で進めないため）
 */
class SearchRsdtEs
{
    public const ALIAS = 'rsdt';

    /** 検索条件の項目 → 部分一致で探す列（どれかに当たればよい） */
    private const WILDCARD_FIELDS = ['city' => ['city', 'ward'], 'town' => ['oaza_cho', 'koaza'], 'chome' => ['chome']];

    /** 検索条件の項目 → 完全一致で探す列 */
    private const EXACT_FIELDS = ['blkNum' => 'blk_num', 'rsdtNum' => 'rsdt_num'];

    public function __construct(private EsClient $client) {}

    public function call(RsdtSearchCriteria $criteria, int $perPage, ?string $after = null, ?string $before = null, bool $last = false): SearchRsdtEsResult
    {
        if ($perPage <= 0) {
            throw new InvalidArgumentException("perPage が正しくありません: {$perPage}");
        }

        try {
            $filters = $this->filters($criteria);
            [$hits, $total, $index] = $this->fetch($filters, $perPage, $after, $before, $last);
            // 「前へ」で 1 ページに満たなかったら、先頭まで来ているので、先頭の 1 ページを出す
            if ($before !== null && count($hits) < $perPage) {
                [$hits, $total, $index] = $this->fetch($filters, $perPage);
            }

            $firstKey = $hits[0]['_source']['key'] ?? null;
            // このページより前に何件あるか（何件目からかを出すため）
            $beforeCount = $firstKey !== null ? $this->count([...$filters, ['range' => ['key' => ['lt' => $firstKey]]]]) : 0;

            return new SearchRsdtEsResult(
                rows: collect($hits)->map(fn (array $h) => $this->toRsdt($h['_source'])),
                total: $total,
                from: $hits === [] ? 0 : $beforeCount + 1,
                prevKey: $beforeCount > 0 ? $firstKey : null,
                nextKey: $beforeCount + count($hits) < $total ? end($hits)['_source']['key'] : null,
                index: $index ?? $this->aliasTarget(),
            );
        } catch (EsException $e) {
            return new SearchRsdtEsResult(collect(), error: $e->notFound
                ? 'Elasticsearch にインデックス '.self::ALIAS.' がありません。abr-batch で npm run es:index を実行してください'
                : $e->getMessage());
        }
    }

    private function filters(RsdtSearchCriteria $c): array
    {
        $filters = [];
        foreach (self::WILDCARD_FIELDS as $prop => $cols) {
            if ($c->{$prop} === '') {
                continue;
            }
            $pattern = '*'.addcslashes(RsdtFold::call($c->{$prop}), '\\*?').'*'; // * ? \ は、ただの文字として探す
            $filters[] = ['bool' => [
                'should' => array_map(fn ($col) => ['wildcard' => ["fold.{$col}" => ['value' => $pattern]]], $cols),
                'minimum_should_match' => 1,
            ]];
        }
        foreach (self::EXACT_FIELDS as $prop => $col) {
            if ($c->{$prop} !== '') {
                $filters[] = ['term' => ["fold.{$col}" => RsdtFold::call($c->{$prop})]];
            }
        }

        return $filters;
    }

    /** 1 ページ分を取る。逆順で取ったときは、主キー順に並べ直して返す */
    private function fetch(array $filters, int $perPage, ?string $after = null, ?string $before = null, bool $last = false): array
    {
        $desc = $before !== null || $last;
        $body = [
            'track_total_hits' => true,
            'size' => $perPage,
            'sort' => [['key' => $desc ? 'desc' : 'asc']],
            'query' => ['bool' => ['filter' => $filters]],
        ];
        if (($after ?? $before) !== null) {
            $body['search_after'] = [$after ?? $before];
        }
        $res = $this->client->post('/'.self::ALIAS.'/_search', $body);
        $hits = $res['hits']['hits'];

        return [$desc ? array_reverse($hits) : $hits, $res['hits']['total']['value'], $hits[0]['_index'] ?? null];
    }

    private function count(array $filters): int
    {
        return $this->client->post('/'.self::ALIAS.'/_count', ['query' => ['bool' => ['filter' => $filters]]])['count'];
    }

    private function aliasTarget(): ?string
    {
        return array_key_first($this->client->get('/_alias/'.self::ALIAS));
    }

    /** 画面の部品（住所の表示・編集のリンクなど）を DB 検索と同じにするため、Rsdt にする（DB には読みに行かない） */
    private function toRsdt(array $source): Rsdt
    {
        $columns = [...Rsdt::KEY_COLUMNS, ...Rsdt::editableAttributes()];

        return (new Rsdt)->newFromBuilder(array_intersect_key($source, array_flip($columns)));
    }
}
