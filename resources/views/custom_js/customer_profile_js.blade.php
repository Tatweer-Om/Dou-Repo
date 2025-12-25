<script>
function customerProfile() {
    return {
        tab: '{{ $specialOrdersCount > 0 ? "special_orders" : ($posOrdersCount > 0 ? "pos_orders" : "") }}',
        @foreach($specialOrders as $order)
        expandedOrder{{ $order->id }}: false,
        @endforeach
        @foreach($posOrders as $order)
        expandedPosOrder{{ $order->id }}: false,
        @endforeach
        init() {
            // Set initial tab based on available orders
            @if($specialOrdersCount > 0)
                this.tab = 'special_orders';
            @elseif($posOrdersCount > 0)
                this.tab = 'pos_orders';
            @endif
        }
    }
}
</script>

