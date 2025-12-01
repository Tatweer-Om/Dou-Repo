<script>
 $(document).ready(function() {
    $('.delete-image').click(function() {
        let imageId = $(this).data('id');
        let token = "{{ csrf_token() }}";

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/stock/image/' + imageId,
                    type: 'DELETE',
                    data: {
                        _token: token
                    },
                    success: function(response) {
                        // Remove image div from DOM
                        $('#image-' + imageId).remove();

                        Swal.fire(
                            'Deleted!',
                            response.message,
                            'success'
                        );
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'Something went wrong while deleting the image.',
                            'error'
                        );
                    }
                });
            }
        });
    });

  

$('#update_abaya').on('submit', function(e) {
    e.preventDefault();

    let abaya_code = $('#abaya_code').val().trim();
    let barcode = $('#barcode').val().trim();
    let design_name = $('#design_name').val();
    let tailor = $('#tailor_id').val();

    if (!abaya_code) {
        show_notification('error', '<?= trans("messages.enter_abaya_code", [], session("locale")) ?>');
        return;
    }
    if (!barcode) {
        show_notification('error', '<?= trans("messages.enter_barcode", [], session("locale")) ?>');
        return;
    }
    if (!design_name) {
        show_notification('error', '<?= trans("messages.enter_design_name", [], session("locale")) ?>');
        return;
    }
  

    let formData = new FormData(this);

    // Manually append files from Alpine.js
  

    $.ajax({
        url: "{{ route('update_stock') }}",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        beforeSend: function() {
        },
        success: function(response) {
            if (response.status === 'success') {
                show_notification('success', 'Stock added successfully!');
                $('#abaya_form')[0].reset();
                // Clear Alpine images array
                if (window.imageUploaderComponent) {
                    window.imageUploaderComponent.images = [];
                }
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    show_notification('error', value[0]); 
                });
            } else {
                show_notification('error', 'Something went wrong!');
            }
        }
    });
});

});


document.getElementById('material_image').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('imagePreview');
        img.src = e.target.result;
        img.classList.remove('hidden');

        // Hide icon and text
        document.getElementById('uploadIcon').style.display = 'none';
        document.getElementById('uploadText').style.display = 'none';
    };
    reader.readAsDataURL(file);
});
function imageUploader() {
    return {
        images: [],  // preview images

        handleFiles(event) {
            const files = event.target.files;

            for (let i = 0; i < files.length; i++) {
                const file = files[i];

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.images.push({
                        file: file,
                        url: e.target.result
                    });
                };
                reader.readAsDataURL(file);
            }
        },

        removeImage(index) {
            this.images.splice(index, 1);
        }
    };
}


</script>