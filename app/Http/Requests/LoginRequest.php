<?php

namespace App\Http\Requests;

use App\Helpers\Helper;

class LoginRequest extends baseRequest {

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules () : array {
        return [
            'mobile' => 'required|max:15'
        ];
    }

    /**
     * normalize mobile
     */
    protected function prepareForValidation () : void {
        if ( $this->has('mobile') ) {
            $this->merge([
                'mobile' => Helper::normalizeMobile($this->input('mobile'))
            ]);
        }
    }


}
