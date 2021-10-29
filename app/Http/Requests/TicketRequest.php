<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;

class TicketRequest extends FormRequest
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
        $rules = [];
        if (count(session()->get('account')->childAccounts) > 0) {
            $rules['account_id'] = [
                'required',
                Rule::in([
                    session()->get('account')->id,
                    ...\array_map(fn($a) => $a->id, session()->get('account')->childAccounts)
                ])
            ];
        }

        return array_merge($rules, [
            'subject' => 'string|required',
            'description' => 'string|required',
        ]);
    }

    public function messages()
    {
        return [
            'account_id.required' => 'The account field is required.',
            'account_id.in' => 'The account field has invalid data.',
        ];
    }
}
