<?php

namespace App\Http\Requests;

use App\Enums\GuestLinkDuration;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestLinkRequest extends FormRequest
{
    /**
     * @return array<string, list<ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'duration_days' => ['required', 'integer', Rule::enum(GuestLinkDuration::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'duration_days.*' => 'Choose how long the guest link lasts: 1, 7 or 14 days.',
        ];
    }

    public function duration(): GuestLinkDuration
    {
        return $this->enum('duration_days', GuestLinkDuration::class);
    }
}
