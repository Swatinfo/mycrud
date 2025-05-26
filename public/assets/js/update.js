// $.ajaxSetup({
//      headers: {
//           'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
//      }
// });
$(document).on('click', '.update', function (e) {
     e.preventDefault();
     if ($("#form").valid()) {
          var route = $(this).data('route');
          var formData = new FormData($("#form")[0]);
          for (var pair of formData.entries()) {
               console.log(pair[0] + ": " + pair[1]);
          }
          $.ajax({
               type: 'post',
               url: route,
               data: formData,
               dataType: 'json',
               cache: false,
               contentType: false,
               processData: false,
               headers: {
                    'X-HTTP-Method-Override': 'PUT',
               },
               beforeSend: function () {
                    $(".invalid-feedback").html('');
                    $(".custom-error").html('');
                    $(".save").html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Wait`);
                    $(".save").attr('disabled', true);
               },
               success: function (response) {
                    $(".save").html("Submit");
                    $(".save").attr('disabled', false);
                    if (response.status_code == 500) {
                         Swal.fire({ title: "Error", text: response.message, icon: 'error' })
                    } else if (response.status_code == 403) {
                         Swal.fire({ title: "Warning", text: response.message, icon: 'warning' })
                    } else if (response.status_code == 201) {
                         $.each(response.errors, function (key, value) {
                              if (key.indexOf('.') !== -1) {
                                   $('#error_' + key.replace(/\./g, '_')).html('<p class="text-danger mb-0">' + value + '</p>');
                              } else {
                                   $('#error_' + key).html('<p class="text-danger mb-0">' + value + '</p>');
                              }
                         });
                         // Swal.fire({ title: "Warning", text: response.message, icon: 'warning' })
                    } else {
                         $('#form')[0].reset();
                         $("#inlineModal").modal('hide');
                         table.ajax.reload(null, true);
                         Swal.fire({ title: "Success", text: response.message, icon: 'success', showCancelButton: false })
                    }
               }
          });
     } else {
          return false;
     }
});

$('.table-responsive').on('show.bs.dropdown', function () {
     $('.table-responsive').css("overflow", "inherit");
});

$('.table-responsive').on('hide.bs.dropdown', function () {
     $('.table-responsive').css("overflow", "auto");
})