<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HomeRequest extends FormRequest
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
            'content' => 'required|string|min:10|max:5000',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg|max:5120|dimensions:min_width=200,min_height=200,max_width=4000,max_height=4000',
            'user_id' => 'required|exists:users,id',
            'type'    => 'nullable|in:post,collaboration,volunteer',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Konten post tidak boleh kosong.',
            'content.min' => 'Konten post minimal 10 karakter.',
            'content.max' => 'Konten post maksimal 5000 karakter.',
            'image.mimes' => 'Gambar harus berformat JPEG, PNG, atau JPG.',
            'image.max' => 'Ukuran gambar maksimal 5MB.',
            'image.dimensions' => 'Gambar harus berukuran minimal 200x200 pixel dan maksimal 4000x4000 pixel.',
        ];
    }
}
