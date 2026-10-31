<?php

namespace App\Http\Requests;

use App\Models\PendingUpload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InitiateUploadRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $maxBytes = config('features.file_max_size_gb') * 1024 * 1024 * 1024;

        return [
            'name' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:127'],
            'size' => ['required', 'integer', 'min:1', "max:{$maxBytes}"],
            'total_chunks' => ['required', 'integer', 'min:1'],
            'encryption_salt' => ['required', 'string', 'max:64'],
            'client_iv' => ['required', 'string', 'max:24'],
        ];
    }

    /**
     * The declared size fixes how many chunks may arrive, so chunk validation
     * and the quota can trust it.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['size', 'total_chunks'])) {
                    return;
                }

                if ($this->integer('total_chunks') !== PendingUpload::chunksFor($this->integer('size'))) {
                    $validator->errors()->add('total_chunks', 'The chunk count does not match the file size.');
                }
            },
        ];
    }
}
