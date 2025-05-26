<link rel="stylesheet" type="text/css" href="{{asset('assets/vendor/libs/jstree/css/jstree.min.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('assets/vendor/libs/jstree/css/ext-component-tree.min.css')}}">
<form id="formUser" action="javascript:void(0);" method="POST">
     @csrf
     <div class="row">
          <div class="col-12 col-sm-12 form-group custom-input-group  @if(Gate::allows('assign-user-create'))col-md-6 col-lg-6 @else col-md-12 col-lg-12 @endif">
               <label class="form-label sales-manager" for="parent_id">{{ __('message.users.parentUser') }} </label>
               <h6 class="mb-0">{{$parentUser->name}} - {{$parentUser->mobile}}</h6>
               <span class="badge bg-label-info">{{$parentUser->getRoleNames()->first()}}</span>
          </div>

          @can('assign-user-create')
          <div class="col-12 col-sm-12 col-md-6 col-lg-6 form-group custom-input-group">
               <label class="form-label" for="child_id">{{ __('message.users.childUser') }} <span class="text-danger">*</span></label>
               <select class="select2 form-select custom-select2 " name="child_id[]" id="child_id" multiple>
                    @foreach ($userProfile as $keys => $use)
                         @if(count($use) > 0)
                              <optgroup label="{{$keys}}">
                                   @foreach ($use as $u)
                                        <option value="{{$u['id']}}">{{$u['name']}} ({{ formatRoleName($u['role_name'])}}) - {{$u['mobile']}} </option>
                                   @endforeach
                              </optgroup>
                         @endif
                    @endforeach
               </select>
          </div>
          @endcan

          <div class="col-12">
               <div id="jstree-basic">
                    <ul>
                         @foreach($getUserTree as $us)
                         <li data-jstree='{"icon" : "far fa-user"}' class="{{ $us->color }}">
                              {{ $us->user->name }} <small><span class="badge bg-label-info">{{ formatRoleName($us->user->getRoleNames()->first()) }}</span> <a data-id="{{$us->user_id}}" data-parent="{{$us->parent_id}}" href="javascript:void(0);" class="avatar bg-light-danger p-50 m-0 text-danger delete_tree" data-bs-toggle="tooltip" data-placement="left" title="Remove User"><i class="fa fa-trash"></i></a></small>
                              @if(!empty($us->children)) {{-- Check if children exist --}}
                                   @include('users.user_tree', ['children' => $us->children])
                              @endif
                         </li>
                         @endforeach
                    </ul>
               </div>
          </div>
          @can('assign-user-create')
          <div class="col-12 col-sm-12 col-md-12 col-lg-12 mt-1">
               <button type="button" id="save" class="btn btn-sm btn-primary float-end ">{{ __('message.common.submit') }}</button>
          </div>
          @endcan
     </div>
</form>

<script src="{{asset('assets/vendor/libs/jstree/js/jstree.min.js')}}"></script>
<script src="{{asset('assets/vendor/libs/jstree/js/ext-component-tree.min.js')}}"></script>