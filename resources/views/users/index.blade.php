@extends('layouts.app')
@section('title', 'User Profile')
@section('content')
<style>
     .select2-search__field {
          width: auto !important;
          display: inline-block !important;
     }
</style>
<div class="row">
     <div class="col-12">
          <h4 class="content-header-title float-start">{{ __('message.user.userList') }}</h4>
          @can('users-create')
          <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary float-end new-create"><i class="fa fa-plus me-50"></i> {{ __('message.common.addNew') }}</a>
          @endcan
     </div>
     <div class="col-12">
          <div class="card">
               <div class="card-body">
                    <table id="table" class="datatables-basic table table-hover">
                         <thead>
                              <tr>
                                   <th>#</th>
                                   <th>{{ __('message.users.name') }}</th>
                                   <th>{{ __('message.users.mobile') }}</th>
                                   <th>{{ __('message.users.email') }}</th>
                                   <th>{{ __('message.users.userName') }}</th>
                                   <th>{{ __('message.users.department') }}</th>
                                   <th>{{ __('message.common.action') }}</th>
                              </tr>
                         </thead>
                         <tbody>

                         </tbody>
                    </table>
               </div>
          </div>
     </div>
</div>
<div class="modal fade" id="assignUserModal" tabindex="-1" aria-labelledby="assignUserModalLabel">
     <div class="modal-dialog modal-xl">
          <div class="modal-content">
               <div class="modal-header p-2 bg-transparent border-bottom">
                    <h5 class="mb-0">{{ __('message.users.assignUser') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>

               <div class="modal-body" id="assignUsers"></div>
          </div>
     </div>
</div>
@endsection

@section('pagescript')
<script type="application/javascript">
     @if($message = Session::get('error'))
     toastr.error("{{ addslashes($message) }}", "Error");
     @endif

          'use strict';
     const URL = "{{route('users.index')}}";

     var table = '';
     var assignId = '';
     $(function() {
          table = $('#table').DataTable({
               ajax: URL,
               processing: true,
               serverSide: true,
               fixedHeader: false,
               scrollX: true,
               bScrollInfinite: true,
               bScrollCollapse: true,
               sScrollY: "465px",
               aLengthMenu: [
                    [15, 30, 50, 100, -1],
                    [15, 30, 50, 100, "All"]
               ],
               order: [
                    [0, 'asc']
               ],
               columns: [{
                         data: 'id',
                         render: function(data, type, row, meta) {
                              var rowNumber = meta.row + meta.settings._iDisplayStart + 1;
                              var isResponsive = meta.settings.responsive && meta.settings.responsive.details;
                              if (type === 'display' && isResponsive && meta.settings.responsive.details.type === 'column') {
                                   return '';
                              } else {
                                   return rowNumber;
                              }
                         },
                         orderable: false,
                         createdCell: function(td, cellData, rowData, row, col) {
                              var isResponsive = table.responsive.hasHidden();
                              if (isResponsive) {
                                   $(td).addClass('dtr-control');
                              } else {
                                   $(td).removeClass('dtr-control');
                              }
                         }
                    },
                    {
                         data: 'user.name',
                         name: 'user.name'
                    },
                    {
                         data: 'user.mobile',
                         name: 'user.mobile'
                    },
                    {
                         data: 'user.email',
                         name: 'user.email'
                    },
                    {
                         data: 'user.username',
                         name: 'user.username'
                    },
                    {
                         data: 'role',
                         name: 'role'
                    },
                    {
                         data: 'action',
                         name: 'action',
                         orderable: false,
                         sortable: false
                    },
               ],
               initComplete: function(settings, json) {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                         return new bootstrap.Tooltip(tooltipTriggerEl)
                    })
               },
               responsive: {
                    details: {
                         display: $.fn.dataTable.Responsive.display.modal({
                              header: function(t) {
                                   return t.data().user.name
                              }
                         }),
                         type: "column",
                         renderer: function(t, a, e) {
                              e = $.map(e, function(t, a) {
                                   return "" !== t.title ? '<tr data-dt-row="' + t.rowIndex + '" data-dt-column="' + t.columnIndex + '"><td>' + t.title + " :</td> <td>" + t.data + "</td></tr>" : ""
                              }).join("");
                              return !!e && $('<table class="table table-sm"/><tbody />').append(e)
                         }
                    }
               }
          });
     });

     $(document).on('click', '.assignUser', function() {
          assignId = '';
          let id = $(this).data('id');
          $.ajax({
               url: '{{ route("assign-user")}}',
               type: "POST",
               dataType: 'json',
               data: {
                    "_token": "{{ csrf_token() }}",
                    "id": id,
               },
               success: function(response) {
                    if (response.status_code == 200) {
                         assignId = id;
                         $("#assignUsers").empty();
                         $("#assignUsers").html(response.result);
                         $('#child_id').select2({
                              dropdownParent: $('#assignUserModal'),
                              width: "100%",
                              multiple: true,
                              //allowClear: true
                         });
                         $(".select2-search .select2-search__field").attr("placeholder", "Assing child user");
                         $("#assignUserModal").modal('show');
                    } else if (response.status_code == 201) {
                         toastr.warning(response.message, "Warning");
                    } else {
                         toastr.error(response.message, "Error");
                    }
               },
               error: function(error) {
                    toastr.error(error.message, "Error");
               }
          });
     });

     $(document).on('click', '#save', function() {
          var formData = new FormData($("#formUser")[0]);
          formData.append('parent_id', assignId);
          if ($("#formUser").valid()) {
               $.ajax({
                    type: "POST",
                    url: "{{route('assign-user-store')}}",
                    data: formData,
                    dataType: 'json',
                    cache: false,
                    contentType: false,
                    processData: false,
                    beforeSend: function() {
                         $(".invalid-feedback").html(' ');
                         $("#save").html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wait`);
                         $("#save").attr('disabled', true);
                    },
                    success: function(response) {
                         $("#save").html("Submit");
                         $("#save").attr('disabled', false);
                         if (response.status_code == 500) {
                              toastr.error(response.message, "Error");
                         } else if (response.status_code == 403) {
                              toastr.warning(response.message, "Warning");
                         } else if (response.status_code == 201) {
                              $.each(response.errors, function(key, value) {
                                   $('#error_' + key).html('<p class="text-danger mb-0">' + value + '</p>');
                              });
                              toastr.warning(response.message, "Warning");
                         } else {
                              $('#formUser')[0].reset();
                              assignId = '';
                              $("#assignUserModal").modal('hide');
                              toastr.success(response.message, "Success");
                              table.ajax.reload(null, false);
                         }
                    }
               });
          } else {
               return false;
          }
     });

     $(document).on('click', '.delete_tree', function() {
          let id = $(this).data('id');
          let parentId = $(this).data('parent');
          Swal.fire({
                    title: "Are you sure?",
                    text: "You wont be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!",
                    customClass: {
                         confirmButton: 'btn btn-primary',
                         cancelButton: 'btn btn-outline-danger ms-1'
                    },
                    buttonsStyling: false
               })
               .then(function(result) {
                    if (result.value) {
                         $.ajax({
                              type: "POST",
                              url: "{{route('assign-user-delete')}}",
                              dataType: 'json',
                              data: {
                                   "_token": "{{ csrf_token() }}",
                                   "id": id,
                                   "parentId": parentId,
                              },
                              cache: false,
                              success: function(response) {
                                   if (response.status_code == 200) {
                                        $("#assignUserModal").modal('hide');
                                        toastr.success(response.message, "Success");
                                   } else if (response.status_code == 201) {
                                        toastr.warning(response.message, "Warning");
                                   } else {
                                        toastr.error(response.message, "Error");
                                   }
                              },
                              error: function($error){
                                   toastr.error("Something went wrong. Please try again.", "Error");
                              }
                         });
                    } else {
                         Swal.fire({
                              text: "Your data is safe."
                         });
                    }
               });
     });
</script>
<script src="{{asset('assets/custom/delete.js')}}"></script>
@endsection