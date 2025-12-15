<script>
    // Store current page globally
    var currentStockPage = 1;

    // Translations
    const trans = {
        details: "تفاصيل",
        enter_quantity: "إدخال كميات",
        edit: "تعديل",
        delete: "حذف"
    };

    // Make loadStock globally accessible
    function loadStock(page = 1) {
        currentStockPage = page || currentStockPage || 1;
            $.get("/stock/list", {
                page: page
            }, function(res) {

                // --- Desktop Table ---
                let tableRows = "";
                $.each(res.data, function(index, stock) {
                    let image = stock.images.length ? stock.images[0].image_path : '';

                  // Get first color_size combination if available
                    let size = '-';
                    let color = '-';
                    let quantity = 0;
                    
                    if (stock.color_sizes && stock.color_sizes.length > 0) {
                        const first = stock.color_sizes[0];
                        size = first.size ? (first.size.size_name_ar || first.size.size_name_en || '-') : '-';
                        color = first.color ? (first.color.color_name_ar || first.color.color_name_en || '-') : '-';
                        // Calculate total quantity from all color_sizes
                        quantity = stock.color_sizes.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
                    }

                    const categoryName = stock.category ? stock.category.category_name : '-';
                    
                    tableRows += `
                <tr class="border-t hover:bg-pink-50/60 transition" data-id="${stock.id}">
                    <td class="px-3 py-3">
                        <img src="${image}" class="w-12 h-16 object-cover rounded-md" />
                    </td>
                    <td class="px-3 py-3 font-bold">${stock.abaya_code}</td>
                    <td class="px-3 py-3">${stock.design_name ?? '-'}</td>
                    <td class="px-3 py-3">${categoryName}</td>
                    <td class="px-3 py-3">${size}</td>
                    <td class="px-3 py-3">${color}</td>
                    <td class="px-3 py-3 font-bold">${quantity}</td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex justify-center gap-5 text-[12px] font-semibold text-gray-700">
                           <button class="flex flex-col items-center gap-1 hover:text-purple-600 transition"
                                    onclick="openFullStockDetails(${stock.id})">
                            <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>
                                ${trans.details}
                            </button>
                        <button class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition d-none"
                                x-on:click="$dispatch('open-stock-details', ${stock.id})">
                            <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>
                            ${trans.details}
                        </button>
                                <button class="openQuantityBtn flex flex-col items-center"
                                data-bs-toggle="modal"
                                data-bs-target="#quantityModal"
                                onclick="openStockQuantity(${stock.id})">
                            <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                            Enter Quantity
                        </button>


                            <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition"
                                    onclick="window.location.href='/edit_stock/${stock.id}'">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                                ${trans.edit}
                            </button>

                            <button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-stock-btn">
                            <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                            ${trans.delete}
                        </button>
                        </div>
                    </td>
                </tr>`;
                });
                $("#desktop_stock_body").html(tableRows);

                // --- Mobile Cards ---
                let mobileCards = '';
                $.each(res.data, function(index, stock) {
                    let image = stock.images.length ? stock.images[0].image_path : '';
                    // Get first color_size combination if available
                    let size = '-';
                    let color = '-';
                    let quantity = 0;
                    
                    if (stock.color_sizes && stock.color_sizes.length > 0) {
                        const first = stock.color_sizes[0];
                        size = first.size ? (first.size.size_name_ar || first.size.size_name_en || '-') : '-';
                        color = first.color ? (first.color.color_name_ar || first.color.color_name_en || '-') : '-';
                        // Calculate total quantity from all color_sizes
                        quantity = stock.color_sizes.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
                    }

                    const categoryName = stock.category ? stock.category.category_name : '-';
                    
                    mobileCards += `
                <div class="bg-white border border-pink-100 rounded-2xl shadow-sm hover:shadow-md transition p-4 mb-4 md:hidden">
                    <h3 class="font-bold text-gray-900 truncate">${stock.abaya_code}</h3>
                    <p>تصميم: ${stock.design_name ?? '-'}</p>
                    <p>الفئة: ${categoryName}</p>
                    <p>المقاس: ${size}</p>
                    <p>اللون: ${color}</p>
                    <p>الكمية: ${quantity}</p>
                    <div class="flex justify-around mt-3 text-xs font-semibold">
                    <button class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition d-none"
                            @click="$store.modals.showDetails = true">
                        <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>
                        تفاصيل
                    </button>
                        <button class="flex flex-col items-center gap-1 hover:text-purple-600 transition"
                                onclick="openFullStockDetails(${stock.id})">
                        <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>
                            ${trans.details}
                        </button>
                        <button class="flex flex-col items-center gap-1 hover:text-green-600 transition"
                            <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                            ${trans.enter_quantity}
                        </button>

                    

                        <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition"
                                onclick="window.location.href='/edit_stock/${stock.id}'">
                            <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                            ${trans.edit}
                        </button>

                        <button class="flex flex-col items-center gap-1 hover:text-red-600 transition"
                                onclick="alert('Delete stock ID: ${stock.id}')">
                            <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                            ${trans.delete}
                        </button>
                    </div>
                </div>`;
                });
                $("#mobile_stock_cards").html(mobileCards);

                // --- Pagination ---
                let pagination = "";

                // Previous Button
                pagination += `
<li class="flex">
    <a href="${res.prev_page_url || '#'}"
       class="px-3 py-1 border rounded-lg mx-1 text-sm transition
              ${!res.prev_page_url 
                ? 'opacity-40 cursor-not-allowed bg-gray-200' 
                : 'bg-white hover:bg-gray-100'}">
        &laquo;
    </a>
</li>`;

                // Number Buttons
                for (let i = 1; i <= res.last_page; i++) {
                    pagination += `
    <li>
        <a href="/stock/list?page=${i}"
           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition
                  ${res.current_page == i 
                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                    : 'bg-white hover:bg-gray-100'}">
            ${i}
        </a>
    </li>`;
                }

                // Next Button
                pagination += `
<li class="flex">
    <a href="${res.next_page_url || '#'}"
       class="px-3 py-1 border rounded-lg mx-1 text-sm transition
              ${!res.next_page_url 
                ? 'opacity-40 cursor-not-allowed bg-gray-200' 
                : 'bg-white hover:bg-gray-100'}">
        &raquo;
    </a>
</li>`;

                $("#stock_pagination").html(pagination);

            });
    }

    $(document).ready(function() {
        // Pagination click
        $(document).on("click", "#stock_pagination a", function(e) {
            e.preventDefault();
            let href = $(this).attr("href");
            if (href && href !== "#") {
                let page = new URL(href, window.location.origin).searchParams.get("page");
                if (page) loadStock(page);
            }
        });

        // Client-side search
        $("#stock_search").on("keyup", function() {
            let search = $(this).val().toLowerCase();
            $("#desktop_stock_body tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(search) > -1);
            });
            $("#mobile_stock_cards > div").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(search) > -1);
            });
        });

        // Initial load
        loadStock();
    });


