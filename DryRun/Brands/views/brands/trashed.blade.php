@extends('layouts.app')

@section('title', 'Trashed Brands')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Trashed Brands</h1>
        <a href="{{ route('brands.brands.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Brands List
        </a>
    </div>

    {{-- Session messages --}}
    @if (session('success')) <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div> @endif
    @if (session('error')) <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div> @endif

    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Trashed Brands List</h5></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="brand-trashed-table" class="table table-striped table-hover table-bordered" style="width:100%">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>ID</th>
                    <th>Name</th>
                    <th>Team Id</th>
                            <th>Deleted At</th>
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
        $('#brand-trashed-table').DataTable({
            processing: true, serverSide: true, responsive: true,
            ajax: "{{ route('brands.brands.trashed') }}",
            columns: [
                { data: 'id', name: 'id' },
                @php
                    $fieldsForDataTable = ['name', 'team_id'];
                    $dataTableGeneratedColumns = [];
                    foreach($fieldsForDataTable as $field) {
                        if ($field === 'id' || $field === 'deleted_at') continue;
                        $dataTableGeneratedColumns[] = "{ data: '" . $field . "', name: '" . $field . "' },";
                    }
                @endphp
                {!! implode("\n                ", $dataTableGeneratedColumns) !!}
                { data: 'deleted_at', name: 'deleted_at', render: function(data){ return data ? new Date(data).toLocaleString() : ""; } },
                {
                    data: 'actions', name: 'actions', orderable: false, searchable: false,
                    render: function(data, type, row) {
                        let restoreUrl = "{{ route('brands.brands.restore', ':id') }}".replace(':id', row.id);
                        let forceDeleteUrl = "{{ route('brands.brands.forceDelete', ':id') }}".replace(':id', row.id);
                        let actions = '';
                        actions += `<form action="${restoreUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Restore?');">@csrf<button type="submit" class="btn btn-sm btn-success me-1" title="Restore"><i class="fas fa-undo"></i></button></form>`;
                        actions += `<form action="${forceDeleteUrl}" method="POST" style="display:inline-block;" onsubmit="return confirm('Delete Permanently?');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger" title="Force Delete"><i class="fas fa-trash-alt"></i></button></form>`;
                        return actions.replace(/@csrf/g, '{{ csrf_field() }}').replace(/@method\('DELETE'\)/g, '<input type="hidden" name="_method" value="DELETE">');
                    }
                }
            ],
        });
    });
    </script>
@endpush
