$(document).on('click', '.delete', function () {
     var id = $(this).data('id');
     var me = $(this);
     Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          confirmButtonText: "Yes, delete it!",
          showCancelButton: true,
     }).then(function (result) {
          if (result.value) {
               axios.delete(URL + '/' + id)
                    .then(function (response) {
                         if (response.data.status_code == 200) {
                              toastr.success(response.data.message, "Success");
                              me.parent().parent().hide();
                         } else if (response.data.status_code == 201) {
                              toastr.warning(response.data.message, "Warning");
                         } else {
                              toastr.error(response.data.message, "Error");
                         }
                    })
                    .catch(function () {
                         toastr.error("Something went wrong. Please try again.", "Error");
                    });
          }
     });
});