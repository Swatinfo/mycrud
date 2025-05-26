<!-- start view button -->
@can($show)
    @if($showURL != "")
        <a href="{{$showURL}}" class="btn btn-sm btn-label-success" data-bs-toggle="tooltip" data-placement="left" title="View"><i class="fa fa-eye"></i></a>
    @else
        <button data-id="{{$row->id}}" class="btn btn-sm btn-label-success view" data-bs-toggle="tooltip" data-placement="left" title="View"><i class="fa fa-eye"></i></button>
    @endif
@endcan
<!-- end view button -->

<!-- start edit button -->
@can($edit)
    @if($editURL != "") 
        <a href="{{$editURL}}" class="btn btn-sm btn-label-primary" data-bs-toggle="tooltip" data-placement="left" title="Edit"><i class="fa fa-edit"></i></a>
    @else
        <button data-id="{{$row->id}}" class="btn btn-sm btn-label-primary edit" data-bs-toggle="tooltip" data-placement="left" title="Edit"><i class="fa fa-edit"></i></button>
    @endif
@endcan
<!-- end edit button -->

<!-- start delete button -->
@can($delete)
    <button data-id="{{$row->id}}" class="btn btn-sm btn-label-danger delete" data-bs-toggle="tooltip" data-placement="left" title="Delete"><i class="fa fa-trash"></i></button>
@endcan
<!-- end delete button -->

<!-- start receive button -->
@isset($receive)
    @can($receive)
        <button data-id="{{$row->id}}" class="btn btn-sm btn-label-info receive" data-bs-toggle="tooltip" data-placement="left" title="Receive"><i class="fas fa-truck"></i></button>
    @endcan  
@endisset

<!-- end receive button -->

<!-- Assign user button -->
@isset($assign)
    @can($assign)
        <button data-id="{{$row->user_id}}" class="btn btn-sm btn-label-info assignUser" data-bs-toggle="tooltip" data-placement="left" title="Assign User"><i class="fa fa-american-sign-language-interpreting"></i></button>
    @endcan  
@endisset
<!-- Assign user button -->

@isset($clone)
    @can($clone)
        <button data-id="{{$row->id}}" class="btn btn-sm btn-label-info clone" data-bs-toggle="tooltip" data-placement="left" title="Clone Product"><i class="fa fa-copy"></i></button>
    @endcan  
@endisset