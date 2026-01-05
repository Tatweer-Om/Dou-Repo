<script>
    $(document).ready(function() {

       function loadExpenses(page = 1) {
    $.get("{{ url('expenses/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, expense) {
            const categoryName = expense.category ? expense.category.category_name : '-';
            const accountName = expense.account ? expense.account.account_name : '-';
            const expenseDate = expense.expense_date ? new Date(expense.expense_date).toLocaleDateString() : '-';
            
            // Check if receipt exists
            const receiptButton = expense.expense_image ? 
                `<button class="view-receipt-btn icon-btn hover:text-blue-500" data-file="${expense.expense_image}" title="View/Download Receipt">
                    <span class="material-symbols-outlined">receipt</span>
                </button>` : '';

            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors border-b border-gray-100" data-id="${expense.id}">
                <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)] font-medium">${expense.expense_name || '-'}</td>
                <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${categoryName}</td>
                <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)] font-bold text-red-600">${expense.amount ? parseFloat(expense.amount).toFixed(3) : '0.000'}</td>
                <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${accountName}</td>
                <td class="px-4 sm:px-6 py-3 text-[var(--text-primary)]">${expenseDate}</td>
                <td class="px-4 sm:px-6 py-3 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
                        <button class="edit-btn icon-btn">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        ${receiptButton}
                        <button class="delete-btn icon-btn hover:text-red-500">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });
        $('tbody').html(rows);

        // ---- Pagination ----
        let pagination = '';

        // Previous
        pagination += `
        <li class="${!res.prev_page_url ? 'disabled' : ''}">
            <a href="${res.prev_page_url ? res.prev_page_url : '#'}">&laquo;</a>
        </li>`;

        // Page numbers
        for (let i = 1; i <= res.last_page; i++) {
            if (res.current_page == i) {
                pagination += `
                <li class="active">
                    <span>${i}</span>
                </li>
                `;
            } else {
                pagination += `
                <li>
                    <a href="{{ url('expenses/list') }}?page=${i}">${i}</a>
                </li>
                `;
            }
        }

        // Next
        pagination += `
        <li class="${!res.next_page_url ? 'disabled' : ''}">
            <a href="${res.next_page_url ? res.next_page_url : '#'}">&raquo;</a>
        </li>`;

        $('#pagination').html(pagination);
    });
}

