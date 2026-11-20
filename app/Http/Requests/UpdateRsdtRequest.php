<?php

namespace App\Http\Requests;

use App\Models\Rsdt;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 住居の更新の入力チェック
 *
 * 前後の空白を取る・空文字を NULL にするのは、Laravel が最初から入れているミドルウェア
 * （TrimStrings・ConvertEmptyStringsToNull）がやるので、ここでは書かない。
 * チェックに通らないと、Laravel が編集画面に redirect して、入力した値とエラーを渡す。
 */
class UpdateRsdtRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [];
        foreach (Rsdt::ATTRIBUTE_RULES as $rule) {
            $rules[$rule['name']] = match (true) {
                // date_format は「2026-02-30」のような無い日付も通さない
                ($rule['type'] ?? null) === 'date' => ['nullable', 'date_format:Y-m-d'],
                // max は文字数（コードポイント単位）で数える。MySQL の VARCHAR と同じ
                default => [($rule['required'] ?? false) ? 'required' : 'nullable', 'string', "max:{$rule['max']}"],
            };
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => '必須です',
            'string' => '文字で入力してください',
            'max' => ':max 文字以内で入力してください',
            'date_format' => '日付の形式が正しくありません（YYYY-MM-DD）',
        ];
    }
}
