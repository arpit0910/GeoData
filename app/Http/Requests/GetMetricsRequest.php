<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetMetricsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'isin' => $this->route('isin'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'isin' => 'required|string|size:12|alpha_num',
        ];
    }
}
