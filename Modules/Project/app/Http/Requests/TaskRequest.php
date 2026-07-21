<?php

declare(strict_types=1);

namespace Modules\Project\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(['todo', 'in_progress', 'done'])],
            'assigned_to_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('company_id', auth()->user()->company_id);
                }),
            ],
        ];

        // If it's a PATCH request, make fields optional
        if ($this->isMethod('PATCH') || $this->isMethod('PUT')) {
            $rules['title'] = ['sometimes', 'required', 'string', 'max:255'];
            $rules['status'] = ['sometimes', 'required', 'string', Rule::in(['todo', 'in_progress', 'done'])];
        }

        return $rules;
    }
}
