<script>
function decodeHtmlEntities(str) {
    if (!str) return '';
    var txt = document.createElement('textarea');
    txt.innerHTML = str;
    return txt.value;
}

function openDetails(btnOrTransfer) {
    var transfer;
    if (typeof btnOrTransfer === 'object' && btnOrTransfer !== null && btnOrTransfer.getAttribute) {
        try {
            var raw = btnOrTransfer.getAttribute('data-transfer') || '{}';
            var decoded = decodeHtmlEntities(raw);
            transfer = JSON.parse(decoded);
        } catch (e) {
            console.error('Invalid transfer data', e);
            return;
        }
    } else {
        transfer = btnOrTransfer;
    }
    if (!transfer || !transfer.items) {
        transfer = transfer || {};
        transfer.items = Array.isArray(transfer.items) ? transfer.items : [];
    }
    const modal = document.getElementById('detailsModal');
    const content = document.getElementById('detailsContent');
    if (!modal || !content) return;
    
    let html = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.operation_number', [], session('locale')) }}</p>
                    <p class="font-bold">${transfer.no}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.date', [], session('locale')) }}</p>
                    <p class="font-bold">${transfer.date}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.from', [], session('locale')) }}</p>
                    <p class="font-bold">${transfer.from}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">{{ trans('messages.to', [], session('locale')) }}</p>
                    <p class="font-bold">${transfer.to}</p>
                </div>
            </div>
            ${transfer.note ? `<div><p class="text-sm text-gray-600">{{ trans('messages.notes', [], session('locale')) }}</p><p>${transfer.note}</p></div>` : ''}
            <div>
                <p class="text-sm text-gray-600 mb-2">{{ trans('messages.items_sent', [], session('locale')) }}</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border">
                        <thead class="bg-pink-50">
                            <tr>
                                <th class="px-3 py-2 text-right">{{ trans('messages.code', [], session('locale')) }}</th>
                                <th class="px-3 py-2 text-right">{{ trans('messages.color', [], session('locale')) }}</th>
                                <th class="px-3 py-2 text-right">{{ trans('messages.size', [], session('locale')) }}</th>
                                <th class="px-3 py-2 text-right">{{ trans('messages.quantity', [], session('locale')) }}</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    transfer.items.forEach(item => {
        html += `
            <tr class="border-t">
                <td class="px-3 py-2">${item.code}</td>
                <td class="px-3 py-2">${item.color || '—'}</td>
                <td class="px-3 py-2">${item.size || '—'}</td>
                <td class="px-3 py-2">${item.qty}</td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    content.innerHTML = html;
    modal.classList.remove('hidden');
}

function closeDetails() {
    var modal = document.getElementById('detailsModal');
    if (modal) modal.classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.movement-detail-btn');
        if (btn) {
            e.preventDefault();
            openDetails(btn);
        }
    });
});
</script>