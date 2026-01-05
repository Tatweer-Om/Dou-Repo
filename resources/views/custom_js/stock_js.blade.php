<script>
$(document).ready(function() {
    $('#add_material').on('submit', function(e) {
        e.preventDefault();

        // --- Manual validations ---
        let material_name     = $('#material_name').val().trim();
        let material_unit     = $('#material_unit').val();
        let material_category = $('#material_category').val();

        if (!material_name) {
            show_notification('error', '<?= trans("messages.enter_material_name", [], session("locale")) ?>');
            return;
        }

        if (!material_unit) {
            show_notification('error', '<?= trans("messages.enter_material_unit", [], session("locale")) ?>');
            return;
        }

        if (!material_category) {
            show_notification('error', '<?= trans("messages.enter_material_category", [], session("locale")) ?>');
            return;
        }

        // --- FormData (includes file) ---
        let formData = new FormData(this); // make sure <form> has enctype="multipart/form-data"

        $.ajax({
            url: "{{ route('add_material') }}",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            beforeSend: function() {
                // optional loader
            },
            success: function(response) {
                if(response.status === 'success') {
                    show_notification('success', response.message);
                    $('#add_material')[0].reset();
                    $('#imagePreview').attr('src','').hide();
                    // Redirect to material list
                    if (response.redirect_url) {
                        setTimeout(function() {
                            window.location.href = response.redirect_url;
                        }, 1000);
                    }
                }
            },
            error: function(xhr) {
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        show_notification('error', value[0]); // show first validation error
                    });
                } else {
                    show_notification('error', '<?= trans("messages.something_went_wrong", [], session("locale")) ?: "Something went wrong!" ?>');
                }
            }
        });
    });

      $('#abaya_form').on('submit', function(e) {
    e.preventDefault();

    let $form = $(this);
    let $submitBtn = $form.find('button[type="submit"]');
    
    // Check if already submitting
    if ($submitBtn.prop('disabled')) {
        return false;
    }

    let abaya_code = $('#abaya_code').val().trim();
    let barcode = $('#barcode').val().trim();
    let design_name = $('#design_name').val();
let tailor = $('input[name="tailor_id[]"]:checked').map(function() {
    return $(this).val();
}).get();

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
    if (!tailor) {
        show_notification('error', '<?= trans("messages.enter_tailor", [], session("locale")) ?>');
        return;
    }

    // Store original button text and disable button
    let originalBtnText = $submitBtn.html();
    $submitBtn.prop('disabled', true).css('opacity', '0.6').html('<?= trans("messages.processing", [], session("locale")) ?>...');

    let formData = new FormData(this);
    
    // Remove the images[] entries that FormData automatically added
    formData.delete('images[]');

    // Only append files from Alpine.js images array (files that haven't been removed)
    if (window.imageUploaderComponent && window.imageUploaderComponent.images && window.imageUploaderComponent.images.length > 0) {
        window.imageUploaderComponent.images.forEach(function(image) {
            if (image.file) {
                formData.append('images[]', image.file);
            }
        });
    } else {
        // Fallback: if Alpine component not found, use all files from input
        let imageInput = document.querySelector('#images');
        if (imageInput && imageInput.files) {
            for (let i = 0; i < imageInput.files.length; i++) {
                formData.append('images[]', imageInput.files[i]);
            }
        }
    }

    $.ajax({
        url: "{{ route('add_stock') }}",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        beforeSend: function() {
        },
        success: function(response) {
            if (response.status === 'success') {
                show_notification('success', '<?= trans("messages.stock_added_successfully", [], session("locale")) ?: "Stock added successfully!" ?>');
                $('#abaya_form')[0].reset();
                // Clear Alpine images array and file input
                if (window.imageUploaderComponent) {
                    window.imageUploaderComponent.images = [];
                }
                let imageInput = document.querySelector('#images');
                if (imageInput) {
                    imageInput.value = '';
                }
                // Redirect to stock list
                if (response.redirect_url) {
                    setTimeout(function() {
                        window.location.href = response.redirect_url;
                    }, 1000);
                }
            } else {
                // Re-enable button on unexpected response
                $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
            }
        },
        error: function(xhr) {
            // Re-enable button on error
            $submitBtn.prop('disabled', false).css('opacity', '1').html(originalBtnText);
            
            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    show_notification('error', value[0]); 
                });
            } else {
                show_notification('error', '<?= trans("messages.something_went_wrong", [], session("locale")) ?: "Something went wrong!" ?>');
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
        images: [],
        init() {
            // Store reference to this component globally for form submission
            window.imageUploaderComponent = this;
        },
        handleFiles(event) {
            const files = event.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.images.push({ file: file, url: e.target.result });
                };
                reader.readAsDataURL(file);
            }
        },
        removeImage(index) {
            // Remove the image from the array
            this.images.splice(index, 1);
        }
    };
}






</script>
