@extends('layouts.app')

@section('title', 'Order Management - Admin Dashboard')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Order Management System</h1>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-blue-500 text-white p-4 rounded-lg">
            <p class="text-sm">Pending Orders</p>
            <p class="text-3xl font-bold" id="pending-count">0</p>
        </div>
        <div class="bg-yellow-500 text-white p-4 rounded-lg">
            <p class="text-sm">In Preparation</p>
            <p class="text-3xl font-bold" id="preparing-count">0</p>
        </div>
        <div class="bg-green-500 text-white p-4 rounded-lg">
            <p class="text-sm">Out for Delivery</p>
            <p class="text-3xl font-bold" id="delivery-count">0</p>
        </div>
        <div class="bg-purple-500 text-white p-4 rounded-lg">
            <p class="text-sm">Total Today</p>
            <p class="text-3xl font-bold" id="total-count">0</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg mb-6 shadow">
        <label class="block mb-2">Filter by Status:</label>
        <select id="status-filter" class="px-4 py-2 border rounded-lg">
            <option value="">All Orders</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="preparing">Preparing</option>
            <option value="ready">Ready</option>
            <option value="on_way">On The Way</option>
            <option value="delivered">Delivered</option>
        </select>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-6 py-3 text-left">Order ID</th>
                    <th class="px-6 py-3 text-left">Customer</th>
                    <th class="px-6 py-3 text-left">Items</th>
                    <th class="px-6 py-3 text-left">Total</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Time</th>
                    <th class="px-6 py-3 text-left">Actions</th>
                </tr>
            </thead>
            <tbody id="orders-list">
                <tr class="text-center py-8">
                    <td colspan="7" class="text-gray-500">Loading orders...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Update Status Modal -->
<div id="update-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
        <h2 class="text-xl font-bold mb-4">Update Order Status</h2>
        
        <div class="mb-4">
            <p class="text-sm text-gray-600 mb-2">Order ID: <span id="modal-order-id"></span></p>
            <label class="block mb-2">New Status:</label>
            <select id="modal-status" class="w-full px-4 py-2 border rounded-lg">
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="preparing">Preparing</option>
                <option value="ready">Ready for Pickup</option>
                <option value="on_way">On The Way</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button onclick="closeModal()" class="flex-1 px-4 py-2 bg-gray-300 rounded-lg">Cancel</button>
            <button onclick="saveStatus()" class="flex-1 px-4 py-2 bg-blue-500 text-white rounded-lg">Update</button>
        </div>
    </div>
</div>

<style>
    .status-badge {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .status-pending { background-color: #fbbf24; color: #78350f; }
    .status-confirmed { background-color: #60a5fa; color: #1e3a8a; }
    .status-preparing { background-color: #a78bfa; color: #3f0f5c; }
    .status-ready { background-color: #34d399; color: #065f46; }
    .status-on_way { background-color: #f87171; color: #7f1d1d; }
    .status-delivered { background-color: #10b981; color: #065f46; }
</style>

<script>
    const restaurantId = {{ $restaurantId ?? 'null' }};
    let currentOrder = null;

    async function loadOrders() {
        if (!restaurantId) {
            document.getElementById('orders-list').innerHTML = '<tr><td colspan="7" class="text-center py-4">Restaurant ID not set</td></tr>';
            return;
        }

        try {
            const response = await fetch(`/api/restaurant/${restaurantId}/orders`);
            const data = await response.json();
            
            if (!data.orders || data.orders.length === 0) {
                document.getElementById('orders-list').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-gray-500">No pending orders</td></tr>';
                return;
            }

            let statusCounts = {};
            let html = '';

            data.orders.forEach(order => {
                statusCounts[order.status] = (statusCounts[order.status] || 0) + 1;

                let itemsText = order.notes ? order.notes : 'Order taken via chat';
                if (Array.isArray(order.items) && order.items.length > 0) {
                    itemsText = order.items.join(', ');
                }
                const items = itemsText;
                const time = new Date(order.created_at).toLocaleTimeString();

                html += `
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4">#${order.id}</td>
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-semibold">${order.customer_name || 'Guest'}</p>
                                <p class="text-sm text-gray-500">${order.customer_phone}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">${items}</td>
                        <td class="px-6 py-4 font-semibold">Rs. ${order.total}</td>
                        <td class="px-6 py-4">
                            <span class="status-badge status-${order.status}">${order.status.toUpperCase()}</span>
                        </td>
                        <td class="px-6 py-4 text-sm">${time}</td>
                        <td class="px-6 py-4">
                            <button onclick="openStatusModal(${order.id}, '${order.status}')" class="text-blue-500 hover:underline">Update</button>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('orders-list').innerHTML = html;

            // Update stats
            document.getElementById('pending-count').innerHTML = statusCounts['pending'] || 0;
            document.getElementById('preparing-count').innerHTML = statusCounts['preparing'] || 0;
            document.getElementById('delivery-count').innerHTML = statusCounts['on_way'] || 0;
            document.getElementById('total-count').innerHTML = data.orders.length;

        } catch (error) {
            console.error('Error loading orders:', error);
            document.getElementById('orders-list').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-red-500">Error loading orders</td></tr>';
        }
    }

    function openStatusModal(orderId, currentStatus) {
        currentOrder = orderId;
        document.getElementById('modal-order-id').innerHTML = orderId;
        document.getElementById('modal-status').value = currentStatus;
        const modal = document.getElementById('update-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        const modal = document.getElementById('update-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        currentOrder = null;
    }

    async function saveStatus() {
        if (!currentOrder) return;

        const newStatus = document.getElementById('modal-status').value;

        try {
            const response = await fetch(`/api/orders/${currentOrder}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ status: newStatus })
            });

            const data = await response.json();
            if (data.success) {
                closeModal();
                loadOrders(); // Refresh table
                alert('Order status updated!');
            }
        } catch (error) {
            console.error('Error updating status:', error);
            alert('Error updating order status');
        }
    }

    // Auto-refresh orders every 3 seconds
    loadOrders();
    setInterval(loadOrders, 3000);

    // Filter by status
    document.getElementById('status-filter').addEventListener('change', (e) => {
        const filter = e.target.value;
        const rows = document.querySelectorAll('#orders-list tr');
        
        rows.forEach(row => {
            if (!filter) {
                row.style.display = '';
            } else {
                const status = row.querySelector('.status-badge')?.textContent.toLowerCase();
                row.style.display = status?.startsWith(filter.toUpperCase()) ? '' : 'none';
            }
        });
    });
</script>
@endsection
