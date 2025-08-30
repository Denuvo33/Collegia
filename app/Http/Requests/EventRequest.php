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
            'user_id'   => 'required|exists:users,id',
            'organizer' => 'required|string|max:255',
            'title'     => 'required|string|max:255',
            'content'   => 'required|string',
            'date_event' => 'required|date|after:today',
            'image'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'price'     => 'nullable|numeric|min:0',
            'location'  => 'required|string|max:255',
            'status'    => 'required|in:upcoming,ongoing,finished',
        ];
    }
}
