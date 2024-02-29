<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class LoginRequest extends FormRequest
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
   * @return array<string, mixed>
   */
  public function rules()
  {
    return [
      'email' => 'required|email|exists:users,email',
      'password' => 'required',
    ];
  }

  protected function failedValidation(Validator $validator)
  {
    $err = $validator->errors()->toArray();
    $res = new JsonResponse([
      'success' => false,
      'message' => $err,
    ], 422);
    throw new HttpResponseException($res);
  }
}