function stockDetails() {
    return {
        loading: false,
        showDetails: false,
        stock: null, // raw stock data

        openStockDetails(id) {
            this.loading = true;
            this.showDetails = true;
            this.stock = null;

            $.ajax({
                url: '{{ url("stock_detail") }}',
                method: 'GET',
                data: { id: id },
                success: (response) => {
                    if (response) {
                        this.stock = response; // <-- directly use response

                        $('#abaya_code').text(this.stock.abaya_code || '-');
                        $('#design_name').text(this.stock.design_name || '-');
                        $('#barcode').text(this.stock.barcode || '-');
                        $('#description').text(this.stock.abaya_notes || '-');
                        $('#size_container').html(this.stock.sizes_html || '-');
                    $('#size_color_container').html(this.stock.size_color_html || '-');
                                            $('#color_container').html(this.stock.color || '-');

                        $('#status').text(this.stock.status || 'Available');

                        $('#stock_main_image').attr('src', this.stock.image_path || '/images/placeholder.png');
                    }
                    this.loading = false;
                },
                error: (err) => {
                    console.error('Error:', err);
                    alert('فشل تحميل التفاصيل');
                    this.loading = false;
                    this.showDetails = false;
                }
            });
        }
    }
}



function openStockQuantity(stockId) {

    // Show the modal
    const modalEl = document.getElementById('quantityModal');
    const bsModal = new bootstrap.Modal(modalEl);
    
    // Set stock_id in the form before showing modal
    $('#stock_id').val(stockId);
    
    // Reset form state
    $('#save_qty')[0].reset();
    $('#stock_id').val(stockId); // Set again after reset
    $('input[name="qtyType"][value="add"]').prop('checked', true);
    
    // Re-enable submit button and restore original text
    var submitBtn = $('#save_qty').find('button[type="submit"]');
    submitBtn.prop('disabled', false);
    var originalBtnText = '<i class="bi bi-check2-all me-2"></i>{{ trans("global.save_operation", [], session("locale")) }}';
    submitBtn.html(originalBtnText);
    
    // Ensure modal structure is visible
    $('#quantityModal .modal-footer').css('display', 'flex');
    $('#quantityModal .modal-body').css('overflow-y', 'auto');
    
    bsModal.show();

    // Show loading state if you have a loader
    const loader = document.getElementById('quantityLoader');
    if (loader) loader.style.display = 'block';

    // Fetch stock quantity data via AJAX
    $.ajax({
        url: '{{ url("get_stock_quantity") }}',
        method: 'GET',
        data: { id: stockId },
        success: function(response) {
            if (response) {
                // Inject HTML into modal
                $('#sizecont').html(response.sizes_html || '');
                $('#colorsize_container').html(response.size_color_html || '');
                $('#colorcont').html(response.color || '');
            }
            
            // Ensure footer is visible after content loads
            $('#quantityModal .modal-footer').css('display', 'flex');
        },
        error: function(err) {
            console.error('Error:', err);
            alert('فشل تحميل التفاصيل');
            bsModal.hide(); // Close modal on error
        },
        complete: function() {
            // Hide loader
            if (loader) loader.style.display = 'none';
            
            // Ensure modal structure remains intact
            $('#quantityModal .modal-footer').css('display', 'flex');
        }
    });
}



    $(document).on('click', '.delete-stock-btn', function() {
        let id = $(this).closest('tr').data('id');

        Swal.fire({
            title: '<?= trans("messages.confirm_delete_title", [], session("locale")) ?>',
            text: '<?= trans("messages.confirm_delete_text", [], session("locale")) ?>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= trans("messages.yes_delete", [], session("locale")) ?>',
            cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({
                    url: '<?= url("delete_stock") ?>/' + id,
                    method: 'DELETE',
                    data: {
                        _token: '<?= csrf_token() ?>'
                    },

                    success: function(data) {
                        loadStock(); // reload table or redirect
                        Swal.fire(
                            '<?= trans("messages.deleted_success", [], session("locale")) ?>',
                            '<?= trans("messages.deleted_success_text", [], session("locale")) ?>',
                            'success'
                        );
                    },

                    error: function() {
                        Swal.fire(
                            '<?= trans("messages.delete_error", [], session("locale")) ?>',
                            '<?= trans("messages.delete_error_text", [], session("locale")) ?>',
                            'error'
                        );
                    }
                });

            }
        });
    });


