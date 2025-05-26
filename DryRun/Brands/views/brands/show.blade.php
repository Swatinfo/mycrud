@extends('layouts.app') {{-- Or your application's main layout --}}

@section('title', 'View Brand')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Brand: #{{ $brand->id }}</h1>
        <div>
            @can('update', $brand)
            <a href="{{ route('brands.brands.edit', $brand) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('brands.brands.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    @if($brand->deleted_at)
        <div class="alert alert-warning" role="alert">
            This brand was soft deleted on {{ $brand->deleted_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) }}.
            @can('restore', $brand)
            <form action="{{ route('brands.brands.restore', $brand->id) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to restore this brand?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-undo me-1"></i>Restore</button>
            </form>
            @endcan
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Brand Details</h5>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ $brand->id }}</dd>

                @php
                    $fieldsToShowInShow = ['name', 'team_id'];
                @endphp

                @foreach($fieldsToShowInShow as $field)
                    @if($field === 'deleted_at') @continue @endif {{-- Handled separately --}}
                    <dt class="col-sm-3">{{ Str::title(str_replace('_', ' ', $field)) }}</dt>
                    <dd class="col-sm-9">
                        @php $fieldValue = $brand->$field; @endphp
                        @if(is_object($fieldValue) && $fieldValue instanceof \Carbon\Carbon)
                            {{ $fieldValue->format(config('app.datetime_format_display', 'M d, Y H:i A')) }}
                        @elseif(is_bool($fieldValue))
                            <span class="badge bg-{{ $fieldValue ? 'success' : 'secondary' }}">{{ $fieldValue ? 'Yes' : 'No' }}</span>
                        @elseif(is_array($fieldValue))
                            {{ json_encode($fieldValue) }} {{-- Or format nicely --}}
                        @else
                            {{ $fieldValue ?? 'N/A' }}
                        @endif
                    </dd>
                @endforeach

                {{-- TODO: Display related models data here --}}
                {{-- Example: BelongsTo User --}}
                {{-- @if($brand->relationLoaded('user') && $brand->user)
                <dt class="col-sm-3">User</dt>
                <dd class="col-sm-9"><a href="#">{{ $brand->user->name ?? 'N/A' }}</a></dd>
                @endif --}}

                {{-- Example: BelongsToMany Tags --}}
                {{-- @if($brand->relationLoaded('tags') && $brand->tags->isNotEmpty())
                <dt class="col-sm-3">Tags</dt>
                <dd class="col-sm-9">
                    @foreach($brand->tags as $tag)
                        <span class="badge bg-info me-1">{{ $tag->name }}</span>
                    @endforeach
                </dd>
                @endif --}}

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $brand->created_at ? $brand->created_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) : 'N/A' }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $brand->updated_at ? $brand->updated_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) : 'N/A' }}</dd>

                @if($brand->deleted_at)
                <dt class="col-sm-3 text-danger">Deleted At</dt>
                <dd class="col-sm-9 text-danger">{{ $brand->deleted_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) }}</dd>
                @endif
            </dl>
        </div>
        @if(!$brand->deleted_at)
        <div class="card-footer text-end">
             @can('delete', $brand)
             <form action="{{ route('brands.brands.destroy', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to (soft) delete this brand?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Delete (Soft)</button>
            </form>
            @endcan
        </div>
        @endif
    </div>
</div>
@endsection
