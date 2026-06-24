<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'size:13', Rule::unique('books', 'isbn')->ignore($this->book)],
            'published_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => '登録者IDを入力してください',
            'user_id.integer' => '登録者IDは整数で入力してください',
            'user_id.exists' => '指定されたユーザーは存在しません',
            'title.required' => 'タイトルを入力してください',
            'author.required' => '著者を入力してください',
            'isbn.required' => 'ISBNを入力してください',
            'isbn.size' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'このISBNは既に使用されています',
            'published_date.required' => '出版日を選択してください',
            'published_date.before_or_equal' => '出版日は今日以前の日付を指定してください',
            'image_url.url' => '画像URLは正しいURL形式で入力してください',
            'genres.required' => 'ジャンルを選択してください',
            'genres.array' => 'ジャンルの形式が不正です',
            'genres.min' => 'ジャンルを1つ以上選択してください',
            'genres.*.integer' => 'ジャンルIDは整数で入力してください',
            'genres.*.exists' => '選択されたジャンルが存在しません',
        ];
    }
}
