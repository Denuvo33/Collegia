<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
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
            'organizer'  => 'required|string|min:3|max:255',
            'title'      => 'required|string|min:5|max:255',
            'content'    => 'required|string|min:50|max:5000',
            'date_event' => 'required|date|after:today',
            'image'      => 'nullable|image|mimes:jpeg,png,jpg|max:5120|dimensions:min_width=300,min_height=300,max_width=4000,max_height=4000',
            'price'      => 'nullable|numeric|min:0|max:100000000',
            'location'   => 'required|string|min:3|max:255',
            'status'     => 'required|in:upcoming,ongoing,finished',
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Judul event minimal 5 karakter.',
            'title.max' => 'Judul event maksimal 255 karakter.',
            'content.min' => 'Deskripsi event minimal 50 karakter.',
            'content.max' => 'Deskripsi event maksimal 5000 karakter.',
            'organizer.min' => 'Nama penyelenggara minimal 3 karakter.',
            'location.min' => 'Lokasi minimal 3 karakter.',
            'image.mimes' => 'Gambar harus berformat JPEG, PNG, atau JPG.',
            'image.max' => 'Ukuran gambar maksimal 5MB.',
            'image.dimensions' => 'Gambar harus berukuran minimal 300x300 pixel dan maksimal 4000x4000 pixel.',
            'date_event.after' => 'Tanggal event harus setelah hari ini.',
            'price.max' => 'Harga tidak boleh lebih dari 100 juta.',
        ];
    }
}
