<?php

declare(strict_types=1);

namespace Modules\Project\Http\Requests;

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
     */
    public function rules(): array
    {
        $rules = [
            'content' => ['required', 'string', 'min:1'],
        ];

        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['content'] = ['sometimes', 'required', 'string', 'min:1'];
        }

        return $rules;
    }
}
