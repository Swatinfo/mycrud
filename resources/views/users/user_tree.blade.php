<ul>
    @foreach($children as $child)
        <li data-jstree='{"icon" : "far fa-user"}' class="{{ $child->color }}">
            {{ $child->user->name }} <small><span class="badge bg-label-info">{{ formatRoleName($child->user->roles()->first()->name) }}</span> <a data-id="{{$child->user_id}}" data-parent="{{$us->parent_id}}" href="javascript:void(0);" class="avatar bg-light-danger p-50 m-0 text-danger delete_tree" data-bs-toggle="tooltip" data-placement="left" title="Remove User"><i class="fa fa-trash"></i></a></small>
            @if(!empty($child->children))  {{-- Check if children exist --}}
                @include('users.user_tree', ['children' => $child->children])
            @endif
        </li>
    @endforeach
</ul>