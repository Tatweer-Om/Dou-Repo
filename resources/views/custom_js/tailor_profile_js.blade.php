<script>
function tailorProfile() {
    return {
        tab: 'special_orders',
        selectedMaterial: false,
        selectedMaterialName: '',
        materialUnit: '',
        materialCategory: '',
        quantityLabel: '',
        materials: [],
        filteredMaterials: [],
        showMaterialDropdown: false,
        materialSearch: '',
        init() {
            this.loadMaterials();
        },
        loadMaterials() {
            $.get("{{ route('materials.all') }}", (data) => {
                this.materials = data || [];
                this.filteredMaterials = this.materials;
                let select = $('#material_select');
                select.empty();
                select.append('<option value="">{{ trans("messages.choose_material", [], session("locale")) }}</option>');
                this.materials.forEach(material => {
                    const categoryLabel = this.getCategoryLabel(material.category);
                    select.append(`<option value="${material.id}" data-category="${material.category || ''}">${material.material_name}${material.category ? ' (' + categoryLabel + ')' : ''}</option>`);
                });
            }).fail(() => {
                console.error('Error loading materials');
                this.materials = [];
                this.filteredMaterials = [];
            });
        },
        filterMaterials() {
            const search = (this.materialSearch || '').toLowerCase().trim();
            if (!search) {
                this.filteredMaterials = this.materials;
            } else {
                this.filteredMaterials = this.materials.filter(m => {
                    const nameMatch = (m.material_name || '').toLowerCase().includes(search);
                    const categoryMatch = (this.getCategoryLabel(m.category) || '').toLowerCase().includes(search);
                    const unitMatch = (m.unit || '').toLowerCase().includes(search);
                    return nameMatch || categoryMatch || unitMatch;
                });
            }
            // Show dropdown if there are filtered results
            if (this.filteredMaterials.length > 0) {
                this.showMaterialDropdown = true;
            }
        },
        selectMaterial(material) {
            $('#material_select').val(material.id);
            this.materialSearch = material.material_name;
            this.showMaterialDropdown = false;
            this.onMaterialSelect();
        },
        onMaterialSelect() {
            const materialId = $('#material_select').val();
            if (materialId) {
                $.get("{{ url('materials') }}/" + materialId, (response) => {
                    if (response.status === 'success') {
                        const material = response.material;
                        $('#material_id').val(material.id);
                        this.selectedMaterial = true;
                        this.selectedMaterialName = material.material_name || '';
                        this.materialUnit = material.unit || '-';
                        this.materialCategory = material.category || '-';
                        
                        // Set quantity label based on unit
                        if (material.unit === 'roll') {
                            this.quantityLabel = '{{ trans("messages.how_many_rolls", [], session("locale")) }}';
                        } else if (material.unit === 'meter') {
                            this.quantityLabel = '{{ trans("messages.how_many_meters", [], session("locale")) }}';
                        } else if (material.unit === 'piece') {
                            this.quantityLabel = '{{ trans("messages.how_many_pieces", [], session("locale")) }}';
                        } else {
                            this.quantityLabel = '{{ trans("messages.quantity", [], session("locale")) }}';
                        }
                    }
                }).fail(() => {
                    show_notification('error', '{{ trans("messages.error_loading_material", [], session("locale")) }}');
                });
            } else {
                this.selectedMaterial = false;
                this.selectedMaterialName = '';
                this.materialSearch = '';
                $('#material_id').val('');
            }
        },
        getCategoryLabel(category) {
            if (!category) return '-';
            const categories = {
                'fabric': '{{ trans("messages.fabric", [], session("locale")) }}',
                'embroidery': '{{ trans("messages.embroidery", [], session("locale")) }}',
                'accessories': '{{ trans("messages.accessories", [], session("locale")) }}'
            };
            return categories[category] || category;
        }
    }
}

