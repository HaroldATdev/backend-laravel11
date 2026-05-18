<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('category')?->id ?? $this->route('id');

        return [
            'name'        => ['sometimes', 'string', 'max:255', "unique:categories,name,{$id}"],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'boolean'],
        ];
    }
}
