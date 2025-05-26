@extends('layouts.app')
@section('title', 'User Add')
@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="content-header-title float-start">{{ __('message.users.userAdd') }}</h4>
    </div>
    <div class="col-12">
        <div class="card p-1">
            <div class="card-body">
                <form id="form" action="{{ route('users.store') }}" method="POST" autocomplete="nope">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <input type="hidden" name="user_profile_id" id="user_profile_id" value="{{ old('user_profile_id') }}">
                            <label class="form-label" for="firstname">{{ __('message.users.firstName') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" id="firstname" placeholder="{{ __('message.users.firstName') }}" value="{{ old('firstname') }}" required>
                            <span class="invalid-feedback d-block" id="error_firstname" role="alert">{{ $errors->first('firstname') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="lastname">{{ __('message.users.lastName') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name" value="{{ old('lastname') }}" required>
                            <span class="invalid-feedback d-block" id="error_lastname" role="alert">{{ $errors->first('lastname') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="dateofbirth">{{ __('message.users.dateOfBirth') }}</label>
                            <input type="text" class="form-control date-picker flatpickr-input" name="dateofbirth" id="dateofbirth" placeholder="{{ __('message.users.dateOfBirth') }}" value="{{ old('dateofbirth') }}">
                            <span class="invalid-feedback d-block" id="error_dateofbirth" role="alert">{{ $errors->first('dateofbirth') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="mobile">{{ __('message.users.mobileNumber') }}<span class="text-danger">*</span></label>
                            <input type="number" maxlength="10" class="form-control" id="mobile" name="mobile" placeholder="{{ __('message.users.mobileNumber') }}" value="{{ old('mobile') }}" required>
                            <span class="invalid-feedback d-block" id="error_mobile" role="alert">{{ $errors->first('mobile') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="email">{{ __('message.users.email') }} </label>
                            <input type="email" class="form-control text-lowercase" id="email" name="email" placeholder="{{ __('message.users.email') }}" value="{{ old('email') }}" autocomplete="new-email">
                            <span class="invalid-feedback d-block" id="error_email" role="alert">{{ $errors->first('email') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group"></div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="username">{{ __('message.users.userName') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-lowercase" id="username" name="username" placeholder="{{ __('message.users.userName') }}" value="{{ old('username') }}" required autocomplete="new-username">
                            <span class="invalid-feedback d-block" id="error_username" role="alert">{{ $errors->first('username') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="password">{{ __('message.users.password') }} <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <input type="password" minlength="8" class="form-control" id="password" name="password" placeholder="{{ __('message.users.password') }}" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" required autocomplete="new-password">
                                <span class="input-group-text cursor-pointer toggle-password">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            <span class="invalid-feedback d-block" id="error_password" role="alert">{{ $errors->first('password') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="confirm_password">{{ __('message.users.confirmPassword') }} <span class="text-danger">*</span></label>
                            <div class="input-group input-group-merge">
                                <input type="password" minlength="8" class="form-control" id="confirm_password" name="confirm_password" placeholder="{{ __('message.users.confirmPassword') }}" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" required autocomplete="new-password">
                                <span class="input-group-text cursor-pointer toggle-password">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            <span class="invalid-feedback d-block" id="error_confirm_password" role="alert">{{ $errors->first('confirm_password') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group select2-primary">
                            <label class="form-label" for="roles">{{ __('message.users.designation') }} <span class="text-danger">*</span></label>
                            <select class="select2 form-select " name="roles[]" id="roles" multiple>
                                @foreach ($roleMaster as $role)
                                <option value="{{ $role }}" {{ old('roles') == $role ? 'selected' : '' }}>{{ $role }}</option>
                                @endforeach
                            </select>
                            <span class="invalid-feedback d-block" id="error_role_id" role="alert">{{ $errors->first('roles') }}</span>
                        </div>
 
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="status">{{ __('message.users.status') }} <span class="text-danger">*</span></label>
                            <select class="select2 form-select select2-hidden-accessible" name="status" id="status">
                                <option value="Active" {{ old('status') == 'Active' ? 'selected' : 'selected' }}>{{ __('message.users.active') }}</option>
                                <option value="Deactivated" {{ old('status') == 'Deactivated' ? 'selected' : '' }}>{{ __('message.users.deactivated') }}</option>
                            </select>
                            <span class="invalid-feedback d-block" id="error_role_id" role="alert">{{ $errors->first('status') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 mt-1">
                            <a href="{{ route('users.index') }}" class="btn btn-label-secondary float-start">{{ __('message.common.cancel') }}</a>

                            <button type="submit" class="btn btn-primary float-end save">{{ __('message.common.submit') }}</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script type="application/javascript">
    @if($message = Session::get('error'))
    toastr.error("{{ addslashes($message) }}", "Error");
    @endif

        'use strict';
    const URL = "{{route('users.index')}}";

    $(document).on('keypress', '#mobile', function() {
        if ($("#mobile").val().length > 9) {
            $("#mobile").attr('type', 'text');
        } else {
            $("#mobile").attr('type', 'number');
        }
    });

    $(document).on('keypress', '#alt_mobile_one', function() {
        if ($("#alt_mobile_one").val().length > 9) {
            $("#alt_mobile_one").attr('type', 'text');
        } else {
            $("#alt_mobile_one").attr('type', 'number');
        }
    });

    $(document).on('keypress', '#alt_mobile_two', function() {
        if ($("#alt_mobile_two").val().length > 9) {
            $("#alt_mobile_two").attr('type', 'text');
        } else {
            $("#alt_mobile_two").attr('type', 'number');
        }
    });

    $("#form").validate({
        rules: {
            firstname: {
                required: true,
            },
            lastname: {
                required: true,
            },
            mobile: {
                regex: /^[0-9]{10}$/,
                required: true,
                minlength: 10,
            },
            username: {
                required: true,
            },
            password: {
                required: true,
            },
            confirm_password: {
                required: true,
            },
            "roles[]": {
                required: true,
            },
        },
        messages: {
            firstname: {
                required: "{{ __('message.Enter Firstname') }}"
            },
            lastname: {
                required: "{{ __('message.Enter Lastname') }}"
            },
            mobile: {
                regex: "{{ __('message.Enter valid number') }}",
                required: "{{ __('message.Enter mobile') }}",
                minlength: "{{ __('message.Enter at least 10 digits') }}",
            },
            username: {
                required: "{{ __('message.Enter Username') }}"
            },
            password: {
                required: "{{ __('message.Enter Password') }}"
            },
            confirm_password: {
                required: "{{ __('message.Enter Confirm Password') }}"
            },
            "roles[]": {
                required: "{{ __('message.Select Designation') }}"
            },
        },
        errorElement: "p",
        errorClass: "text-danger mb-0 custom-error",

        highlight: function(element) {
            $(element).addClass('has-error');
        },
        unhighlight: function(element) {
            $(element).removeClass('has-error');
        },
        errorPlacement: function(error, element) {
            $(element).closest('.custom-input-group').append(error);
        }

    });
</script>
@endsection