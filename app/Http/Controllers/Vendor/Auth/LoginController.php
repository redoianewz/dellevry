<?php

namespace App\Http\Controllers\Vendor\Auth;

use App\Contracts\Repositories\VendorRepositoryInterface;
use App\Enums\SessionKey;
use App\Enums\ViewPaths\Vendor\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\LoginRequest;
use App\Repositories\VendorWalletRepository;
use App\Services\VendorService;
use App\Traits\RecaptchaTrait;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use RecaptchaTrait;

    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepo,
        private readonly VendorService             $vendorService,
        private readonly VendorWalletRepository    $vendorWalletRepo,

    )
    {
        $this->middleware('guest:seller', ['except' => ['logout']]);
    }

    public function generateReCaptcha(): void
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        if (Session::has(SessionKey::VENDOR_RECAPTCHA_KEY)) {
            Session::forget(SessionKey::VENDOR_RECAPTCHA_KEY);
        }
        Session::put(SessionKey::VENDOR_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Type:image/jpeg");
        $recaptchaBuilder->output();
    }

    public function getLoginView(): View
    {
        $recaptchaBuilder = $this->generateDefaultReCaptcha(4);
        $recaptcha = getWebConfig(name: 'recaptcha');
        Session::put(SessionKey::VENDOR_RECAPTCHA_KEY, $recaptchaBuilder->getPhrase());
        return view(Auth::VENDOR_LOGIN[VIEW], compact('recaptchaBuilder', 'recaptcha'));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        // الحصول على إعدادات reCAPTCHA
        $recaptcha = getWebConfig(name: 'recaptcha');

        // التحقق مما إذا كانت reCAPTCHA مفعلة
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            // التحقق من صحة توكن reCAPTCHA
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) use ($recaptcha) {
                        $secretKey = $recaptcha['secret_key'];
                        $response = $value;

                        // إرسال الطلب إلى Google للتحقق من التوكن
                        $url = 'https://www.google.com/recaptcha/api/siteverify';
                        $response = Http::asForm()->post($url, [
                            'secret' => $secretKey,
                            'response' => $response,
                        ])->json();

                        // تحقق من نجاح التحقق
                        if (!isset($response['success']) || !$response['success']) {
                            $fail(translate('ReCAPTCHA_Failed'));
                        }
                    },
                ],
            ]);
        }
        
        $vendor = $this->vendorRepo->getFirstWhere(['identity' => $request['email']]);
        if (!$vendor){
            return response()->json(['error'=>translate('credentials_doesnt_match').'!']);
        }
        $passwordCheck = Hash::check($request['password'],$vendor['password']);
        if ($passwordCheck && $vendor['status'] !== 'approved') {
            return response()->json(['status' => $vendor['status']]);
        }
        if ($this->vendorService->isLoginSuccessful($request->email, $request->password, $request->remember)) {
            if ($this->vendorWalletRepo->getFirstWhere(params:['id'=>auth('seller')->id()]) === false) {
                $this->vendorWalletRepo->add($this->vendorService->getInitialWalletData(vendorId:auth('seller')->id()));
            }
            Toastr::info(translate('welcome_to_your_dashboard').'.');
            return response()->json([
                'success' =>translate('login_successful') . '!',
                'redirectRoute'=>route('vendor.dashboard.index'),
            ]);
        }else{
            return response()->json(['error'=>translate('credentials_doesnt_match').'!']);

        }
    }

    public function logout(): RedirectResponse
    {
        $this->vendorService->logout();
        Toastr::success(translate('logged_out_successfully').'.');
        return redirect()->route('vendor.auth.login');
    }
}
