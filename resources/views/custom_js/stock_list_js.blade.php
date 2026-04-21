<script>
    // Store current page and full stock data (view_stock only: search/filter on overall stock in frontend)
    var currentStockPage = 1;
    var allStockData = [];
    var allStockLoaded = false;
    var perPage = 10;

    // Translations
    const trans = {
        details: "{{ trans('messages.details', [], session('locale')) }}",
        enter_quantity: "{{ trans('messages.enter_quantity', [], session('locale')) }}",
        edit: "{{ trans('messages.edit', [], session('locale')) }}",
        delete: "{{ trans('messages.delete', [], session('locale')) }}",
        design: "{{ trans('messages.design', [], session('locale')) }}",
        category: "{{ trans('messages.category', [], session('locale')) }}",
        size: "{{ trans('messages.size', [], session('locale')) }}",
        color: "{{ trans('messages.color', [], session('locale')) }}",
        quantity: "{{ trans('messages.quantity', [], session('locale')) }}",
        failed_to_load_details: "{{ trans('messages.failed_to_load_details', [], session('locale')) }}",
        success_title: "{{ trans('messages.success_title', [], session('locale')) }}",
        error_title: "{{ trans('messages.error_title', [], session('locale')) }}",
        error_occurred: "{{ trans('messages.error_occurred', [], session('locale')) }}",
        error_saving: "{{ trans('messages.error_saving', [], session('locale')) }}",
        saving: "{{ trans('messages.saving', [], session('locale')) }}",
        please_enter_pull_notes: "{{ trans('messages.please_enter_pull_notes', [], session('locale')) }}",
        pieces: "{{ trans('messages.pieces', [], session('locale')) }}"
    };

    function getStockQuantity(stock) {
        if (!stock.color_sizes || !stock.color_sizes.length) return 0;
        return stock.color_sizes.reduce(function(sum, item) { return sum + (parseInt(item.qty, 10) || 0); }, 0);
    }
    function getQuantityStatus(stock) {
        var q = getStockQuantity(stock);
        if (q === 0) return 'out_of_stock';
        if (q <= 5) return 'low';
        return 'available';
    }
    function getFilteredStock() {
        var search = ($("#stock_search").val() || '').toLowerCase().trim();
        var filterVal = $("#stock_filter").val() || 'all';
        return allStockData.filter(function(stock) {
            var quantityStatus = getQuantityStatus(stock);
            if (filterVal !== 'all' && quantityStatus !== filterVal) return false;
            if (search === '') return true;
            var code = (stock.abaya_code || '').toLowerCase();
            var name = (stock.design_name || '').toLowerCase();
            var barcode = (stock.barcode || '').toLowerCase();
            var cat = (stock.category && stock.category.category_name) ? stock.category.category_name.toLowerCase() : '';
            return code.indexOf(search) > -1 || name.indexOf(search) > -1 || barcode.indexOf(search) > -1 || cat.indexOf(search) > -1;
        });
    }
    function buildRowFromStock(stock) {
        var image = stock.images && stock.images.length ? stock.images[0].image_path : '';
        var size = '-', color = '-', quantity = getStockQuantity(stock);
        if (stock.color_sizes && stock.color_sizes.length > 0) {
            var first = stock.color_sizes[0];
            size = first.size ? (first.size.size_name_ar || first.size.size_name_en || '-') : '-';
            color = first.color ? (first.color.color_name_ar || first.color.color_name_en || '-') : '-';
        }
        var categoryName = stock.category ? stock.category.category_name : '-';
        var quantityStatus = getQuantityStatus(stock);
        var salesPrice = stock.sales_price ? parseFloat(stock.sales_price).toFixed(2) : '-';
        var formattedSalesPrice = salesPrice !== '-' ? salesPrice + ' OMR' : '-';
        return {
            quantityStatus: quantityStatus,
            tableRow: '<tr class="border-t hover:bg-pink-50/60 transition" data-id="' + stock.id + '" data-quantity-status="' + quantityStatus + '">' +
                '<td class="px-3 sm:px-4 md:px-6 py-3 text-center"><div class="flex items-start justify-center gap-3">' +
                '<img src="' + image + '" class="w-12 h-16 object-cover rounded-md flex-shrink-0" />' +
                '<div class="flex flex-col items-start text-left min-w-0 flex-1">' +
                '<span class="font-bold break-words">' + (stock.design_name || '-') + '</span>' +
                (categoryName !== '-' ? '<span class="text-sm text-gray-600">(' + categoryName + ')</span>' : '') + '</div></div></td>' +
                '<td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap font-medium">' + (stock.abaya_code || '-') + '</td>' +
                '<td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap">' + color + '</td>' +
                '<td class="px-3 sm:px-4 md:px-6 py-3 text-center font-bold whitespace-nowrap">' + quantity + '</td>' +
                '<td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap font-semibold text-[var(--primary-color)]">' + formattedSalesPrice + '</td>' +
                '<td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap">' + (stock.tailor_names_display || '-') + '</td>' +
                '<td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap"><div class="flex justify-center gap-2 text-[12px] font-semibold text-gray-700">' +
                '<button class="flex flex-col items-center gap-1 hover:text-purple-600 transition" onclick="openFullStockDetails(' + stock.id + ')">' +
                '<span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>' + trans.details + '</button>' +
                '<button class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition d-none" x-on:click="$dispatch(\'open-stock-details\', ' + stock.id + ')">' +
                '<span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>' + trans.details + '</button>' +
                '<button class="openQuantityBtn flex flex-col items-center gap-1 hover:text-green-600 transition" onclick="openStockQuantity(' + stock.id + ')">' +
                '<span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>' + trans.enter_quantity + '</button>' +
                '<button class="flex flex-col items-center gap-1 hover:text-blue-600 transition" onclick="window.location.href=\'/edit_stock/' + stock.id + '?page=' + (currentStockPage || 1) + '\'">' +
                '<span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>' + trans.edit + '</button>' +
                '<button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-stock-btn">' +
                '<span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>' + trans.delete + '</button></div></td></tr>',
            mobileCard: '<div class="bg-white rounded-xl shadow-sm border border-pink-100 p-4 flex flex-col gap-3" data-id="' + stock.id + '" data-quantity-status="' + quantityStatus + '">' +
                '<div class="flex gap-4"><div class="w-20 h-24 rounded-md overflow-hidden bg-gray-100 flex-shrink-0">' +
                '<img src="' + image + '" alt="' + (stock.abaya_code || trans.design) + '" class="w-full h-full object-cover" onerror="this.src=\'/images/placeholder.png\'" />' +
                '</div><div class="flex-1 text-sm"><div class="flex justify-between items-center mb-2">' +
                '<h3 class="font-bold text-gray-900">' + (stock.abaya_code || '-') + '</h3>' +
                '<span class="text-[var(--primary-color)] font-semibold text-xs">' + formattedSalesPrice + '</span></div>' +
                '<p class="text-gray-600 text-xs">' + trans.design + ': ' + (stock.design_name || '-') + '</p>' +
                (categoryName !== '-' ? '<p class="text-gray-600 text-xs">' + trans.category + ': ' + categoryName + '</p>' : '') +
                '<p class="text-gray-600 text-xs">' + trans.color + ': ' + color + '</p>' +
                '<p class="text-gray-600 text-xs font-semibold">' + trans.quantity + ': ' + quantity + '</p></div></div>' +
                '<div class="mt-4 border-t pt-3"><div class="flex justify-around text-xs font-semibold text-gray-600">' +
                '<button class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition" onclick="openFullStockDetails(' + stock.id + ')">' +
                '<span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>' + trans.details + '</button>' +
                '<button class="flex flex-col items-center gap-1 hover:text-green-600 transition openQuantityBtn" onclick="openStockQuantity(' + stock.id + ')">' +
                '<span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>' + trans.enter_quantity + '</button>' +
                '<button class="flex flex-col items-center gap-1 hover:text-blue-500 transition" onclick="window.location.href=\'/edit_stock/' + stock.id + '?page=' + (currentStockPage || 1) + '\'">' +
                '<span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>' + trans.edit + '</button>' +
                '<button class="flex flex-col items-center gap-1 hover:text-red-500 transition delete-stock-btn-mobile" data-stock-id="' + stock.id + '">' +
                '<span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>' + trans.delete + '</button></div></div></div>'
        };
    }
    function renderFromFullData(page) {
        var filtered = getFilteredStock();
        var total = filtered.length;
        var last = Math.max(1, Math.ceil(total / perPage));
        var cur = Math.max(1, Math.min(page, last));
        currentStockPage = cur;
        var start = (cur - 1) * perPage;
        var slice = filtered.slice(start, start + perPage);
        var tableRows = '', mobileCards = '';
        for (var i = 0; i < slice.length; i++) {
            var o = buildRowFromStock(slice[i]);
            tableRows += o.tableRow;
            mobileCards += o.mobileCard;
        }
        $("#desktop_stock_body").html(tableRows);
        $("#mobile_stock_cards").html(mobileCards);
        var windowSize = 2;
        var pagination = '';
        var btn = function(pageNum, label, active, disabled) {
            var base = "inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 ";
            var activeCls = active ? "bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md" : "bg-white hover:bg-gray-100 border-gray-200";
            var disCls = disabled ? "opacity-40 pointer-events-none bg-gray-200 border-gray-200" : "";
            return '<li class="shrink-0"><a href="#" data-page="' + (disabled ? '' : pageNum) + '" class="' + base + (disabled ? disCls : activeCls) + '">' + label + '</a></li>';
        };
        pagination += btn(cur > 1 ? cur - 1 : '', '&laquo; Prev', false, cur <= 1);
        if (last <= 7) {
            for (var i = 1; i <= last; i++) pagination += btn(i, i, cur === i, false);
        } else {
            var showFirst = cur > windowSize + 2, showLast = cur < last - windowSize - 1;
            var startP = Math.max(1, cur - windowSize), endP = Math.min(last, cur + windowSize);
            if (showFirst) pagination += btn(1, '1', cur === 1, false) + '<li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>';
            for (var j = startP; j <= endP; j++) pagination += btn(j, j, cur === j, false);
            if (showLast) pagination += '<li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>' + btn(last, last, cur === last, false);
        }
        pagination += btn(cur < last ? cur + 1 : '', 'Next &raquo;', false, cur >= last);
        $("#stock_pagination").html(pagination);
        $("#stock_pagination_loader").hide();
    }
    function fetchAllStockThenRender(showPage) {
        $("#stock_pagination_loader").show();
        var data = [];
        var page = 1;
        function fetchNext() {
            $.get("/stock/list", { page: page }).done(function(res) {
                data = data.concat(res.data || []);
                var lastPage = res.last_page || 1;
                if (page >= lastPage) {
                    allStockData = data;
                    allStockLoaded = true;
                    renderFromFullData(showPage);
                } else {
                    page++;
                    fetchNext();
                }
            }).fail(function() {
                $("#stock_pagination_loader").hide();
            });
        }
        fetchNext();
    }
    function loadStock(page) {
        page = page || currentStockPage || 1;
        if (!allStockLoaded) {
            fetchAllStockThenRender(page);
            return;
        }
        $("#stock_pagination_loader").show();
        renderFromFullData(page);
    }

    var stockSearchTimeout = null;
    $(document).ready(function() {
        $(document).on("click", "#stock_pagination a", function(e) {
            e.preventDefault();
            var p = $(this).attr("data-page");
            if (p !== undefined && p !== '' && !isNaN(parseInt(p, 10))) {
                $("#stock_pagination_loader").show();
                loadStock(parseInt(p, 10));
            }
        });
        $("#stock_search").on("keyup", function() {
            if (stockSearchTimeout) clearTimeout(stockSearchTimeout);
            stockSearchTimeout = setTimeout(function() {
                if (allStockLoaded) {
                    currentStockPage = 1;
                    $("#stock_pagination_loader").show();
                    renderFromFullData(1);
                } else {
                    loadStock(1);
                }
            }, 350);
        });
        $("#stock_filter").on("change", function() {
            if (allStockLoaded) {
                currentStockPage = 1;
                $("#stock_pagination_loader").show();
                renderFromFullData(1);
            } else {
                loadStock(1);
            }
        });
        var startPage = 1;
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page')) {
            var p = parseInt(urlParams.get('page'), 10);
            if (!isNaN(p) && p >= 1) startPage = p;
        }
        loadStock(startPage);
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
                    alert(trans.failed_to_load_details);
                    this.loading = false;
                    this.showDetails = false;
                }
            });
        }
    }
}



