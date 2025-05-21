@extends('layouts.app')

@section('template_title')
    Job Batches
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                {{ __('Job Batches') }}
                            </span>

                             <div class="float-right">
                                <a href="{{ route('job-batches.create') }}" class="btn btn-primary btn-sm float-right"  data-placement="left">
                                  {{ __('Create New') }}
                                </a>
                              </div>
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success m-4">
                            <p>{{ $message }}</p>
                        </div>
                    @endif

                    <div class="card-body bg-white">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>No</th>
                                        
									<th >Name</th>
									<th >Total Jobs</th>
									<th >Pending Jobs</th>
									<th >Failed Jobs</th>
									<th >Failed Job Ids</th>
									<th >Options</th>
									<th >Cancelled At</th>
									<th >Finished At</th>

                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($jobBatches as $jobBatch)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            
										<td >{{ $jobBatch->name }}</td>
										<td >{{ $jobBatch->total_jobs }}</td>
										<td >{{ $jobBatch->pending_jobs }}</td>
										<td >{{ $jobBatch->failed_jobs }}</td>
										<td >{{ $jobBatch->failed_job_ids }}</td>
										<td >{{ $jobBatch->options }}</td>
										<td >{{ $jobBatch->cancelled_at }}</td>
										<td >{{ $jobBatch->finished_at }}</td>

                                            <td>
                                                <form action="{{ route('job-batches.destroy', $jobBatch->id) }}" method="POST">
                                                    <a class="btn btn-sm btn-primary " href="{{ route('job-batches.show', $jobBatch->id) }}"><i class="fa fa-fw fa-eye"></i> {{ __('Show') }}</a>
                                                    <a class="btn btn-sm btn-success" href="{{ route('job-batches.edit', $jobBatch->id) }}"><i class="fa fa-fw fa-edit"></i> {{ __('Edit') }}</a>
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); confirm('Are you sure to delete?') ? this.closest('form').submit() : false;"><i class="fa fa-fw fa-trash"></i> {{ __('Delete') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {!! $jobBatches->withQueryString()->links() !!}
            </div>
        </div>
    </div>
@endsection
