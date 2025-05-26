@extends('layouts.app')
@section('content')
<!-- Role cards -->
<div class="row">
    <div class="col-12">
        <h4 class="mb-1">Roles List</h4>
    </div>
    @can('role-create')
    <div class="col-xl-4 col-lg-6 col-md-6 mb-2">
        <div class="card h-100">
            <div class="row h-100">
                <div class="col-sm-5">
                    <div class="d-flex align-items-end h-100 justify-content-center mt-sm-0 mt-4">
                        <img src="{{ asset('assets/img/add-new-roles.png') }}" class="img-fluid mt-sm-4 mt-md-0" alt="add-new-roles" width="83" />
                    </div>
                </div>
                <div class="col-sm-7">
                    <div class="card-body text-sm-end text-center ps-sm-0">
                        <!-- <button data-bs-target="#addRoleModal" data-bs-toggle="modal" class="btn btn-sm btn-primary mb-4 text-nowrap add-new-role">
                            Add New Role
                        </button> -->
                        <a href="{{ route('roles.create') }}" class="btn btn-sm btn-primary new-create mb-4 text-nowrap add-new-role">
                            Add New Role
                        </a>
                        <p class="mb-0">
                            Add new role, <br />
                            if it doesn't exist.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    @foreach ($roles as $key => $role)
    <div class="col-xl-4 col-lg-6 col-md-6">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-normal mb-0 text-body">Total {{ $role->users->count() }} users</h6>
                    <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                        @if($role->users->count() > 0)
                        @foreach($role->users as $usr)
                        <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                            title="{{ $usr->name }}" class="avatar pull-up">
                            <img class="rounded-circle" src="{{ asset('assets/img/avatars/1.png') }}" alt="{{ $usr->name }}" />
                        </li>
                        @endforeach
                        @endif
                    </ul>
                </div>
                <div class="d-flex justify-content-between align-items-end">
                    <div class="role-heading">
                        <h5 class="mb-1">{{ $role->name }}</h5>
                        @can('role-edit')
                        <a href="{{ route('roles.edit',$role->id) }}" class="role-edit-modal"><span>Edit Role</span></a>
                        @endcan
                    </div>
                    @can('role-delete')
                    <a href="javascript:void(0);" data-id="{{ $role->id }}" class="delete">
                        <i class="fas fa-trash text-danger"></i>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>

@endsection

@section('pagescript')
<script src="{{asset('assets/custom/delete.js')}}"></script>
@endsection