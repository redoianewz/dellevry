@extends('layouts.front-end.app')

@section('title', translate('sign_in'))

@section('content')
    <div class="container py-4 py-lg-5 my-4 text-align-direction">
         <div class="login-card">
            <div class="mx-auto __max-w-360">
                <h2 class="text-center h4 mb-4 font-bold text-capitalize fs-18-mobile">{{ translate('sign_in')}}</h2>
                <form class="needs-validation mt-2" autocomplete="off" action="{{route('customer.auth.login')}}"
                        method="post" id="customer-login-form">
                    @csrf
                    <div class="form-group">
                        <label class="form-label font-semibold">
                            {{ translate('email') }} / {{ translate('phone')}}
                        </label>
                        <input class="form-control text-align-direction" type="text" name="user_id" id="si-email"
                                value="{{old('user_id')}}" placeholder="{{ translate('enter_email_or_phone') }}"
                                required>
                        <div class="invalid-feedback">{{ translate('please_provide_valid_email_or_phone_number') }} .</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label font-semibold">{{ translate('password') }}</label>
                        <div class="password-toggle rtl">
                            <input class="form-control text-align-direction" name="password" type="password" id="si-password" placeholder="{{ translate('enter_password')}}" required>
                            <label class="password-toggle-btn">
                                <input class="custom-control-input" type="checkbox">
                                    <i class="tio-hidden password-toggle-indicator"></i>
                                    <span class="sr-only">{{ translate('show_password') }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-group d-flex flex-wrap justify-content-between">
                        <div class="rtl">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" name="remember"
                                        id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="custom-control-label text-primary" for="remember">{{ translate('remember_me') }}</label>
                            </div>
                        </div>
                        <a class="font-size-sm text-primary text-underline" href="{{route('customer.auth.recover-password')}}">
                            {{ translate('forgot_password') }}?
                        </a>
                    </div>
                   <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                    <button class="btn btn--primary btn-block btn-shadow" type="submit">{{ translate('sign_in') }}</button>
                </form>
                @if($web_config['social_login_text'])
                <div class="text-center m-3 text-black-50">
                    <small>{{ translate('or_continue_with') }}</small>
                </div>
                @endif
                <div class="d-flex justify-content-center my-3 gap-2">
                @foreach (getWebConfig(name: 'social_login') as $socialLoginService)
                    @if (isset($socialLoginService) && $socialLoginService['status'])
                        <div>
                            <a class="d-block" href="{{ route('customer.auth.service-login', $socialLoginService['login_medium']) }}">
                                <img src="{{theme_asset(path: 'public/assets/front-end/img/icons/'.$socialLoginService['login_medium'].'.png') }}" alt="">
                            </a>
                        </div>
                    @endif
                @endforeach
                </div>
                <div class="text-black-50 text-center">
                    <small>
                        {{  translate('Enjoy_New_experience') }}
                        <a class="text-primary text-underline" href="{{route('customer.auth.sign-up')}}">
                            {{ translate('sign_up') }}
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script')
   <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        grecaptcha.ready(function() {
            grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', {action: 'login'}).then(function(token) {
                document.getElementById('g-recaptcha-response').value = token; // تأكد من استخدام المعرف الصحيح هنا
            });
        });
    });
</script>
@endpush

