@extends('layouts.app') {{-- Or your application's main layout --}}

@section('title', 'View Product')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Product: #{{ $product->id }}</h1>
        <div>
            @can('update', $product)
            <a href="{{ route('products.products.edit', $product) }}" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i> Edit
            </a>
            @endcan
            <a href="{{ route('products.products.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    @if($product->deleted_at)
        <div class="alert alert-warning" role="alert">
            This product was soft deleted on {{ $product->deleted_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) }}.
            @can('restore', $product)
            <form action="{{ route('products.products.restore', $product->id) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Are you sure you want to restore this product?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-undo me-1"></i>Restore</button>
            </form>
            @endcan
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Product Details</h5>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">ID</dt>
                <dd class="col-sm-9">{{ $product->id }}</dd>

                @php
                    $fieldsToShowInShow = ['name', 'hsn_code', 'display_order', 'min_quantity', 'max_quantity', 'burning_loss', 'is_internal_product', 'is_active', 'product_weight', 'team_id', 'min_stock', 'max_stock', 'is_store_room', 'unit_id', 'rack_place'];
                @endphp

                @foreach($fieldsToShowInShow as $field)
                    @if($field === 'deleted_at') @continue @endif {{-- Handled separately --}}
                    <dt class="col-sm-3">{{ Str::title(str_replace('_', ' ', $field)) }}</dt>
                    <dd class="col-sm-9">
                        @php $fieldValue = $product->$field; @endphp
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
                {{-- @if($product->relationLoaded('user') && $product->user)
                <dt class="col-sm-3">User</dt>
                <dd class="col-sm-9"><a href="#">{{ $product->user->name ?? 'N/A' }}</a></dd>
                @endif --}}

                {{-- Example: BelongsToMany Tags --}}
                {{-- @if($product->relationLoaded('tags') && $product->tags->isNotEmpty())
                <dt class="col-sm-3">Tags</dt>
                <dd class="col-sm-9">
                    @foreach($product->tags as $tag)
                        <span class="badge bg-info me-1">{{ $tag->name }}</span>
                    @endforeach
                </dd>
                @endif --}}

                <dt class="col-sm-3">Created At</dt>
                <dd class="col-sm-9">{{ $product->created_at ? $product->created_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) : 'N/A' }}</dd>

                <dt class="col-sm-3">Updated At</dt>
                <dd class="col-sm-9">{{ $product->updated_at ? $product->updated_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) : 'N/A' }}</dd>

                @if($product->deleted_at)
                <dt class="col-sm-3 text-danger">Deleted At</dt>
                <dd class="col-sm-9 text-danger">{{ $product->deleted_at->format(config('app.datetime_format_display', 'M d, Y H:i A')) }}</dd>
                @endif
            </dl>
        </div>
        @if(!$product->deleted_at)
        <div class="card-footer text-end">
             @can('delete', $product)
             <form action="{{ route('products.products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to (soft) delete this product?');">
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
