<?php
namespace App\Http\Requests\loan;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator as ValidationValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ApplyRequest extends FormRequest
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
            'customer_id' => 'nullable',
            'amount' => 'required',
            'duration' => 'required',
            'monthly_repayment' => 'required',
            'loan_purpose' => 'required',
            'total_repayment' => 'required',
            'monthly_interest' => 'required'
        ];
    }

    protected function failedValidation(ValidationValidator $validator)
    {

        $message = '';
        foreach ($validator->errors()->all() as $error) {
            $message .= "$error <br> ";
        }
        $response = response()->json([
            'status' => 'error',
            'message' => $message,
        ], 400);

        throw (new ValidationException($validator, $response))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    public function failedAuthorization()
    {
        throw new AuthorizationException("You don't have the authority to perform this resource");
    }
}
