@extends('layouts.app')

@section('title', 'Brands')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Brands</h1>
        <div>
            @can('create', 'Modules\Brands\Models\Brand')
            <a href="{{  route('brands.brands.create')  }}" class="btn btn-primary me-2">
                 <i class="fas fa-plus me-1"></i> Add New Brand
            </a>
            @endcan
            @if(Route::has('brands.brands.trashed'))
                <a href="{{ route('brands.brands.trashed') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-trash-restore me-1"></i> View Trashed
                </a>
            @endif
        </div>
    </div>

    @if (session('success')) <div class="alert alert-success alert-dismissible fade show" role="alert">{{  session('success')  }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div> @endif
    @if (session('error')) <div class="alert alert-danger alert-dismissible fade show" role="alert">{{  session('error')  }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div> @endif

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Brands List</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="brand-table" class="table table-striped table-hover table-bordered" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                    <th>Name</th>
                    <th>Team Id</th> {{-- This will be populated by the command --}}
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
        const brandTable = $('#brand-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: "{{ $isTrashed ?? false ? route('brands.brands.trashed') : route('brands.brands.index')  }}",
            columns: [
                {{dataTableColumnsJs}} // This placeholder should be filled by your command with column definitions
                { // Actions column
                    data: 'actions', // This can be null if you are not sending 'actions' data from server
                    name: 'actions',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        // Routes are resolved server-side when this Blade file is rendered.
                        // JavaScript will use these as templates.
                        let viewUrlTemplate = "{{ route('brands.brands.show', ':id') }}";
                        let editUrlTemplate = "{{ route('brands.brands.edit', ':id') }}";
                        let destroyUrlTemplate = "{{ route('brands.brands.destroy', ':id') }}";
                        let restoreUrlTemplate = "{{ route('brands.brands.restore', ':id') }}";
                        let forceDeleteUrlTemplate = "{{ route('brands.brands.forceDelete', ':id') }}";

                        // CSRF and Method fields are rendered by Blade server-side into these JS strings.
                        let csrfFieldHtml = `{!! csrf_field() !!}`;
                        let methodDeleteFieldHtml = `{!! method_field('DELETE') !!}`;

                        // Construct URLs for the current row
                        let viewUrl = viewUrlTemplate.replace(':id', row.id);
                        let editUrl = editUrlTemplate.replace(':id', row.id);
                        let destroyUrl = destroyUrlTemplate.replace(':id', row.id);
                        let restoreUrl = restoreUrlTemplate.replace(':id', row.id);
                        let forceDeleteUrl = forceDeleteUrlTemplate.replace(':id', row.id);

                        let actions = '';

                        // Authorization checks (@can) should ideally be done server-side.
                        // For client-side, you might get flags in 'row' data (e.g., row.can_edit).
                        // Here, we generate all buttons and rely on server-side protection of routes.

                        // View button
                        actions += `<a href="${viewUrl}" class="btn btn-sm btn-info me-1" title="View"><i class="fas fa-eye"></i></a>`;

                        if (row.deleted_at) { // Check if 'deleted_at' is present and truthy
                            // Restore Action
                            actions += `<form action="${restoreUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to restore this brand?');">`;
                            actions += csrfFieldHtml;
                            actions += `<button type="submit" class="btn btn-sm btn-success me-1" title="Restore"><i class="fas fa-undo"></i></button></form>`;

                            // Force Delete Action
                            actions += `<form action="${forceDeleteUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete this brand? This action cannot be undone.');">`;
                            actions += csrfFieldHtml;
                            actions += methodDeleteFieldHtml;
                            actions += `<button type="submit" class="btn btn-sm btn-danger" title="Force Delete"><i class="fas fa-trash-alt"></i></button></form>`;
                        } else {
                            // Edit Action
                            actions += `<a href="${editUrl}" class="btn btn-sm btn-warning me-1" title="Edit"><i class="fas fa-edit"></i></a>`;

                            // Soft Delete Action
                            actions += `<form action="${destroyUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete (soft) this brand?');">`;
                            actions += csrfFieldHtml;
                            actions += methodDeleteFieldHtml;
                            actions += `<button type="submit" class="btn btn-sm btn-danger" title="Delete (Soft)"><i class="fas fa-trash"></i></button></form>`;
                        }
                        return actions;
                    }
                }
            ],
            // Optional: Add language options for accessibility or internationalization
            // language: {
            //     search: "_INPUT_",
            //     searchPlaceholder: "Search Brands..."
            // }
        });
    });
    </script>
@endpush
