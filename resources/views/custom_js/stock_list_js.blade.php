<script>
    $(document).ready(function() {

        // Translations
        const trans = {
            details: "تفاصيل",
            enter_quantity: "إدخال كميات",
            edit: "تعديل",
            delete: "حذف"
        };

        function loadStock(page = 1) {
            $.get("/stock/list", {
                page: page
            }, function(res) {

                // --- Desktop Table ---
                let tableRows = "";
                $.each(res.data, function(index, stock) {
                    let image = stock.images.length ? stock.images[0].image_path : '';

                    let size = '-';
                    if (stock.sizes.length && stock.sizes[0].size) {
                        size = stock.sizes[0].size.size_name_ar; // or size_name_en
                    }

                    let color = '-';
                    if (stock.colors.length && stock.colors[0].color) {
                        color = stock.colors[0].color.color_name_ar; // or color_name_en
                    }

                    let quantity = stock.sizes.length ? stock.sizes[0].qty : 0;

                    tableRows += `
                <tr class="border-t hover:bg-pink-50/60 transition" data-id="${stock.id}">
                    <td class="px-3 py-3">
                        <img src="${image}" class="w-12 h-16 object-cover rounded-md" />
                    </td>
                    <td class="px-3 py-3 font-bold">${stock.abaya_code}</td>
                    <td class="px-3 py-3">${stock.design_name ?? '-'}</td>
                    <td class="px-3 py-3">${size}</td>
                    <td class="px-3 py-3">${color}</td>
                    <td class="px-3 py-3 font-bold">${quantity}</td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex justify-center gap-5 text-[12px] font-semibold text-gray-700">
<button class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition"
        x-on:click="$dispatch('open-stock-details', ${stock.id})">
    <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>
    ${trans.details}
</button>
            <button class="flex flex-col items-center gap-1 hover:text-green-600 transition"
                                    onclick="alert('Enter quantity for stock ID: ${stock.id}')">
                                <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>
                                ${trans.enter_quantity}
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
                    let size = stock.sizes.length && stock.sizes[0].size ? stock.sizes[0].size.size_name_ar : '-';
                    let color = stock.colors.length && stock.colors[0].color ? stock.colors[0].color.color_name_ar : '-';
                    let quantity = stock.sizes.length ? stock.sizes[0].qty : 0;

                    mobileCards += `
                <div class="bg-white border border-pink-100 rounded-2xl shadow-sm hover:shadow-md transition p-4 mb-4 md:hidden">
                    <h3 class="font-bold text-gray-900 truncate">${stock.abaya_code}</h3>
                    <p>تصميم: ${stock.design_name ?? '-'}</p>
                    <p>المقاس: ${size}</p>
                    <p>اللون: ${color}</p>
                    <p>الكمية: ${quantity}</p>
                    <div class="flex justify-around mt-3 text-xs font-semibold">
   <button class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition"
        x-on:click="$dispatch('open-stock-details', ${stock.id})">
    <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>
    ${trans.details}
</button>

                        <button class="flex flex-col items-center gap-1 hover:text-green-600 transition"
                                onclick="alert('Enter quantity for stock ID: ${stock.id}')">
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
        $("#search_stock").on("keyup", function() {
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
            stock: null,

            openStockDetails(id) {
                this.loading = true;
                this.showDetails = true;
                this.stock = null; // important: clear old data

                fetch(`/stock/${id}`)
                    .then(response => response.json())
                    .then(result => {
                        console.log('Backend response:', result);

                        // This is the ONLY correct line for your backend
                        this.stock = result.data;

                        this.loading = false;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('فشل تحميل التفاصيل');
                        this.loading = false;
                        this.showDetails = false;
                    });
            },

            mainImage() {
                return this.stock?.images?.[0]?.image_path || '/placeholder.jpg';
            }
        }
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
</script>