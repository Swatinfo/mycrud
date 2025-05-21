@extends('layouts.app')

@section('template_title')
    {{ $jobBatch->name ?? __('Show') . " " . __('Job Batch') }}
@endsection

@section('content')
    <section class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="float-left">
                            <span class="card-title">{{ __('Show') }} Job Batch</span>
                        </div>
                        <div class="float-right">
                            <a class="btn btn-primary btn-sm" href="{{ route('job-batches.index') }}"> {{ __('Back') }}</a>
                        </div>
                    </div>

                    <div class="card-body bg-white">
                        
                                <div class="form-group mb-2 mb20">
                                    <strong>Name:</strong>
                                    {{ $jobBatch->name }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Total Jobs:</strong>
                                    {{ $jobBatch->total_jobs }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Pending Jobs:</strong>
                                    {{ $jobBatch->pending_jobs }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Failed Jobs:</strong>
                                    {{ $jobBatch->failed_jobs }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Failed Job Ids:</strong>
                                    {{ $jobBatch->failed_job_ids }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Options:</strong>
                                    {{ $jobBatch->options }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Cancelled At:</strong>
                                    {{ $jobBatch->cancelled_at }}
                                </div>
                                <div class="form-group mb-2 mb20">
                                    <strong>Finished At:</strong>
                                    {{ $jobBatch->finished_at }}
                                </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