// Form submission
$(document).ready(function() {
    $('#send_material_form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Form submitted');
        
        // Get values
        let material_id = $('#material_id').val();
        let material_select = $('#material_select').val(); // Fallback to select value
        let quantity = $('#quantity').val();
        let abayas_expected = $('#abayas_expected').val();
        let tailor_id = $('input[name="tailor_id"]').val();

        console.log('Form values:', {
            material_id: material_id,
            material_select: material_select,
            quantity: quantity,
            abayas_expected: abayas_expected,
            tailor_id: tailor_id
        });

        // Use material_select if material_id is empty (fallback)
        if (!material_id && material_select) {
            material_id = material_select;
            $('#material_id').val(material_select);
        }

        // Validation
        if (!material_id) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ trans("messages.warning", [], session("locale")) }}',
                    text: '{{ trans("messages.please_select_material", [], session("locale")) }}',
                    confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                });
            } else {
                show_notification('error', '{{ trans("messages.please_select_material", [], session("locale")) }}');
            }
            return false;
        }

        if (!quantity || quantity <= 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ trans("messages.warning", [], session("locale")) }}',
                    text: '{{ trans("messages.please_enter_quantity", [], session("locale")) }}',
                    confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                });
            } else {
                show_notification('error', '{{ trans("messages.please_enter_quantity", [], session("locale")) }}');
            }
            return false;
        }

        if (!abayas_expected || abayas_expected < 1) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: '{{ trans("messages.warning", [], session("locale")) }}',
                    text: '{{ trans("messages.please_enter_abayas_expected", [], session("locale")) }}',
                    confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                });
            } else {
                show_notification('error', '{{ trans("messages.please_enter_abayas_expected", [], session("locale")) }}');
            }
            return false;
        }

        // Prepare form data
        let formData = {
            tailor_id: tailor_id,
            material_id: material_id,
            quantity: parseFloat(quantity),
            abayas_expected: parseInt(abayas_expected),
            _token: '{{ csrf_token() }}'
        };

        console.log('Sending AJAX request with data:', formData);

        // Disable submit button to prevent double submission
        const submitBtn = $('#send_material_form button[type="submit"]');
        const originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>{{ trans("messages.saving", [], session("locale")) }}...');

        // Make AJAX request
        $.ajax({
            url: "{{ route('send_material_to_tailor') }}",
            type: "POST",
            data: formData,
            dataType: 'json',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            success: function(response) {
                console.log('Success response:', response);
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                if(response && response.status === 'success') {
                    // Show SweetAlert success message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: '{{ trans("messages.success", [], session("locale")) }}',
                            text: response.message || '{{ trans("messages.material_sent_successfully", [], session("locale")) }}',
                            confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}',
                            timer: 2000,
                            timerProgressBar: true,
                            showConfirmButton: true
                        }).then((result) => {
                            // Reset form
                            $('#send_material_form')[0].reset();
                            $('#material_id').val('');
                            $('#material_select').val('');
                            
                            // Reset Alpine.js state
                            const component = Alpine.$data(document.querySelector('[x-data="tailorProfile()"]'));
                            if (component) {
                                component.selectedMaterial = false;
                                component.selectedMaterialName = '';
                                component.materialUnit = '';
                                component.materialCategory = '';
                                component.quantityLabel = '';
                                component.showMaterialDropdown = false;
                                component.materialSearch = '';
                            }
                            
                            // Reload page to show updated materials sent history
                            window.location.reload();
                        });
                    } else {
                        show_notification('success', response.message || '{{ trans("messages.material_sent_successfully", [], session("locale")) }}');
                        $('#send_material_form')[0].reset();
                        $('#material_id').val('');
                        $('#material_select').val('');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                } else {
                    // Handle unexpected response
                    console.error('Unexpected response:', response);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: '{{ trans("messages.warning", [], session("locale")) }}',
                            text: response.message || '{{ trans("messages.generic_error", [], session("locale")) }}',
                            confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                        });
                    } else {
                        show_notification('error', response.message || '{{ trans("messages.generic_error", [], session("locale")) }}');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseJSON,
                    statusCode: xhr.status
                });
                
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                let errorMessage = '{{ trans("messages.generic_error", [], session("locale")) }}';
                
                if(xhr.status === 422) {
                    let errors = xhr.responseJSON?.errors || {};
                    errorMessage = xhr.responseJSON?.message || '';
                    
                    // If validation errors, show first error or all errors
                    if (Object.keys(errors).length > 0) {
                        const firstError = Object.values(errors)[0];
                        errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 500) {
                    errorMessage = '{{ trans("messages.server_error", [], session("locale")) }}';
                } else if (xhr.status === 0) {
                    errorMessage = '{{ trans("messages.network_error", [], session("locale")) }}';
                }
                
                // Show SweetAlert error
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ trans("messages.error", [], session("locale")) }}',
                        text: errorMessage,
                        confirmButtonText: '{{ trans("messages.ok", [], session("locale")) }}'
                    });
                } else {
                    show_notification('error', errorMessage);
                }
            }
        });
        
        return false;
    });
});
</script>