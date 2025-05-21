{{-- This form partial is used by create.blade.php and edit.blade.php --}}
{{-- Variable $product is expected to be passed to this view --}}
{{-- For create, pass new \DryRun\Product\Models\Product(), for edit, pass the existing $product --}}

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name ?? '') }}">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="hsn_code" class="form-label">Hsn Code</label>
    <textarea name="hsn_code" id="hsn_code" class="form-control @error('hsn_code') is-invalid @enderror" rows="3">{{ old('hsn_code', $product->hsn_code ?? '') }}</textarea>
    @error('hsn_code')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="display_order" class="form-label">Display Order</label>
    <input type="text" name="display_order" id="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', $product->display_order ?? '') }}">
    @error('display_order')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="min_quantity" class="form-label">Min Quantity</label>
    <input type="text" name="min_quantity" id="min_quantity" class="form-control @error('min_quantity') is-invalid @enderror" value="{{ old('min_quantity', $product->min_quantity ?? '') }}">
    @error('min_quantity')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="max_quantity" class="form-label">Max Quantity</label>
    <input type="text" name="max_quantity" id="max_quantity" class="form-control @error('max_quantity') is-invalid @enderror" value="{{ old('max_quantity', $product->max_quantity ?? '') }}">
    @error('max_quantity')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="burning_loss" class="form-label">Burning Loss</label>
    <input type="text" name="burning_loss" id="burning_loss" class="form-control @error('burning_loss') is-invalid @enderror" value="{{ old('burning_loss', $product->burning_loss ?? '') }}">
    @error('burning_loss')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="is_internal_product" value="0"> {{-- Hidden input for unchecked value --}}
    <input type="checkbox" name="is_internal_product" id="is_internal_product" class="form-check-input @error('is_internal_product') is-invalid @enderror" value="1" {{ (old('is_internal_product', $product->is_internal_product ?? 0) == 1) ? 'checked' : '' }}>
    <label for="is_internal_product" class="form-check-label">Is Internal Product</label>
    @error('is_internal_product')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="is_active" value="0"> {{-- Hidden input for unchecked value --}}
    <input type="checkbox" name="is_active" id="is_active" class="form-check-input @error('is_active') is-invalid @enderror" value="1" {{ (old('is_active', $product->is_active ?? 0) == 1) ? 'checked' : '' }}>
    <label for="is_active" class="form-check-label">Is Active</label>
    @error('is_active')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="product_weight" class="form-label">Product Weight</label>
    <input type="text" name="product_weight" id="product_weight" class="form-control @error('product_weight') is-invalid @enderror" value="{{ old('product_weight', $product->product_weight ?? '') }}">
    @error('product_weight')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="team_id" class="form-label">Team Id</label>
    <select name="team_id" id="team_id" class="form-select @error('team_id') is-invalid @enderror">
        <option value="">-- Select Team --</option>
                    @foreach($teams ?? [] as $relatedItem)
                        <option value="{{$relatedItem->id}}" {{ (old('team_id', $product->team_id ?? null) == $relatedItem->id) ? 'selected' : '' }}>{{$relatedItem->name ?? $relatedItem->id}}</option>
                    @endforeach
    </select>
    @error('team_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="min_stock" class="form-label">Min Stock</label>
    <input type="text" name="min_stock" id="min_stock" class="form-control @error('min_stock') is-invalid @enderror" value="{{ old('min_stock', $product->min_stock ?? '') }}">
    @error('min_stock')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="max_stock" class="form-label">Max Stock</label>
    <input type="text" name="max_stock" id="max_stock" class="form-control @error('max_stock') is-invalid @enderror" value="{{ old('max_stock', $product->max_stock ?? '') }}">
    @error('max_stock')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3 form-check">
    <input type="hidden" name="is_store_room" value="0"> {{-- Hidden input for unchecked value --}}
    <input type="checkbox" name="is_store_room" id="is_store_room" class="form-check-input @error('is_store_room') is-invalid @enderror" value="1" {{ (old('is_store_room', $product->is_store_room ?? 0) == 1) ? 'checked' : '' }}>
    <label for="is_store_room" class="form-check-label">Is Store Room</label>
    @error('is_store_room')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="unit_id" class="form-label">Unit Id</label>
    <select name="unit_id" id="unit_id" class="form-select @error('unit_id') is-invalid @enderror">
        <option value="">-- Select Unit --</option>
                    @foreach($units ?? [] as $relatedItem)
                        <option value="{{$relatedItem->id}}" {{ (old('unit_id', $product->unit_id ?? null) == $relatedItem->id) ? 'selected' : '' }}>{{$relatedItem->name ?? $relatedItem->id}}</option>
                    @endforeach
    </select>
    @error('unit_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="rack_place" class="form-label">Rack Place</label>
    <textarea name="rack_place" id="rack_place" class="form-control @error('rack_place') is-invalid @enderror" rows="3">{{ old('rack_place', $product->rack_place ?? '') }}</textarea>
    @error('rack_place')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