function openStockQuantity(stockId) {
    // Get the Alpine.js component from the main element
    const mainElement = document.querySelector('main[x-data]');
    if (!mainElement) {
        console.error('Main element with Alpine.js not found');
        return;
    }
    
    // Try multiple methods to access Alpine.js data
    let alpineData = null;
    try {
        // Method 1: Direct access via _x_dataStack
        if (mainElement._x_dataStack && mainElement._x_dataStack[0]) {
            alpineData = mainElement._x_dataStack[0];
        }
        // Method 2: Use Alpine.$data if available
        else if (window.Alpine && typeof window.Alpine.$data === 'function') {
            alpineData = window.Alpine.$data(mainElement);
        }
        // Method 3: Try accessing via Alpine reactive
        else if (window.Alpine && mainElement._x_dataStack) {
            alpineData = mainElement._x_dataStack[0];
        }
    } catch (e) {
        console.error('Error accessing Alpine.js data:', e);
    }
    
    if (!alpineData) {
        console.error('Could not access Alpine.js data');
        return;
    }
    
    // Set stock_id in the form before showing modal
    $('#stock_id').val(stockId);
    
    // Reset form state
    $('#save_qty')[0].reset();
    $('#stock_id').val(stockId); // Set again after reset
    $('#selected_tailor_id').val(''); // Reset tailor selection
    if (alpineData) {
        alpineData.actionType = 'add';
    }
    
    // Re-enable submit button and restore original text
    var submitBtn = $('#save_qty').find('button[type="submit"]');
    submitBtn.prop('disabled', false);
    submitBtn.html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
    
    // Show the modal using Alpine.js
    if (alpineData) {
        alpineData.showQuantity = true;
    }

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
                
                // Update quantity inputs based on current mode (add by default)
                setTimeout(function() {
                    if (alpineData && typeof alpineData.updateQuantityInputs === 'function') {
                        alpineData.updateQuantityInputs();
                    }
                }, 100);
                
                // Populate original tailor display
                let originalTailorHtml = '';
                if (response.original_tailors && response.original_tailors.length > 0) {
                    response.original_tailors.forEach(function(tailor) {
                        originalTailorHtml += `
                            <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-100 text-blue-800 rounded-lg text-sm font-semibold border border-blue-200">
                                <span class="material-symbols-outlined text-base">person</span>
                                ${tailor.name}
                            </span>
                        `;
                    });
                } else {
                    originalTailorHtml = `
                        <span class="text-sm text-gray-500 italic">
                            {{ trans('messages.no_tailor_assigned', [], session('locale')) ?: 'No tailor assigned' }}
                        </span>
                    `;
                }
                $('#original_tailor_display').html(originalTailorHtml);
                
                // Populate tailor dropdown
                let tailorSelect = $('#selected_tailor_id');
                tailorSelect.find('option:not(:first)').remove(); // Clear existing options except first
                
                if (response.all_tailors && response.all_tailors.length > 0) {
                    response.all_tailors.forEach(function(tailor) {
                        tailorSelect.append(`<option value="${tailor.id}">${tailor.name}</option>`);
                    });
                } else {
                    tailorSelect.append(`<option value="">{{ trans('messages.no_tailors_available', [], session('locale')) ?: 'No tailors available' }}</option>`);
                }
            }
        },
        error: function(err) {
            console.error('Error:', err);
            alert(trans.failed_to_load_details);
            alpineData.showQuantity = false; // Close modal on error
        }
    });
}



    $(document).on('click', '.delete-stock-btn', function() {
        let id = $(this).closest('tr').data('id');
        deleteStock(id);
    });

    // Delete handler for mobile cards
    $(document).on('click', '.delete-stock-btn-mobile', function() {
        let id = $(this).data('stock-id');
        deleteStock(id);
    });

    // Common delete function
    function deleteStock(id) {
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
                        loadStock(currentStockPage); // reload table with current page
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
    }


