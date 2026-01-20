<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|min:3|max:300',
            'post_id' => 'required|exists:post,id'
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Komentar tidak boleh kosong',
            'content.min' => 'Komentar minimal 3 karakter',
            'content.max' => 'Komentar maksimal 300 karakter',
            'post_id.required' => 'Post ID tidak valid',
            'post_id.exists' => 'Post tidak ditemukan',
        ];
    }
}
