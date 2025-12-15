<?php

namespace App\Http\Controllers\Api;

use App\Enums\OTP\Type;
use App\Enums\User\Status;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\OTP;
use App\Models\User;
use App\Services\OtpService;
use app\Supports\Sanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller {
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     operationId="authLogin",
     *     tags={"Authentication"},
     *     summary="Login or register user via mobile number",
     *     description="Logs in a user using mobile number. If user does not exist, it will be created and an OTP code will be sent.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"mobile"},
     *             @OA\Property(
     *                 property="mobile",
     *                 type="string",
     *                 example="09123456789",
     *                 description="User mobile number"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful login step",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(property="message", type="string", example="Verification code sent"),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="nextPage", type="string", example="validationCode")
     *                     )
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="success", type="boolean", example=true),
     *                     @OA\Property(
     *                         property="data",
     *                         type="object",
     *                         @OA\Property(property="nextPage", type="string", example="password")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=202,
     *         description="OTP already sent, user must wait",
     *         @OA\JsonContent(
     *             @OA\Property(property="warning", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Please wait before requesting another code"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="secondsLeft", type="integer", example=42)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The mobile field is required."),
     *             @OA\Property(property="errors", type="object", example={"mobile": {"The mobile field is required."}})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Please try again later")
     *         )
     *     )
     * )
     */

    public function login (LoginRequest $request) : JsonResponse {

        $mobile = $request->input('mobile');

        /** @var User $user */
        $user = User::whereMobile($mobile)->first();
        if ( !$user ) {
            DB::beginTransaction();

            try {
                $user = User::create([
                    'mobile' => $mobile,
                ]);

                $otp = app(OtpService::class)->generate($user->id, Type::Login, 180);

                DB::commit();

                return self::success([
                    'message' => __('auth.enter_code', ['code' => $otp->code]),
                    'data'    => [
                        'nextPage' => 'validationCode'
                    ],
                ]);

            } catch ( \Throwable $e ) {
                DB::rollBack();
                logException($e, 'Create user failed', [
                    'mobile' => $mobile,
                ], 'login');

                return self::error(__('alert.try_again'));
            }
        }

        if ( !$user->isCompleteInfo() || !$user->hasVerifiedMobile() ) {


            $otp = $user->findOtp(Type::Login);

            if ( !$otp || $otp->isExpired(Type::Login) ) {
                $otp = app(OtpService::class)->generate($user->id, Type::Login, 180);

                if ( $otp ) {
                    return self::success([
                        'message' => __('auth.enter_code', ['code' => $otp->code]),
                        'data'    => [
                            'page' => 'validationCode'
                        ],
                    ]);
                }

                return self::error(__('alert.try_again'));
            }

            $secondsLeft = now()->diffInSeconds($otp->expires_at);

            return self::warning([
                'message' => __('otp.wait_code'),
                'errors'  => [
                    'secondsLeft' => abs((int) $secondsLeft),
                ],
            ]);
        }

        return self::success([
            'data' => [
                'nextPage' => 'password'
            ]
        ]);

    }

    public function otpVerify (Request $request) {

        $validator = Validator::make($request->all(), [
            'mobile' => 'required|max:15',
            'code'   => 'required|digits:4'
        ]);

        if ( $validator->fails() ) {
            return self::validationResponse($validator);
        }
        $data   = $validator->validated();
        $mobile = $data['mobile'];
        $code   = $data['code'];
        $mobile = Helper::normalizeMobile($mobile);
        if ( !$mobile ) {
            return self::warning([
                'message' => 'فیلدها را به صورت صحیح وارد فرمایید',
                'errors'  => [
                    'mobile' => 'شماره همراه معتبر نیست'
                ]
            ], 422);
        }
        /**
         * @var User $user
         */
        $user = User::whereMobile($mobile)->first();
        if ( !$user ) {
            return self::warning('شماره تلفن صحیح نمی باشد', 404);
        }
        /** @var OTP|null $otp */
        $otp = $user->otp;
        if ( !$otp || $otp->isExpired(Type::Login) || !$otp->isValid($code) ) {
            return self::warning('کد منقضی شده است');
        }

        if ( !$user->hasVerifiedMobile() ) {
            $user->forceFill([
                'mobile_verified_at' => now(),
            ])->save();
        }
        // expire code
        $otp->setExpire();
        $otp->save();

        if ( blank($user->last_name) ) {
            return self::success([
                'message' => 'لطفا اطلاعات خود را کامل فرمایید',
                'data'    => [
                    'page' => 'register'
                ],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return self::success([
            'message' => 'با موفقیت وارد شدید',
            'data'    => [
                'token' => $token,
                'page'  => 'home',
            ],
        ]);


    }

    public function updateInfo (Request $request) : \Illuminate\Http\JsonResponse {


        $validator = Sanitizer::make($request->all(), [
            'name'        => 'string|html:5|trim',
            'password'    => 'string|alphanumeric|trim',
            'last_name'   => 'string|alphanumeric|trim',
            'national_id' => 'string|numeric|trim',
        ])->sanitizeAndValidate([
            'mobile'      => 'required|max:15',
            'name'        => 'required|string|max:255',
            'password'    => 'required|string|max:255',
            'last_name'   => 'required|string|max:255',
            'national_id' => 'required|string|size:10',
        ]);

        if ( $validator->fails() ) {
            return self::validationResponse($validator);
        }

        $data       = $validator->validated();
        $mobile     = $data['mobile'];
        $name       = $data['name'];
        $lastName   = $data['last_name'];
        $password   = $data['password'];
        $nationalId = $data['national_id'];
        $mobile     = Helper::normalizeMobile($mobile);

        if ( !$mobile ) {
            return self::warning([
                'message' => 'فیلدها را به صورت صحیح وارد فرمایید',
                'errors'  => [
                    'mobile' => 'شماره همراه معتبر نیست'
                ]
            ], 422);
        }
        $isValidNationalId = Helper::validateNationalCode($nationalId);
        if ( !$isValidNationalId ) {
            return self::warning([
                'message' => 'فیلدها را به صورت صحیح وارد فرمایید',
                'errors'  => [
                    'national_id' => 'شماره کد ملی معتبر نیست'
                ]
            ], 422);
        }

        $user = User::where('mobile', $mobile)->first();
        if ( !$user ) {
            return self::warning('شماره تلفن صحیح نمی باشد', 404);
        }
        try {

            $user->update([
                'name'        => $name,
                'national_id' => $nationalId,
                'status'      => Status::ACTIVE,
                'last_name'   => $lastName,
                'password'    => Hash::make($password),
            ]);

            return self::success([
                'message' => 'اطلاعات با موفقیت ثبت شد',
                'data'    => [
                    'page'  => 'home',
                    'token' => $user->createToken('auth_token')->plainTextToken,
                ]
            ]);

        } catch ( \Exception $e ) {
            Log::channel('test')->info('Register user get failed', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'mobile'  => $mobile
            ]);

            return self::error('مشکلی در ثبت اطلاعات به وجود آمد لطفا دوباره تلاش فرمایید');

        }

    }

}
