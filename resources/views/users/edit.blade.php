@extends('layouts.app')
@section('title', 'User Edit')
@section('content')
<div class="row">
    <div class="col-12">
        <h4 class="content-header-title float-start">User Edit</h4>
    </div>
    <div class="col-12">
        <div class="card p-1">
            <div class="card-body">
                <form id="form" action="{{ route('users.update', $userProfile->id) }}" method="POST" autocomplete="nope">
                    @csrf
                    @method('PUT')
                    <div class="row">

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <input type="hidden" name="user_profile_id" id="user_profile_id" value="{{ old('user_profile_id', $userProfile->id) }}">

                            <label class="form-label" for="firstname">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" id="firstname" value="{{ old('firstname', $userProfile->firstname) }}" placeholder="First Name" required>
                            <span class="invalid-feedback d-block" id="error_firstname" role="alert">{{ $errors->first('firstname') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="lastname">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" id="lastname" value="{{ old('lastname', $userProfile->lastname) }}" placeholder="Last Name" required>
                            <span class="invalid-feedback d-block" id="error_lastname" role="alert">{{ $errors->first('lastname') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="dateofbirth">Date Of Birth</label>
                            <input type="text" class="form-control date-picker flatpickr-input" name="dateofbirth" id="dateofbirth" value="{{ old('dateofbirth', isset($userProfile) && $userProfile->date_of_birth != '' ? date('d-m-Y', strtotime($userProfile->date_of_birth)) : '') }}" placeholder="Date Of Birth">
                            <span class="invalid-feedback d-block" id="error_dateofbirth" role="alert">{{ $errors->first('dateofbirth') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="mobile">Mobile Number<span class="text-danger">*</span></label>
                            <input type="number" maxlength="10" class="form-control" id="mobile" name="mobile" placeholder="Mobile Number" value="{{ old('mobile', isset($user) ? $user->mobile : '') }}" required>
                            <span class="invalid-feedback d-block" id="error_mobile" role="alert">{{ $errors->first('mobile') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="email">Email </label>
                            <input type="email" class="form-control text-lowercase" id="email" name="email" value="{{ old('email', isset($user) ? $user->email : '') }}" placeholder="Email" autocomplete="new-email">
                            <span class="invalid-feedback d-block" id="error_email" role="alert">{{ $errors->first('email') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group"></div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="username">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-lowercase" id="username" name="username" value="{{ old('username', isset($user) ? $user->username : '') }}" placeholder="Username" required autocomplete="new-username">
                            <span class="invalid-feedback d-block" id="error_username" role="alert">{{ $errors->first('username') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" minlength="8" class="form-control" id="password" name="password" placeholder="Password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" autocomplete="new-password">
                                <span class="input-group-text cursor-pointer toggle-password">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            <span class="invalid-feedback d-block" id="error_password" role="alert">{{ $errors->first('password') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" minlength="8" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" autocomplete="new-password">
                                <span class="input-group-text cursor-pointer toggle-password">
                                    <i class="fa fa-eye"></i>
                                </span>
                            </div>
                            <span class="invalid-feedback d-block" id="error_confirm_password" role="alert">{{ $errors->first('confirm_password') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group select2-primary">
                            <label class="form-label" for="roles">Designation <span class="text-danger">*</span></label>
                            <select class="select2 form-select" name="roles[]" id="roles" multiple>
                                @foreach ($roleMaster as $role)
                                <option value="{{ $role }}"
                                    {{ in_array($role, old('roles', $userRole ?? [])) ? 'selected' : '' }}>
                                    {{ $role }}
                                </option>
                                @endforeach
                            </select>
                            <span class="invalid-feedback d-block" id="error_role_id" role="alert">{{ $errors->first('roles') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 form-group custom-input-group">
                            <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
                            <select class="select2 form-select" name="status" id="status">
                                <option value="1" {{ old('status', $user->status) == '1' ? 'selected' : 'selected' }}>Active</option>
                                <option value="0" {{ old('status', $user->status) == '0' ? 'selected' : '' }}>Deactive</option>
                            </select>
                            <span class="invalid-feedback d-block" id="error_status" role="alert">{{ $errors->first('status') }}</span>
                        </div>

                        <div class="col-12 col-sm-12 col-md-12 col-lg-12 mt-1">
                            <a href="{{ route('users.index') }}" class="btn btn-label-secondary float-start">Cancel</a>

                            <button type="submit" class="btn btn-primary float-end save">Submit</button>
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
            "roles[]": {
                required: true,
            },
        },
        messages: {
            firstname: {
                required: "Enter Firstname"
            },
            lastname: {
                required: "Enter Lastname"
            },
            mobile: {
                regex: "Enter valid number",
                required: "Enter mobile",
                minlength: "Enter at least 10 digits",
            },
            username: {
                required: "Enter Username"
            },
            "roles[]": {
                required: "Select Designation"
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