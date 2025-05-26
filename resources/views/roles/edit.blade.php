@extends('layouts.app')
@section('content')
@if ($message = Session::get('success'))
<div class="alert alert-success">
    <p>{{ $message }}</p>
</div>
@endif
<!-- Role cards -->
<div class="row">
    <div class="col-12">
        <h4>{{ __('message.role.editRole') }}</h4>
        <div class="card">
            <div class="card-header">
                <!-- Add role form -->
                {!! Form::model($role, ['method' => 'PATCH','route' => ['roles.update', $role->id]]) !!}
                <div class="col-12">
                    <label class="form-label" for="modalRoleName">Role Name</label>
                    {!! Form::text('name', null, array('placeholder' => 'Name','class' => 'form-control')) !!}
                </div>
                <div class="col-12">
                    <h5 class="mb-1 mt-6">Role Permissions</h5>
                    <!-- Permission table -->
                    <table class="table table-flush-spacing">
                        <tbody>
                            <tr>
                                <td class="text-nowrap fw-medium text-heading">
                                    Administrator Access
                                    <i class="ti ti-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" title="Allows a full access to the system"></i>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" id="selectAll" />
                                            <label class="form-check-label" for="selectAll"> Select All </label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @foreach($permission as $titleTag => $permissionGroup)
                            <tr>
                                <td>{{ str_replace("_", " ", $permissionGroup['name']) }}</td>
                                <td>
                                    <div class="d-flex justify-content-end">
                                        <!-- Parent Checkbox -->
                                        <div class="form-check mb-0 me-4 me-lg-12">
                                            <input class="form-check-input s-child parent-checkbox" type="checkbox" id="all_{{ $permissionGroup['name'] }}" data-child="child_{{ $permissionGroup['name'] }}" />
                                            <label class="form-check-label permission" for="all_{{ $permissionGroup['name'] }}"> All </label>
                                        </div>
                                        <!-- Child Checkboxes -->
                                        @foreach($permissionGroup['child'] as $key => $child)
                                        <div class="form-check mb-0 me-4 me-lg-12">
                                            <input class="form-check-input s-child child-checkbox child_{{ $permissionGroup['name'] }}"
                                                {{ in_array($child->id, $rolePermissions) ? 'checked' : '' }}
                                                name="permission[]" value="{{$child->id}}" type="checkbox" id="child_{{ $child->id }}" data-parent="{{ $permissionGroup['name'] }}" />
                                            <label class="form-check-label permission" for="child_{{ $child->id }}"> {{ $child->title }} </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!-- Permission table -->
                </div>
                <div class="col-12 mt-4 text-center">
                    <a href="{{ route('roles.index') }}" class="btn btn-label-secondary float-start">Cancel </a>
                    <button type="submit" class="btn btn-primary float-end">Submit</button>
                </div>
                {!! Form::close() !!}
                <!--/ Add role form -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('pagescript')
<script type="application/javascript">
    $(document).ready(function() {

        /* For All Checkbox */
        $('#selectAll').on('change', function() {
            var isChecked = $(this).is(':checked');
            $('.parent-checkbox').prop('checked', isChecked);
            $('.child-checkbox').prop('checked', isChecked);
        });
        /* / For All Checkbox */
        /* For Parent Checkbox */
        $('.parent-checkbox').on('change', function() {
            var childClass = $(this).data('child');
            var isChecked = $(this).is(':checked');
            $('.' + childClass).prop('checked', isChecked);
            var schild = $('.s-child');
            if (schild.length === schild.filter(':checked').length) {
                $('#selectAll').prop('checked', true);
            } else {
                $('#selectAll').prop('checked', false);
            }
        });
        /* / For Parent Checkbox */
        /* For Child Checkbox */
        $('.child-checkbox').on('change', function() {
            var parentName = $(this).data('parent');
            var parentCheckbox = $('#all_' + parentName);
            var childCheckboxes = $('.child_' + parentName);
            var schild = $('.s-child');
            if (childCheckboxes.length === childCheckboxes.filter(':checked').length) {
                parentCheckbox.prop('checked', true);
            } else {
                parentCheckbox.prop('checked', false);
            }
            if (schild.length === schild.filter(':checked').length) {
                $('#selectAll').prop('checked', true);
            } else {
                $('#selectAll').prop('checked', false);
            }
        });
        /* / For Child Checkbox */

        $('.child-checkbox').trigger('change');
    });
</script>
@endsection