$(document).ready(function() {
    $('#save_qty').on('submit', function(e) {
        e.preventDefault(); // prevent default form submit
        e.stopPropagation(); // prevent event bubbling

        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        
        // Get Alpine.js data
        const mainElement = document.querySelector('main[x-data]');
        if (!mainElement || !mainElement._x_dataStack || !mainElement._x_dataStack[0]) {
            console.error('Alpine.js not initialized');
            return false;
        }
        const alpineData = mainElement._x_dataStack[0];
        
        // Check for pull reason if action is pull
        const actionType = alpineData.actionType || $('input[name="qtyType"]:checked').val();
        const pullReason = $('#pull_reason').val() ? $('#pull_reason').val().trim() : '';
        
        if (actionType === 'pull' && pullReason === '') {
            show_notification('error', '{{ trans("messages.please_enter_pull_notes", [], session("locale")) ?: "Please enter pull notes" }}');
            return false;
        }
        
        // Validate tailor selection when adding quantity
        if (actionType === 'add') {
            const selectedTailor = $('#selected_tailor_id').val();
            if (!selectedTailor || selectedTailor === '') {
                show_notification('error', '{{ trans("messages.please_select_tailor", [], session("locale")) ?: "Please select a tailor for this quantity" }}');
                $('#selected_tailor_id').focus();
                return false;
            }
        }
        
        // Validate quantity inputs
        let hasQuantity = false;
        let validationError = null;
        
        // Check quantity inputs in all containers (using specific field names)
        $('#colorsize_container input[name="size_color_qty[]"], #sizecont input[name="size_qty[]"], #colorcont input[name="color_qty[]"]').each(function() {
            const $input = $(this);
            const value = parseFloat($input.val());
            
            // Check if value is not empty and not NaN (allow 0 and negative values)
            if (!isNaN(value) && $input.val() !== '') {
                hasQuantity = true;
                
                // If pulling, check if quantity doesn't exceed available
                if (actionType === 'pull') {
                    const availableQty = parseFloat($input.data('available-qty')) || 0;
                    if (value > availableQty) {
                        validationError = '{{ trans("messages.pull_quantity_exceeds_available", [], session("locale")) ?: "Pull quantity cannot exceed available quantity" }}. ' + 
                                         '{{ trans("messages.available", [], session("locale")) ?: "Available" }}: ' + availableQty;
                        return false; // break the loop
                    }
                    // For pull mode: also ensure value is positive (can't pull negative)
                    if (value <= 0) {
                        validationError = '{{ trans("messages.pull_quantity_must_be_positive", [], session("locale")) ?: "Pull quantity must be greater than 0" }}';
                        return false;
                    }
                }
                // For add mode: no restrictions on quantity value (can be negative, any value)
            }
        });
        
        if (validationError) {
            show_notification('error', validationError);
            return false;
        }
        
        if (!hasQuantity) {
            show_notification('error', '{{ trans("messages.please_enter_quantity", [], session("locale")) ?: "Please enter at least one quantity" }}');
            return false;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.prop('disabled', true).html('<span class="material-symbols-outlined align-middle me-2 text-sm animate-spin">hourglass_empty</span>' + trans.saving);
        
        var formData = form.serialize(); // serialize form fields
        formData += '&stock_id=' + $('#stock_id').val();
        formData += '&actionType=' + actionType; // include add/pull
        
        // Explicitly add selected_tailor_id for additions
        if (actionType === 'add') {
            const selectedTailorId = $('#selected_tailor_id').val();
            if (selectedTailorId) {
                formData += '&selected_tailor_id=' + encodeURIComponent(selectedTailorId);
            }
        }

        $.ajax({
            url: "{{ route('add_quantity') }}", // your Laravel route
            type: 'POST',
            data: formData,
         
            success: function(response) {
              if (response.status === "success") {
    // Re-enable submit button before closing modal
    submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
    
    Swal.fire({
        icon: 'success',
        title: trans.success_title,
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

    // Close modal using Alpine.js
    setTimeout(() => {
        alpineData.showQuantity = false;
    }, 500);

} else {

    Swal.fire({
        icon: 'error',
        title: trans.error_title,
        text: response.message || trans.error_occurred
    });

    submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
}

            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                
                var errorMsg = trans.error_saving || '{{ trans("messages.error_saving", [], session("locale")) ?: "Error saving" }}';
                
                // Check if response has JSON with message
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        const jsonResponse = JSON.parse(xhr.responseText);
                        if (jsonResponse.message) {
                            errorMsg = jsonResponse.message;
                        }
                    } catch (e) {
                        // Not JSON, use default error message
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: trans.error_title || '{{ trans("messages.error", [], session("locale")) }}',
                    text: errorMsg
                });
                
                // Re-enable submit button
                submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
            }
        });
        
        return false; // Additional prevention
    });
});
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Full Stock Details Modal Function
function openFullStockDetails(stockId) {
    // Get the Alpine.js component from the main element
    const mainElement = document.querySelector('main[x-data]');
    if (!mainElement) {
        console.error('Main element with Alpine.js not found');
        return;
    }
    
    // Try multiple methods to access Alpine.js data
    let alpineData = null;
    try {
        // Method 1: Direct access via _x_dataStack
        if (mainElement._x_dataStack && mainElement._x_dataStack[0]) {
            alpineData = mainElement._x_dataStack[0];
        }
        // Method 2: Use Alpine.$data if available
        else if (window.Alpine && typeof window.Alpine.$data === 'function') {
            alpineData = window.Alpine.$data(mainElement);
        }
        // Method 3: Try accessing via Alpine reactive
        else if (window.Alpine && mainElement._x_dataStack) {
            alpineData = mainElement._x_dataStack[0];
        }
    } catch (e) {
        console.error('Error accessing Alpine.js data:', e);
    }
    
    if (!alpineData) {
        console.error('Could not access Alpine.js data');
        return;
    }

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

    // Show modal and loader using Alpine.js
    if (alpineData) {
        alpineData.showFullDetails = true;
        alpineData.fullDetailsLoading = true;
    }

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
                            <div class="rounded-xl overflow-hidden shadow-sm">
                                <div class="relative" style="height: 200px; overflow: hidden;">
                                    <img src="${imagePath}" 
                                         class="w-full h-full object-cover" 
                                         alt="${trans.abaya_image} ${index + 1}"
                                         onerror="this.src='/images/placeholder.png'">
                                </div>
                            </div>
                        `;
                    });
                    $('#full_stock_images_container').html(imagesHtml);
                } else {
                    $('#full_stock_images_container').html(`
                        <div class="col-span-full">
                            <p class="text-gray-500 text-center">{{ trans('messages.no_images_available', [], session('locale')) }}</p>
                        </div>
                    `);
                }

                // Populate color-size combinations
                if (response.color_size_details && response.color_size_details.length > 0) {
                    let colorSizeHtml = '';
                    response.color_size_details.forEach(function(item) {
                        colorSizeHtml += `
                            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h6 class="font-bold text-gray-900 mb-1">${item.size_name}</h6>
                                        <div class="flex items-center gap-2">
                                            <div class="rounded-full border-2 border-gray-300" 
                                                 style="width: 24px; height: 24px; background-color: ${item.color_code};"></div>
                                            <span class="text-gray-600 text-sm">${item.color_name}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right mt-3">
                                    <span class="bg-[var(--primary-color)] text-white rounded-full px-4 py-2 text-sm font-semibold">${item.quantity} ${trans.pieces}</span>
                                </div>
                            </div>
                        `;
                    });
                    $('#full_size_color_container').html(colorSizeHtml);
                } else {
                    $('#full_size_color_container').html(`
                        <div class="col-span-full">
                            <p class="text-gray-500 text-center">{{ trans('messages.no_data_available', [], session('locale')) }}</p>
                        </div>
                    `);
                }
            }

            // Hide loader
            if (alpineData) {
                alpineData.fullDetailsLoading = false;
            }
        },
        error: function(err) {
            console.error('Error:', err);
            alert('{{ trans("messages.error_loading_details", [], session("locale")) }}');
            if (alpineData) {
                alpineData.fullDetailsLoading = false;
            }
        }
    });
}
</script>