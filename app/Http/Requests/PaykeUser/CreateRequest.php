<?php

namespace App\Http\Requests\PaykeUser;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'payke_host_db' => 'required',
            'payke_resource' => 'required',
            'payke_app_name' => 'required',
            'user_name' => 'required',
            'email_address' => 'required'
        ];
    }

    public function paykeHostId(): int
    {
        return (int)explode("_", $this->input("payke_host_db"))[0];
    }

    public function paykeDbId(): int
    {
        return (int)explode("_", $this->input("payke_host_db"))[1];
    }

    public function paykeResourceId(): int
    {
        return (int)$this->input('payke_resource');
    }

    public function paykeAppName(): string
    {
        return $this->input('payke_app_name');
    }

    public function enableAffiliate(): bool
    {
        return (bool)$this->input('enable_affiliate');
    }

    public function userName(): string
    {
        return $this->input('user_name');
    }

    public function emailAddress(): string
    {
        return $this->input('email_address');
    }

    public function memo(): string
    {
        return $this->input('memo') ?? "";
    }
}
