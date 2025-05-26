{{-- This form partial is used by create.blade.php and edit.blade.php --}}
{{-- Variable $brand is expected to be passed to this view --}}
{{-- For create, pass new \DryRun\Brands\Models\Brand(), for edit, pass the existing $brand --}}

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $brand->name ?? '') }}">
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="team_id" class="form-label">Team Id</label>
    <select name="team_id" id="team_id" class="form-select @error('team_id') is-invalid @enderror">
        <option value="">-- Select Team --</option>
                    @foreach($teams ?? [] as $relatedItem)
                        <option value="{{$relatedItem->id}}" {{ (old('team_id', $brand->team_id ?? null) == $relatedItem->id) ? 'selected' : '' }}>{{$relatedItem->name ?? $relatedItem->id}}</option>
                    @endforeach
    </select>
    @error('team_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
