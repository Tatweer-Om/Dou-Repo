<script>
    $(document).ready(function() {

        // ---------------- Get Status Display (Active/Inactive) ----------------
        function getStatus(status) {
            if (status == '1') {
                return '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700"><span class="material-symbols-outlined text-xs">check_circle</span> <?= trans("messages.active", [], session("locale")) ?></span>';
            } else if (status == '2') {
                return '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700"><span class="material-symbols-outlined text-xs">cancel</span> <?= trans("messages.inactive", [], session("locale")) ?></span>';
            } else {
                return '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">-</span>';
            }
        }

        // ---------------- Get Rent Invoice Status Display ----------------
        function getRentInvoiceStatus(boutique) {
            // Show unpaid months if any
            if (boutique.unpaid_months && boutique.unpaid_months.length > 0) {
                let unpaidText = boutique.unpaid_months.join(', ');
                return `<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700" title="${unpaidText}">
                    <span class="material-symbols-outlined text-xs">cancel</span> 
                    ${boutique.unpaid_count} <?= trans("messages.unpaid", [], session("locale")) ?> (${unpaidText})
                </span>`;
            } else {
                return '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700"><span class="material-symbols-outlined text-xs">check_circle</span> <?= trans("messages.all_paid", [], session("locale")) ?></span>';
            }
        }

        // ---------------- Load Boutiques Function ----------------
        function loadBoutiques(page = 1, search = '') {
            $.get("{{ url('boutiques/list') }}", {
                page: page,
                search: search
            }, function(res) {

                // -------- Desktop Table Rows --------
                let tableRows = "";
                $.each(res.data, function(index, boutique) {
                    tableRows += `
                    <tr class="border-t hover:bg-pink-50/60 transition" data-id="${boutique.id}">
                        <td class="px-3 py-3">${index + 1}</td>
                        <td class="px-3 py-3 font-bold text-gray-800">${boutique.boutique_name ?? ''}</td>
                        <td class="px-3 py-3 text-gray-700">${boutique.shelf_no ?? '-'}</td>
                        <td class="px-3 py-3 font-semibold text-gray-800">${boutique.monthly_rent ?? '-'} ر.ع</td>
                        <td class="px-3 py-3">${boutique.rent_date ?? '-'}</td>
                        <td class="px-3 py-3 truncate max-w-[220px]" title="${boutique.boutique_address ?? ''}">
                            ${boutique.boutique_address ?? ''}
                        </td>
                        <td class="px-3 py-3 text-center">
                            ${getStatus(boutique.status)}
                        </td>
                        <td class="px-3 py-3 text-center">
                            ${getRentInvoiceStatus(boutique)}
                        </td>
                        <td class="px-3 py-3 text-center">
                            <div class="flex justify-center gap-3 text-xs sm:text-sm font-semibold">
                                <button class="flex items-center gap-1 text-[var(--primary-color)] hover:underline viewBtn" data-id="${boutique.id}">
                                    <span class="material-symbols-outlined text-base">visibility</span> عرض
                                </button>
                          
                            <button class="flex items-center gap-1 text-blue-600 hover:underline editBtn"
                                    onclick="window.location.href='/edit_boutique/${boutique.id}'"
                                    data-id="${boutique.id}">
                                <span class="material-symbols-outlined text-base">edit</span> تعديل
                            </button>

                            <button class="flex items-center gap-1 text-green-600 hover:underline rentInvoiceBtn" data-id="${boutique.id}">
                                <span class="material-symbols-outlined text-base">receipt</span> فاتورة الإيجار
                            </button>

                                                            <button class="flex items-center gap-1 text-red-600 hover:underline deleteBtn" data-id="${boutique.id}">
                                                                <span class="material-symbols-outlined text-base">delete</span> حذف
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>`;
                                            });
                                            $("#desktop_boutique_body").html(tableRows);

                                            // -------- Mobile Cards --------
                                            let mobileCards = '';
                                            $.each(res.data, function(index, boutique) {
                                                mobileCards += `
                                                <div class="bg-white border border-pink-100 rounded-2xl shadow-sm hover:shadow-md transition p-4 mb-4 md:hidden">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-gray-900 text-lg truncate">${boutique.boutique_name ?? ''}</h3>
                                    <span class="text-sm text-gray-500 font-semibold">${boutique.shelf_no ?? '#' + (index+1) + 'A'}</span>
                                </div>
                                <div class="space-y-1 text-sm text-gray-700 mt-2">
                                    <p><span class="font-semibold"><?= trans('messages.monthly_rent', [], session('locale')) ?>:</span> ${boutique.monthly_rent ?? '-'} ر.ع</p>
                                    <p><span class="font-semibold"><?= trans('messages.rent_date', [], session('locale')) ?>:</span> ${boutique.rent_date ?? '-'}</p>
                                    <p class="truncate"><span class="font-semibold"><?= trans('messages.address', [], session('locale')) ?>:</span> ${boutique.boutique_address ?? ''}</p>
                                    <p><span class="font-semibold"><?= trans('messages.status', [], session('locale')) ?>:</span> ${getStatus(boutique.status)}</p>
                                    <p><span class="font-semibold"><?= trans('messages.rent_invoice_status', [], session('locale')) ?>:</span> ${getRentInvoiceStatus(boutique)}</p>
                                </div>
                                <div class="flex justify-around border-t pt-3 mt-2 text-xs font-semibold">
                                    <button class="flex flex-col items-center gap-1 text-[var(--primary-color)] hover:scale-105 transition viewBtn" data-id="${boutique.id}">
                                        <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">visibility</span> <?= trans('messages.view', [], session('locale')) ?>
                                    </button>
                                <button class="flex flex-col items-center gap-1 text-blue-600 hover:scale-105 transition editBtn"
                                    onclick="window.location.href='/edit_boutique/${boutique.id}'">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-600 p-2 rounded-full text-base">edit</span>
                                <?= trans('messages.edit', [], session('locale')) ?>
                            </button>
                                    <button class="flex flex-col items-center gap-1 text-green-600 hover:scale-105 transition rentInvoiceBtn" data-id="${boutique.id}">
                                        <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">receipt</span> <?= trans('messages.rent_invoice', [], session('locale')) ?>
                                    </button>
                                    <button class="flex flex-col items-center gap-1 text-red-600 hover:scale-105 transition deleteBtn" data-id="${boutique.id}">
                                        <span class="material-symbols-outlined bg-red-50 text-red-600 p-2 rounded-full text-base">delete</span> <?= trans('messages.delete', [], session('locale')) ?>
                                    </button>
                                </div>
                            </div>
                `;
                });
                $("#mobile_boutique_cards").html(mobileCards);

                // -------- Pagination --------
                let pagination = "";
                pagination += `<li class="px-3 py-1 rounded-full ${!res.prev_page_url ? "opacity-50 pointer-events-none" : "bg-gray-200 hover:bg-gray-300"}">
                                <a href="${res.prev_page_url || '#'}">&laquo;</a>
                           </li>`;
                for (let i = 1; i <= res.last_page; i++) {
                    pagination += `<li class="px-3 py-1 rounded-full ${res.current_page==i ? "bg-[var(--primary-color)] text-white" : "bg-gray-200 hover:bg-gray-300"}">
                                   <a href="{{ url('boutiques/list') }}?page=${i}">${i}</a>
                               </li>`;
                }
                pagination += `<li class="px-3 py-1 rounded-full ${!res.next_page_url ? "opacity-50 pointer-events-none" : "bg-gray-200 hover:bg-gray-300"}">
                               <a href="${res.next_page_url || '#'}">&raquo;</a>
                           </li>`;
                $("#pagination").html(pagination);
            });
        }

        // ---------------- Handle Pagination Clicks ----------------
        $(document).on("click", "#pagination a", function(e) {
            e.preventDefault();
            let href = $(this).attr("href");
            if (href && href !== "#") {
                let page = new URL(href).searchParams.get("page");
                let search = $("#search_boutique").val() || '';
                if (page) loadBoutiques(page, search);
            }
        });

        // ---------------- Search (button + input) ----------------
        function filterSearch() {
            let search = $("#search_boutique").val().toLowerCase();
            $("#desktop_boutique_body tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(search) > -1);
            });
            $("#mobile_boutique_cards > div").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(search) > -1);
            });
        }

        $("#search_button").on("click", filterSearch);
        $("#search_boutique").on("keyup", filterSearch);

        // ---------------- Initial Load ---------------- 
        loadBoutiques();
   $(document).on('click', '.deleteBtn', function() {
    let id = $(this).data('id'); // get boutique id from button data attribute

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
                url: '<?= url("boutique") ?>/' + id, // boutique delete route
                method: 'DELETE',
                data: {
                    _token: '<?= csrf_token() ?>'
                },
                success: function(data) {
                    loadBoutiques(); // reload table after delete
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

// ---------------- Handle View Button Click ---------------- 
$(document).on('click', '.viewBtn', function() {
    let boutiqueId = $(this).data('id');
    window.location.href = '/boutique_profile/' + boutiqueId;
});

// ---------------- Handle Rent Invoice Button Click ---------------- 
$(document).on('click', '.rentInvoiceBtn', function() {
    let boutiqueId = $(this).data('id');
    $('#rent_invoice_modal').removeClass('hidden');
    $('#rent_invoice_boutique_id').val(boutiqueId);
    loadBoutiqueInvoices(boutiqueId);
});

// ---------------- Load Boutique Invoices ---------------- 
function loadBoutiqueInvoices(boutiqueId) {
    $('#rent_invoice_loading').removeClass('hidden');
    $('#rent_invoice_content').addClass('hidden');
    
    $.ajax({
        url: '<?= url("get_boutique_invoices") ?>',
        method: 'GET',
        data: {
            boutique_id: boutiqueId
        },
        success: function(data) {
            if (data.success) {
                $('#rent_invoice_boutique_name').text(data.boutique.name);
                $('#rent_invoice_monthly_rent').text(data.boutique.monthly_rent || '0');
                
                let tableBody = '';
                data.invoices.forEach(function(invoice) {
                    let statusBadge = invoice.status == '4' 
                        ? '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700"><span class="material-symbols-outlined text-xs">check_circle</span> <?= trans("messages.paid", [], session("locale")) ?></span>'
                        : '<span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700"><span class="material-symbols-outlined text-xs">cancel</span> <?= trans("messages.unpaid", [], session("locale")) ?></span>';
                    
                    let amountInput = `<input type="number" step="0.01" min="0" class="invoice-amount w-24 h-9 border border-pink-200 rounded-lg text-center text-sm" 
                        data-invoice-id="${invoice.id}" value="${invoice.total_amount || ''}" 
                        ${invoice.status == '4' ? '' : ''}>`;
                    
                    let paymentDateInput = `<input type="date" class="invoice-payment-date w-36 h-9 border border-pink-200 rounded-lg text-center text-sm" 
                        data-invoice-id="${invoice.id}" value="${invoice.payment_date || ''}">`;
                    
                    tableBody += `
                        <tr class="border-t hover:bg-pink-50/30">
                            <td class="px-4 py-3 text-right font-semibold">${invoice.month}</td>
                            <td class="px-4 py-3 text-center">${statusBadge}</td>
                            <td class="px-4 py-3 text-right">${amountInput}</td>
                            <td class="px-4 py-3 text-right">${paymentDateInput}</td>
                        </tr>
                    `;
                });
                
                $('#rent_invoice_table_body').html(tableBody);
                $('#rent_invoice_loading').addClass('hidden');
                $('#rent_invoice_content').removeClass('hidden');
            }
        },
        error: function() {
            $('#rent_invoice_loading').addClass('hidden');
            Swal.fire(
                '<?= trans("messages.error", [], session("locale")) ?>',
                '<?= trans("messages.error_loading_invoices", [], session("locale")) ?>',
                'error'
            );
        }
    });
}

// ---------------- Save Invoice Payments ---------------- 
$(document).on('click', '#save_invoice_payments_btn', function() {
    let invoicesToUpdate = [];
    
    // Collect all unpaid invoices with updated data
    $('#rent_invoice_table_body tr').each(function() {
        let $row = $(this);
        let amountInput = $row.find('.invoice-amount');
        let paymentDateInput = $row.find('.invoice-payment-date');
        
        if (amountInput.length && paymentDateInput.length) {
            let invoiceId = amountInput.data('invoice-id');
            let amount = amountInput.val();
            let paymentDate = paymentDateInput.val();
            
            // Only update if amount and payment date are provided
            if (amount && paymentDate) {
                invoicesToUpdate.push({
                    invoice_id: invoiceId,
                    amount: amount,
                    payment_date: paymentDate
                });
            }
        }
    });
    
    if (invoicesToUpdate.length === 0) {
        Swal.fire(
            '<?= trans("messages.warning", [], session("locale")) ?>',
            '<?= trans("messages.please_enter_amount_and_date", [], session("locale")) ?>',
            'warning'
        );
        return;
    }
    
    // Update each invoice
    let updatePromises = invoicesToUpdate.map(function(invoice) {
        return $.ajax({
            url: '<?= url("update_invoice_payment") ?>',
            method: 'POST',
            data: {
                _token: '<?= csrf_token() ?>',
                invoice_id: invoice.invoice_id,
                amount: invoice.amount,
                payment_date: invoice.payment_date
            }
        });
    });
    
    $.when.apply($, updatePromises).done(function() {
        let boutiqueId = $('#rent_invoice_boutique_id').val();
        $('#rent_invoice_modal').addClass('hidden');
        loadBoutiques(); // Reload table
        Swal.fire(
            '<?= trans("messages.success", [], session("locale")) ?>',
            '<?= trans("messages.payments_updated_successfully", [], session("locale")) ?>',
            'success'
        );
    }).fail(function() {
        Swal.fire(
            '<?= trans("messages.error", [], session("locale")) ?>',
            '<?= trans("messages.error_updating_payments", [], session("locale")) ?>',
            'error'
        );
    });
});

// ---------------- Close Modal ---------------- 
$(document).on('click', '#close_rent_invoice_modal', function() {
    $('#rent_invoice_modal').addClass('hidden');
});

// Close modal when clicking outside
$(document).on('click', '#rent_invoice_modal', function(e) {
    if ($(e.target).attr('id') === 'rent_invoice_modal') {
        $('#rent_invoice_modal').addClass('hidden');
    }
});
    });

 

</script>