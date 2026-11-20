<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * 住居表示-住居マスター（mt_rsdtdsp_rsdt）の 1 行
 *
 * テーブルは abr-batch で作ったものを共用する。主キーは 5 列の複合キー。
 * Eloquent は複合主キーに対応していないので、保存・削除のときの WHERE を自分で組み立てる（setKeysForSaveQuery）。
 */
class Rsdt extends Model
{
    protected $table = 'mt_rsdtdsp_rsdt';

    public $timestamps = false;

    public $incrementing = false;

    /** 主キーの 5 列 */
    public const KEY_COLUMNS = ['lg_code', 'machiaza_id', 'blk_id', 'rsdt_id', 'rsdt2_id'];

    /**
     * 主キー以外の 17 列の決まり（最大文字数はテーブル定義に合わせる）。画面の項目名は列名のまま
     */
    public const ATTRIBUTE_RULES = [
        ['name' => 'city', 'max' => 20, 'required' => true],
        ['name' => 'ward', 'max' => 20],
        ['name' => 'oaza_cho', 'max' => 80],
        ['name' => 'chome', 'max' => 20],
        ['name' => 'koaza', 'max' => 80],
        ['name' => 'machiaza_dist', 'max' => 10],
        ['name' => 'blk_num', 'max' => 20],
        ['name' => 'rsdt_num', 'max' => 20],
        ['name' => 'rsdt_num2', 'max' => 20],
        ['name' => 'basic_rsdt_div', 'max' => 1],
        ['name' => 'rsdt_addr_flg', 'max' => 1],
        ['name' => 'rsdt_addr_mtd_code', 'max' => 1],
        ['name' => 'status_flg', 'max' => 1],
        ['name' => 'efct_date', 'type' => 'date'],
        ['name' => 'ablt_date', 'type' => 'date'],
        ['name' => 'src_code', 'max' => 2],
        ['name' => 'remarks', 'max' => 255],
    ];

    protected function casts(): array
    {
        return [
            'efct_date' => 'immutable_date',
            'ablt_date' => 'immutable_date',
        ];
    }

    /** 編集できる列（主キー以外の 17 列） */
    public static function editableAttributes(): array
    {
        return array_column(self::ATTRIBUTE_RULES, 'name');
    }

    public function getFillable(): array
    {
        return self::editableAttributes();
    }

    /**
     * URL の id（主キーを "_" でつないだもの。例：131016_0001001_006_001_）を、列名 → 値にする
     * rsdt2_id は空文字が多いので、末尾が "_" で終わることがある。正しくない形なら null
     */
    public static function keyFromParam(?string $param): ?array
    {
        $parts = explode('_', (string) $param);
        if (count($parts) !== count(self::KEY_COLUMNS)) {
            return null;
        }
        foreach (array_slice($parts, 0, 4) as $p) {
            if ($p === '') {
                return null;
            }
        }

        return array_combine(self::KEY_COLUMNS, $parts);
    }

    /** URL の id。Rails 版の to_param と同じ形 */
    public function routeId(): string
    {
        return implode('_', array_map(fn ($c) => (string) $this->getAttribute($c), self::KEY_COLUMNS));
    }

    /** 主キーの順に並べる */
    #[Scope]
    protected function inKeyOrder(Builder $query): void
    {
        foreach (self::KEY_COLUMNS as $c) {
            $query->orderBy($c);
        }
    }

    /**
     * 検索条件で絞り込む。空の条件は使わない
     * 部分一致はインデックスが効かないので、全件を読む（全件 175 万件で約 1 秒）
     */
    #[Scope]
    protected function search(Builder $query, RsdtSearchCriteria $c): void
    {
        $like = fn (string $s) => '%'.self::escapeLike($s).'%';
        if ($c->city !== '') {
            $w = $like($c->city);
            $query->where(fn (Builder $q) => $q->where('city', 'like', $w)->orWhere('ward', 'like', $w));
        }
        if ($c->town !== '') {
            $w = $like($c->town);
            $query->where(fn (Builder $q) => $q->where('oaza_cho', 'like', $w)->orWhere('koaza', 'like', $w));
        }
        if ($c->chome !== '') {
            $query->where('chome', 'like', $like($c->chome));
        }
        // blk_num・rsdt_num は完全一致（値はすべて数字なので、部分一致だと「1」で 10・21 なども出てしまうため）
        if ($c->blkNum !== '') {
            $query->where('blk_num', $c->blkNum);
        }
        if ($c->rsdtNum !== '') {
            $query->where('rsdt_num', $c->rsdtNum);
        }
    }

    /** LIKE の特別な文字（% _ \）を、ただの文字として探せるようにする。Laravel には無いので自分で書く */
    public static function escapeLike(string $s): string
    {
        return addcslashes($s, '\\%_');
    }

    /** 画面に出す住所（例：千代田区 内幸町一丁目 6-1） */
    public function address(): string
    {
        $town = implode(' ', array_filter(
            [$this->city, $this->ward, $this->oaza_cho.$this->chome, $this->koaza],
            fn ($v) => (string) $v !== ''
        ));
        $num = implode('-', array_filter(
            [$this->blk_num, $this->rsdt_num, $this->rsdt_num2],
            fn ($v) => (string) $v !== ''
        ));

        return $num !== '' ? "{$town} {$num}" : $town;
    }

    /** フォームに出す値（日付は YYYY-MM-DD、NULL は空文字） */
    public function formValue(string $column): string
    {
        $v = $this->getAttribute($column);

        return $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : (string) $v;
    }

    /**
     * 保存・削除の WHERE に、主キーの 5 列を入れる（Eloquent は主キーが 1 列の前提なので、上書きする）
     * 値は読み込んだときのもの（主キーは変更しないが、念のため getOriginal を使う）
     */
    protected function setKeysForSaveQuery($query)
    {
        foreach (self::KEY_COLUMNS as $c) {
            $query->where($c, '=', $this->getOriginal($c));
        }

        return $query;
    }
}