// Handle pagination click
$(document).on('click', '#pagination a', function(e) {
    e.preventDefault();
    let href = $(this).attr('href');
    if (href && href !== '#') {
        let page = new URL(href).searchParams.get('page');
        if (page) loadExpenses(page);
    }
});

        // Initial load
        loadExpenses();

        $('#search_expense').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });

        // File preview
        $('#expense_file').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#expense_file_preview_img').attr('src', e.target.result);
                        $('#expense_file_preview').removeClass('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    $('#expense_file_preview').addClass('hidden');
                }
            } else {
                $('#expense_file_preview').addClass('hidden');
            }
        });
        
        // Add / Update expense
        $('#expense_form').submit(function(e) {
            e.preventDefault();
            let id = $('#expense_id').val();
            let expense_name = $('#expense_name').val().trim();
            let amount = $('#amount').val();
            let expense_date = $('#expense_date').val();

            // Simple validation
            if (!expense_name) {
                show_notification('error', '<?= trans("messages.enter_expense_name", [], session("locale")) ?>');
                return;
            }
            if (!amount || parseFloat(amount) <= 0) {
                show_notification('error', '<?= trans("messages.enter_valid_amount", [], session("locale")) ?>');
                return;
            }
            if (!expense_date) {
                show_notification('error', '<?= trans("messages.enter_expense_date", [], session("locale")) ?>');
                return;
            }
            if (!$('#account_id').val()) {
                show_notification('error', '<?= trans("messages.select_account", [], session("locale")) ?>');
                return;
            }

            let url = id ? `{{ url('expenses') }}/${id}` : "{{ url('expenses') }}";

            // Create FormData for file upload
            let formData = new FormData(this);
            
            if (id) {
                formData.append('_method', 'PUT');
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    // Reset Alpine.js state using custom event
                    window.dispatchEvent(new CustomEvent('close-modal'));
                    
                    // Reset form
                    $('#expense_form')[0].reset();
                    $('#expense_id').val('');
                    $('#expense_file_preview').addClass('hidden');
                    loadExpenses();
                    show_notification(
                        'success',
                        id ?
                        '<?= trans("messages.updated_success", [], session("locale")) ?>' :
                        '<?= trans("messages.added_success", [], session("locale")) ?>'
                    );
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            show_notification('error', value[0]);
                        });
                    } else {
                        show_notification('error', '<?= trans("messages.generic_error", [], session("locale")) ?>');
                    }
                }
            });
        });

        // Close modal button
        $('#close_modal').click(function() {
            // Reset Alpine.js state using custom event
            window.dispatchEvent(new CustomEvent('close-modal'));
            // Reset form
            $('#expense_form')[0].reset();
            $('#expense_id').val('');
            $('#expense_file_preview').addClass('hidden');
        });

        // Edit expense
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.get("{{ url('expenses') }}/" + id, function(expense) {
                $('#expense_id').val(expense.id);
                $('#expense_name').val(expense.expense_name);
                $('#category_id').val(expense.category_id || '');
                $('#amount').val(expense.amount);
                
                // Format date for HTML date input (YYYY-MM-DD)
                let expenseDate = expense.expense_date;
                if (expenseDate) {
                    // If date includes time, extract only the date part
                    let dateObj = new Date(expenseDate);
                    if (!isNaN(dateObj.getTime())) {
                        let year = dateObj.getFullYear();
                        let month = String(dateObj.getMonth() + 1).padStart(2, '0');
                        let day = String(dateObj.getDate()).padStart(2, '0');
                        expenseDate = year + '-' + month + '-' + day;
                    } else if (expenseDate.includes(' ')) {
                        // If it's a string with time, take only the date part
                        expenseDate = expenseDate.split(' ')[0];
                    }
                }
                $('#expense_date').val(expenseDate);
                
                $('#account_id').val(expense.payment_method || '');
                $('#reciept_no').val(expense.reciept_no || '');
                $('#notes').val(expense.notes || '');

                // Show file preview if exists
                if (expense.expense_image) {
                    $('#expense_file_preview_img').attr('src', '{{ url("uploads/expense_files") }}/' + expense.expense_image);
                    $('#expense_file_preview').removeClass('hidden');
                } else {
                    $('#expense_file_preview').addClass('hidden');
                }
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Delete expense
        $(document).on('click', '.delete-btn', function() {
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
                        url: '<?= url("expenses") ?>/' + id,
                        method: 'DELETE',
                        data: {
                            _token: '<?= csrf_token() ?>'
                        },
                        success: function(data) {
                            loadExpenses(); // reload table
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

        // View/Download receipt
        $(document).on('click', '.view-receipt-btn', function() {
            let fileName = $(this).data('file');
            let fileUrl = '<?= url("uploads/expense_files") ?>/' + fileName;
            
            // Determine file type based on extension
            let fileExtension = fileName.split('.').pop().toLowerCase();
            let isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(fileExtension);
            let isPdf = fileExtension === 'pdf';
            
            if (isImage) {
                // For images, open in a modal
                Swal.fire({
                    title: 'Receipt',
                    html: `<img src="${fileUrl}" style="max-width: 100%; max-height: 70vh; border-radius: 8px;" alt="Receipt">`,
                    showCloseButton: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Download',
                    confirmButtonColor: '#3085d6',
                    showCancelButton: true,
                    cancelButtonText: 'Close',
                    width: 'auto',
                    padding: '20px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Download the image
                        let link = document.createElement('a');
                        link.href = fileUrl;
                        link.download = fileName;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                });
            } else if (isPdf) {
                // For PDFs, show options to view or download
                Swal.fire({
                    title: 'Receipt PDF',
                    text: 'Choose an action',
                    icon: 'info',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: 'View',
                    denyButtonText: 'Download',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#3085d6',
                    denyButtonColor: '#28a745'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Open PDF in new tab
                        window.open(fileUrl, '_blank');
                    } else if (result.isDenied) {
                        // Download PDF
                        let link = document.createElement('a');
                        link.href = fileUrl;
                        link.download = fileName;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                });
            } else {
                // For other file types, download directly
                let link = document.createElement('a');
                link.href = fileUrl;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        });

    });
</script>

