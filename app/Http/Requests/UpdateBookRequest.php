<?php

namespace App\Http\Requests;

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
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'size:13', Rule::unique('books', 'isbn')->ignore($this->book)],
            'published_date' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'url'],
            'genre_ids' => ['required', 'array', 'min:1'],
            'genre_ids.*' => ['integer', 'exists:genres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'author.required' => '著者を入力してください',
            'isbn.required' => 'ISBNを入力してください',
            'isbn.size' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'このISBNは既に使用されています。',
            'published_date.required' => '出版日を選択してください',
            'image_url.url' => 'URLはURL形式で入力してください',
            'genre_ids.required' => 'ジャンルを選択してください',
            'genre_ids.min' => 'ジャンルを1つ以上選択してください',
            'genre_ids.*.exists' => '選択されたジャンルが存在しません',
        ];
    }
}
