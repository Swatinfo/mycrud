@extends('layouts.app')

@section('title', 'Products')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    {{-- Font Awesome for icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Products</h1>
        <div>
            @can('create', '\DryRun\Products\Models\Product') {{-- Check create permission --}}
            <a href="{{ route('products.products.create') }}" class="btn btn-primary me-2">
                 <i class="fas fa-plus me-1"></i> Add New Product
            </a>
            @endcan
            {{-- Link to view trashed items --}}
            @if(Route::has('products.products.trashed'))
                <a href="{{ route('products.products.trashed') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-trash-restore me-1"></i> View Trashed
                </a>
            @endif
        </div>
    </div>

    {{-- Session messages --}}
    @if (session('success')) <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div> @endif
    @if (session('error')) <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div> @endif

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Products List</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="product-table" class="table table-striped table-hover table-bordered" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>ID</th>
                    <th>Name</th>
                    <th>Hsn Code</th>
                    <th>Display Order</th>
                    <th>Min Quantity</th>
                    <th>Max Quantity</th>
                    <th>Burning Loss</th>
                    <th>Is Internal Product</th>
                    <th>Is Active</th>
                    <th>Product Weight</th>
                    <th>Team Id</th>
                    <th>Min Stock</th>
                    <th>Max Stock</th>
                    <th>Is Store Room</th>
                    <th>Unit Id</th>
                    <th>Rack Place</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        const productTable = $('#product-table').DataTable({
            processing: true, serverSide: true, responsive: true,
            ajax: "{{ $isTrashed ?? false ? route('products.products.trashed') : route('products.products.index') }}", // Adjust AJAX source for trashed view
            columns: [
                { data: 'id', name: 'id' },
                @php
                    $fieldsForDataTable = ['name', 'hsn_code', 'display_order', 'min_quantity', 'max_quantity', 'burning_loss', 'is_internal_product', 'is_active', 'product_weight', 'team_id', 'min_stock', 'max_stock', 'is_store_room', 'unit_id', 'rack_place'];
                    $dataTableGeneratedColumns = [];
                    foreach($fieldsForDataTable as $field) {
                        if ($field === 'id') continue;
                        $colDefinition = "{ data: '" . $field . "', name: '" . $field . "'";
                        // Example for boolean: if (str_starts_with($field, 'is_')) $colDefinition .= ", render: function(data){ return data ? 'Yes' : 'No'; }";
                        $colDefinition .= " },";
                        $dataTableGeneratedColumns[] = $colDefinition;
                    }
                @endphp
                {!! implode("\n                ", $dataTableGeneratedColumns) !!}
                // { data: 'created_at', name: 'created_at', render: function(data){ return data ? new Date(data).toLocaleString() : ""; } },
                {
                    data: 'actions', name: 'actions', orderable: false, searchable: false,
                    render: function(data, type, row) {
                        // This content will be replaced by the command with the populated actions.stub
                        // For direct use here, it would be the Blade content of actions.stub
                        // The command will handle replacing {{itemVar}} with 'row'
                        let actionsHtml = `{{--
    This stub defines the content of the "Actions" column in DataTables.
    It expects a variable, typically named `$item` or `row` (in JS context),
    representing the current model instance for the row.
    The main command will replace {{$item}} with the correct variable name from JS.
--}}

@can('view', ${{$item}})
    <a href="{{ route('products.products.show', ${{$item}}->id) }}" class="btn btn-sm btn-info me-1" title="View">
        <i class="fas fa-eye"></i>
    </a>
@endcan

@if(isset(${{$item}}->deleted_at) && ${{$item}}->deleted_at) {{-- Item is soft deleted --}}
    @can('restore', ${{$item}})
        <form action="{{ route('products.products.restore', ${{$item}}->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to restore this product?');">
            @csrf
            <button type="submit" class="btn btn-sm btn-success me-1" title="Restore">
                <i class="fas fa-undo"></i>
            </button>
        </form>
    @endcan
    @can('forceDelete', ${{$item}})
        <form action="{{ route('products.products.forceDelete', ${{$item}}->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete this product? This action cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Force Delete">
                <i class="fas fa-trash-alt" style="color: darkred;"></i> <small>Permanently</small>
            </button>
        </form>
    @endcan
@else {{-- Item is not soft deleted --}}
    @can('update', ${{$item}})
        <a href="{{ route('products.products.edit', ${{$item}}->id) }}" class="btn btn-sm btn-warning me-1" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
    @endcan
    @can('delete', ${{$item}})
        <form action="{{ route('products.products.destroy', ${{$item}}->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete (soft) this product?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Delete (Soft)">
                <i class="fas fa-trash"></i>
            </button>
        </form>
    @endcan
@endif
`.replace(/\{\{itemVar\}\}/g, 'row'); // Placeholder for actual action stub content

                        // A more direct way if actions.stub is simple and doesn't need Blade processing here:
                        // This is a simplified version. The command will inject the processed stub.
                        let viewUrl = "{{ route('products.products.show', ':id') }}".replace(':id', row.id);
                        let editUrl = "{{ route('products.products.edit', ':id') }}".replace(':id', row.id);
                        let destroyUrl = "{{ route('products.products.destroy', ':id') }}".replace(':id', row.id);
                        let restoreUrl = "{{ route('products.products.restore', ':id') }}".replace(':id', row.id);
                        let forceDeleteUrl = "{{ route('products.products.forceDelete', ':id') }}".replace(':id', row.id);

                        let actions = '';
                        // Note: @can checks are done in the actions.stub. Here we just build the HTML.
                        // The actual logic for showing/hiding based on permissions is in the actions.stub.
                        // This JavaScript part is more about constructing URLs.
                        // The command should inject the *rendered* Blade from actions.stub if possible,
                        // or this JS part needs to replicate the @can logic which is not ideal.
                        // For now, assuming the command injects pre-rendered HTML or a template literal.

                        // Simplified:
                        actions += `<a href="${viewUrl}" class="btn btn-sm btn-info me-1" title="View"><i class="fas fa-eye"></i></a>`;
                        if (row.deleted_at) {
                             actions += `<form action="${restoreUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Restore?');">@csrf<button type="submit" class="btn btn-sm btn-success me-1" title="Restore"><i class="fas fa-undo"></i></button></form>`;
                             actions += `<form action="${forceDeleteUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete Permanently?');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" title="Force Delete"><i class="fas fa-trash-alt"></i></button></form>`;
                        } else {
                             actions += `<a href="${editUrl}" class="btn btn-sm btn-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>`;
                             actions += `<form action="${destroyUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete (Soft)?');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button></form>`;
                        }
                        // Replace CSRF and METHOD placeholders properly if using this JS approach
                        actions = actions.replace(/@csrf/g, '{{ csrf_field() }}').replace(/@method\('DELETE'\)/g, '<input type="hidden" name="_method" value="DELETE">');
                        return actions;
                    }
                }
            ],
        });
    });
    </script>
@endpush
