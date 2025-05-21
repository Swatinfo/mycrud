<div class="row padding-1 p-1">
    <div class="col-md-12">
        
        <div class="form-group mb-2 mb20">
            <label for="name" class="form-label">{{ __('Name') }}</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $jobBatch?->name) }}" id="name" placeholder="Name">
            {!! $errors->first('name', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
        <div class="form-group mb-2 mb20">
            <label for="total_jobs" class="form-label">{{ __('Total Jobs') }}</label>
            <input type="text" name="total_jobs" class="form-control @error('total_jobs') is-invalid @enderror" value="{{ old('total_jobs', $jobBatch?->total_jobs) }}" id="total_jobs" placeholder="Total Jobs">
            {!! $errors->first('total_jobs', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
        <div class="form-group mb-2 mb20">
            <label for="pending_jobs" class="form-label">{{ __('Pending Jobs') }}</label>
            <input type="text" name="pending_jobs" class="form-control @error('pending_jobs') is-invalid @enderror" value="{{ old('pending_jobs', $jobBatch?->pending_jobs) }}" id="pending_jobs" placeholder="Pending Jobs">
            {!! $errors->first('pending_jobs', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
        <div class="form-group mb-2 mb20">
            <label for="failed_jobs" class="form-label">{{ __('Failed Jobs') }}</label>
            <input type="text" name="failed_jobs" class="form-control @error('failed_jobs') is-invalid @enderror" value="{{ old('failed_jobs', $jobBatch?->failed_jobs) }}" id="failed_jobs" placeholder="Failed Jobs">
            {!! $errors->first('failed_jobs', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
        <div class="form-group mb-2 mb20">
            <label for="failed_job_ids" class="form-label">{{ __('Failed Job Ids') }}</label>
            <input type="text" name="failed_job_ids" class="form-control @error('failed_job_ids') is-invalid @enderror" value="{{ old('failed_job_ids', $jobBatch?->failed_job_ids) }}" id="failed_job_ids" placeholder="Failed Job Ids">
            {!! $errors->first('failed_job_ids', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
        <div class="form-group mb-2 mb20">
            <label for="options" class="form-label">{{ __('Options') }}</label>
            <input type="text" name="options" class="form-control @error('options') is-invalid @enderror" value="{{ old('options', $jobBatch?->options) }}" id="options" placeholder="Options">
            {!! $errors->first('options', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
        <div class="form-group mb-2 mb20">
            <label for="cancelled_at" class="form-label">{{ __('Cancelled At') }}</label>
            <input type="text" name="cancelled_at" class="form-control @error('cancelled_at') is-invalid @enderror" value="{{ old('cancelled_at', $jobBatch?->cancelled_at) }}" id="cancelled_at" placeholder="Cancelled At">
            {!! $errors->first('cancelled_at', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>
        <div class="form-group mb-2 mb20">
            <label for="finished_at" class="form-label">{{ __('Finished At') }}</label>
            <input type="text" name="finished_at" class="form-control @error('finished_at') is-invalid @enderror" value="{{ old('finished_at', $jobBatch?->finished_at) }}" id="finished_at" placeholder="Finished At">
            {!! $errors->first('finished_at', '<div class="invalid-feedback" role="alert"><strong>:message</strong></div>') !!}
        </div>

    </div>
    <div class="col-md-12 mt20 mt-2">
        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
    </div>
</div>