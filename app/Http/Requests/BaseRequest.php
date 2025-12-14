<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRequest extends FormRequest {

    /**
     * Convert errors to single string per field.
     */
    protected function formatErrors (Validator $validator) : array {
        $errors = $validator->errors()->toArray();

        return array_map(function ($messages) {
            return $messages[0] ?? '';
        }, $errors);
    }

    /**
     * Handle failed validation with unified API response.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation (Validator $validator) : void {
        throw new HttpResponseException(response()->json([
            'success'    => false,
            'statusType' => 'warning',
            'title'      => __('alert.warning') ?? 'اخطار',
            'message'    => __('validation.invalid_field'),
            'errors'     => $this->formatErrors($validator),
            'notify'     => true,
        ], 422));
    }

    /**
     * Optional: Handle failed authorization in unified way.
     *
     * @throws HttpResponseException
     */
    protected function failedAuthorization () : void {
        throw new HttpResponseException(response()->json([
            'success'    => false,
            'statusType' => 'error',
            'title'      => __('alert.error') ?? 'خطا',
            'message'    => __('auth.unauthorized'),
            'errors'     => [],
            'notify'     => true,
        ], 403));
    }

    /**
     * Default: allow all unless overridden.
     */
    public function authorize () : bool {
        // You can override this in children if needed.
        return true;
    }

    /**
     * Provide default messages optionally override in child request.
     */
    public function messages () : array {
        return [
            'required' => __('validation.required'),
            'string'   => __('validation.string'),
            'numeric'  => __('validation.numeric'),
            // You can add your common rules here.
        ];
    }

    /**
     * Provide readable attribute names for all requests.
     */
    public function attributes () : array {
        return [
            'name'  => __('fields.name'),
            'email' => __('fields.email'),
            // Add global labels here.
        ];
    }
}
