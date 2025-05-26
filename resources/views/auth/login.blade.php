<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <!-- Login -->
                <div class="card">
                    <div class="card-body">
                        <!-- Logo -->
                        <div class="app-brand justify-content-center mb-6">
                            <a class="app-brand-link">
                                <img src="{{ asset('assets/img/logo.png') }}" class="rounded" />
                            </a>
                        </div>
                        <!-- /Logo -->

                        <form method="POST" action="{{ route('login') }}" id="formAuthentication" class="mb-4">
                            @csrf
                            <div class="mb-6">
                                <x-input-label for="login" :value="__('Mobile or Username')" />
                                <x-text-input id="login" class="form-control" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
                                <x-input-error :messages="$errors->get('login')" class="mt-2 invalid-feedback" />
                            </div>
                            <div class="mb-6">
                                <x-input-label for="password" :value="__('Password')" />
                                <div class="input-group input-group-merge">
                                    <x-text-input id="password" class="form-control"
                                        type="password"
                                        name="password"
                                        required autocomplete="current-password" />
                                    <span class="input-group-text cursor-pointer toggle-password"><i class="fa fa-eye"></i></span>
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2 invalid-feedback" />
                            </div>
                            <div class="my-8">
                                <div class="d-flex justify-content-between">
                                    <div class="form-check mb-0 ms-2">
                                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember" />
                                        <label class="form-check-label" for="remember_me"> Remember Me </label>
                                    </div>
                                    {{-- @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}">
                                    <p class="mb-0">Forgot Password?</p>
                                    </a>
                                    @endif --}}
                                </div>
                            </div>
                            <div class="mb-6">
                                <button class="btn btn-primary d-grid w-100" type="submit">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- / Content -->
</x-guest-layout>