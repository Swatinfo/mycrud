@extends('layouts.app') {{-- Or your application's main layout --}}

@section('title', 'Create New Brand')

@section('content')
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Create New Brand</h1>
        <a href="{{ route('brands.brands.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <p class="fw-bold">Please correct the errors below:</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('brands.brands.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card">
            <div class="card-body">
                @include('brands::brands._form', ['brand' => new \DryRun\Brands\Models\Brand()])
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save me-1"></i> Create Brand
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
