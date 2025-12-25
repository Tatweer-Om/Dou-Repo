<script>
$(document).ready(function() {

    // ------------------ Translations ------------------
    const trans = {
        details: "{{ trans('messages.details', [], session('locale')) }}",
        edit: "{{ trans('messages.edit', [], session('locale')) }}",
        delete: "{{ trans('messages.delete', [], session('locale')) }}",
        unit: "{{ trans('messages.unit', [], session('locale')) }}",
        category: "{{ trans('messages.category', [], session('locale')) }}",
        size: "{{ trans('messages.size', [], session('locale')) }}",
        color: "{{ trans('messages.color', [], session('locale')) }}",
        quantity: "{{ trans('messages.quantity', [], session('locale')) }}"
    };

    // ------------------ Track state ------------------
    let currentPage = 1;
    let currentMaterialId = '';
    let currentSearch = '';

    // ------------------ Render Table & Mobile Cards ------------------
    function renderTable(materials) {
        let tableRows = '';
        let mobileCards = '';

        $.each(materials, function(_, material) {
            let image = material.material_image ? `/images/materials/${material.material_image}` : '';

            tableRows += `
                <tr class="border-t hover:bg-pink-50/60 transition" data-id="${material.id}">
                    <td class="px-3 py-3"><img src="${image}" class="w-12 h-16 object-cover rounded-md" /></td>
                    <td class="px-3 py-3 font-bold">${material.material_name}</td>
                    <td class="px-3 py-3">${material.unit ?? '-'}</td>
                    <td class="px-3 py-3">${material.category ?? '-'}</td>
                    <td class="px-3 py-3 font-bold">${material.rolls_count ?? '-'}</td>
                    <td class="px-3 py-3 font-bold">${material.meters_per_roll ?? '-'}</td>
                    <td class="px-3 py-3 font-bold">${material.sell_price ?? '-'}</td>
                    <td class="px-3 py-3">${material.buy_price ?? '-'}</td>
                    <td class="px-3 py-3 text-center">
                        <div class="flex justify-center gap-5 text-[12px] font-semibold text-gray-700">
                            <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition"
                                onclick="window.location.href='/edit_material/${material.id}'">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                                ${trans.edit}
                            </button>
                            <button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-material-btn">
                                <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                                ${trans.delete}
                            </button>
                        </div>
                    </td>
                </tr>
            `;

            // Mobile cards
            let size = material.sizes?.[0]?.size?.size_name_ar ?? '-';
            let color = material.colors?.[0]?.color?.color_name_ar ?? '-';
            let quantity = material.sizes?.[0]?.qty ?? 0;

            mobileCards += `
                <div class="bg-white border border-pink-100 rounded-2xl shadow-sm hover:shadow-md transition p-4 mb-4 md:hidden" data-id="${material.id}">
                    <h3 class="font-bold text-gray-900 truncate">${material.material_name}</h3>
                    <p>${trans.unit}: ${material.unit ?? '-'}</p>
                    <p>${trans.category}: ${material.category ?? '-'}</p>
                    <p>${trans.size}: ${size}</p>
                    <p>${trans.color}: ${color}</p>
                    <p>${trans.quantity}: ${quantity}</p>
                    <div class="flex justify-around mt-3 text-xs font-semibold">
                        <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition" onclick="window.location.href='/edit_material/${material.id}'">
                            <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>
                            ${trans.edit}
                        </button>
                        <button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-material-btn">
                            <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>
                            ${trans.delete}
                        </button>
                    </div>
                </div>
            `;
        });

        $("#desktop_material_body").html(tableRows);
        $("#mobile_material_cards").html(mobileCards);
    }

    // ------------------ Render Pagination ------------------
  function renderPagination(res) {
    let pagination = '';

    // Previous page
    pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full ${!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
                      <a href="?page=${res.current_page-1}">&laquo;</a>
                   </li>`;

    // Page numbers
    for (let i = 1; i <= res.last_page; i++) {
        pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full">
                           <a href="?page=${i}" class="flex items-center justify-center w-10 h-10 ${res.current_page == i ? 'bg-[var(--primary-color)] text-white' : 'bg-gray-200 hover:bg-gray-300'}">
                               ${i}
                           </a>
                       </li>`;
    }

    // Next page
    pagination += `<li class="w-10 h-10 flex items-center justify-center rounded-full ${!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
                      <a href="?page=${res.current_page+1}">&raquo;</a>
                   </li>`;

    $("#material_pagination").html(pagination);
}

    // ------------------ Load Materials ------------------
    function loadmaterial(page = 1, materialId = '', search = '') {
        currentPage = page;
        currentMaterialId = materialId;
        currentSearch = search;

        $.get("/material/list", { page: page, material_id: materialId }, function(res) {
            renderTable(res.data);
            renderPagination(res);

            if (currentSearch) {
                applySearch(currentSearch);
            }
        });
    }

    // ------------------ Pagination Click ------------------
    $(document).on("click", "#material_pagination a", function(e) {
        e.preventDefault();
        let href = $(this).attr("href");
        if (!href || href === "#") return;

        let page = new URL(href, window.location.origin).searchParams.get("page") || 1;
        loadmaterial(parseInt(page), currentMaterialId, currentSearch);
    });

    // ------------------ Client-side Search ------------------
    function applySearch(search) {
        $("#desktop_material_body tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().includes(search));
        });
        $("#mobile_material_cards > div").filter(function() {
            $(this).toggle($(this).text().toLowerCase().includes(search));
        });
    }

    $("#q").on("keyup", function() {
        currentSearch = $(this).val().toLowerCase();
        applySearch(currentSearch);
    });

    // ------------------ Delete Material ------------------
    $(document).on('click', '.delete-material-btn', function() {
        let id = $(this).closest('[data-id]').data('id');
        if (!id) return console.error('Material ID not found!');

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
                    url: '<?= url("delete_material") ?>/' + id,
                    method: 'DELETE',
                    data: { _token: '<?= csrf_token() ?>' },
                    success: function () {
                        Swal.fire(
                            '<?= trans("messages.deleted_success", [], session("locale")) ?>',
                            '<?= trans("messages.deleted_success_text", [], session("locale")) ?>',
                            'success'
                        );
                        loadmaterial(currentPage, currentMaterialId, currentSearch);
                    },
                    error: function () {
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

    // ------------------ Initial Load ------------------
    loadmaterial();
});
</script>
