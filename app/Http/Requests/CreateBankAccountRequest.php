<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBankAccountRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'account_number' => 'required|numeric',
            'routing_number' => 'required|numeric|digits:9',
            'account_type' => 'required|string|in:checking,savings',
            'country' => 'required|string',
            'line1' => 'required|string',
            'city' => 'required|string',
            'state' => 'string',
            'zip' => 'required|string',
        ];
    }
}