$(document).ready(function() {
    $('#save_qty').on('submit', function(e) {
        e.preventDefault(); // prevent default form submit
        e.stopPropagation(); // prevent event bubbling

        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var cancelBtn = form.find('button[data-bs-dismiss="modal"]');
        var modal = $('#quantityModal');
        var modalBody = modal.find('.modal-body');
        var modalFooter = modal.find('.modal-footer');
        
        // Disable submit button to prevent double submission
        submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i>جاري الحفظ...');
        
        // Ensure modal stays open and visible
        modal.css('display', 'block').addClass('show');
        modalFooter.css('display', 'flex');
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('submitBtn').addEventListener('click', function() {
        const actionTypeEl = document.querySelector('input[name="qtyType"]:checked');
        if (!actionTypeEl) return;

        const actionType = actionTypeEl.value;
        const pullTextarea = document.getElementById('pull_reason');

        // Only check if textarea exists
        const pullReason = pullTextarea ? pullTextarea.value.trim() : '';

        if (actionType === 'pull' && pullReason === '') {
            show_notification('error', 'Please enter pull notes!');
            return;
        }

        console.log('Action Type:', actionType);
        console.log('Pull Reason:', pullReason);
    });
});

function show_notification(type, message) {
    alert(`${type.toUpperCase()}: ${message}`);
}

        // Maintain scroll position
        var scrollTop = modalBody.scrollTop();
        
        var formData = form.serialize(); // serialize form fields
        formData += '&stock_id=' + $('#stock_id').val();
        formData += '&actionType=' + $('input[name="qtyType"]:checked').val(); // include add/pull

        $.ajax({
            url: "{{ route('add_quantity') }}", // your Laravel route
            type: 'POST',
            data: formData,
         
            success: function(response) {
                // Restore scroll position
                modalBody.scrollTop(scrollTop);
                
              if (response.status === "success") {
    // Re-enable submit button before closing modal
    var originalBtnText = '<i class="bi bi-check2-all me-2"></i>{{ trans("global.save_operation", [], session("locale")) }}';
    submitBtn.prop('disabled', false).html(originalBtnText);
    
    Swal.fire({
        icon: 'success',
        title: 'نجح!',
        text: response.message,
        timer: 2000,
        showConfirmButton: false
    });

    // Reload stock list with current page
    if (typeof loadStock === 'function') {
        // Reload the current page to show updated quantities
        loadStock(currentStockPage);
    } else {
        // Fallback: reload the page
        location.reload();
    }

    // Close modal
    setTimeout(() => {
        var bsModal = bootstrap.Modal.getInstance(modal[0]);
        if (bsModal) {
            bsModal.hide();
        } else {
            modal.modal('hide');
        }
    }, 500);

} else {

    Swal.fire({
        icon: 'error',
        title: 'خطأ!',
        text: response.message || 'حدث خطأ ما'
    });

    submitBtn.prop('disabled', false).html(
        '<i class="bi bi-check2-all me-2"></i>{{ trans("global.save_operation", [], session("locale")) }}'
    );
}

            },
            error: function(xhr, status, error) {
                // Restore scroll position
                modalBody.scrollTop(scrollTop);
                
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                
                var errorMsg = 'حدث خطأ أثناء الحفظ';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ!',
                    text: errorMsg
                });
                
                // Re-enable submit button
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-all me-2"></i>{{ trans("global.save_operation", [], session("locale")) }}');
            },
            complete: function() {
                // Ensure modal remains visible and buttons are shown
                modal.css('display', 'block').addClass('show');
                modalFooter.css('display', 'flex');
            }
        });
        
        return false; // Additional prevention
    });
    
    // Ensure button is enabled when modal is shown
    $('#quantityModal').on('show.bs.modal', function() {
        var submitBtn = $('#save_qty').find('button[type="submit"]');
        submitBtn.prop('disabled', false);
        var originalBtnText = '<i class="bi bi-check2-all me-2"></i>{{ trans("global.save_operation", [], session("locale")) }}';
        submitBtn.html(originalBtnText);
    });
    
    // Ensure button is enabled when modal is hidden (in case it was left disabled)
    $('#quantityModal').on('hidden.bs.modal', function() {
        var submitBtn = $('#save_qty').find('button[type="submit"]');
        submitBtn.prop('disabled', false);
        var originalBtnText = '<i class="bi bi-check2-all me-2"></i>{{ trans("global.save_operation", [], session("locale")) }}';
        submitBtn.html(originalBtnText);
    });
});
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Full Stock Details Modal Function
function openFullStockDetails(stockId) {
    const modal = new bootstrap.Modal(document.getElementById('fullStockDetailsModal'));
    const loader = document.getElementById('fullStockDetailsLoader');
    const body = document.getElementById('fullStockDetailsBody');

    // Show modal and loader
    modal.show();
    loader.classList.remove('d-none');
    body.classList.add('d-none');

    // Clear previous data
    $('#full_modal_abaya_code').text('...');
    $('#full_abaya_code').text('-');
    $('#full_design_name').text('-');
    $('#full_barcode').text('-');
    $('#full_description').text('-');
    $('#full_cost_price').text('-');
    $('#full_sales_price').text('-');
    $('#full_tailor_charges').text('-');
    $('#full_tailor_names').text('-');
    $('#full_total_quantity').text('0');
    $('#full_stock_images_container').html('');
    $('#full_size_color_container').html('');

    // Fetch full stock details
    $.ajax({
        url: '{{ url("get_full_stock_details") }}',
        method: 'GET',
        data: { id: stockId },
        success: function(response) {
            if (response) {
                // Populate basic info
                $('#full_abaya_code').text(response.abaya_code || '-');
                $('#full_modal_abaya_code').text(response.abaya_code || '...');
                $('#full_design_name').text(response.design_name || '-');
                $('#full_barcode').text(response.barcode || '-');
                $('#full_description').text(response.abaya_notes || '-');

                // Pricing info
                const costPrice = response.cost_price ? parseFloat(response.cost_price).toFixed(2) : '0.00';
                const salesPrice = response.sales_price ? parseFloat(response.sales_price).toFixed(2) : '0.00';
                const tailorCharges = response.tailor_charges ? parseFloat(response.tailor_charges).toFixed(2) : '0.00';
                
                $('#full_cost_price').text(costPrice);
                $('#full_sales_price').text(salesPrice);
                $('#full_tailor_charges').text(tailorCharges);

                // Tailor names
                const tailorNames = response.tailor_names && response.tailor_names.length > 0 
                    ? response.tailor_names.join(', ') 
                    : '-';
                $('#full_tailor_names').text(tailorNames);

                // Total quantity
                $('#full_total_quantity').text(response.total_quantity || 0);

                // Populate images
                if (response.images && response.images.length > 0) {
                    let imagesHtml = '';
                    response.images.forEach(function(imagePath, index) {
                        imagesHtml += `
                            <div class="col-md-4 col-lg-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-img-top position-relative" style="height: 200px; overflow: hidden;">
                                        <img src="${imagePath}" 
                                             class="w-100 h-100 object-cover" 
                                             alt="Stock Image ${index + 1}"
                                             onerror="this.src='/images/placeholder.png'">
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#full_stock_images_container').html(imagesHtml);
                } else {
                    $('#full_stock_images_container').html(`
                        <div class="col-12">
                            <p class="text-muted text-center">{{ trans('messages.no_images_available', [], session('locale')) }}</p>
                        </div>
                    `);
                }

                // Populate color-size combinations
                if (response.color_size_details && response.color_size_details.length > 0) {
                    let colorSizeHtml = '';
                    response.color_size_details.forEach(function(item) {
                        colorSizeHtml += `
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="mb-1 fw-bold text-gray-900">${item.size_name}</h6>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle border border-2" 
                                                         style="width: 24px; height: 24px; background-color: ${item.color_code};"></div>
                                                    <span class="text-muted small">${item.color_name}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary fs-6 px-3 py-2">${item.quantity} {{ trans('messages.pieces', [], session('locale')) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    $('#full_size_color_container').html(colorSizeHtml);
                } else {
                    $('#full_size_color_container').html(`
                        <div class="col-12">
                            <p class="text-muted text-center">{{ trans('messages.no_data_available', [], session('locale')) }}</p>
                        </div>
                    `);
                }
            }

            // Hide loader and show body
            loader.classList.add('d-none');
            body.classList.remove('d-none');
        },
        error: function(err) {
            console.error('Error:', err);
            alert('{{ trans("messages.error_loading_details", [], session("locale")) }}');
            loader.classList.add('d-none');
            body.classList.remove('d-none');
        }
    });
}
</script>