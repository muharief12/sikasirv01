<div class="grid grid-cols-1 dark:bg-gray-100 md:grid-cols-3 gap-4">
    <div class="md:col-span-2 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        <div class="mb-4 flex gap-2">
            <input wire:model.live.debounce.350ms='search' type="text" placeholder="Cari produk..."
                class="w-full p-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
            <x-filament::button x-data="" x-on:click="$dispatch('toggle-scanner')" color="primary">Scan Barcode</x-filament::button>
            <livewire:scanner-modal-component />
        </div>
        <div class="flex-grow">
            <div class="grid grid-cols-8 sm:grid-cols-3 md:grid-cols-8 lg:grid-cols- gap-4">
                @forelse ($products as $item)
                <div wire:click="addToOrder({{ $item->id }})" class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow cursor-pointer">
                    <img src="{{ Storage::url($item->image) ?? 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'}}"
                        alt="Product Image" class="w-full h-16 object-cover rounded-lg mb-2">
                    <h3 class="text-sm font-semibold">{{ $item->name }}</h3>
                    <p class="text-gray-600 dark:text-gray-400 text-xs">Rp {{ number_format($item->price, '0', ',', '.')}}</p>
                    <p class="text-gray-600 dark:text-gray-400 text-xs">Stok: {{ $item->stock }}</p>
                </div>
                @empty
                <p class="text-white text-center bg-red-500 rounded-full">Sorry, the product is empty</p>
                @endforelse
            </div>
            <div class="py-4">
                {{ $products->links() }}
            </div>
        </div>
    </div>
    <div class="md:col-span-1 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
        @if (count($order_items) > 0)
        <div class="py-4">
            <h3 class="text-lg font-semibold text-center">Total: Rp {{ number_format($this->calculateTotal(), 0, ',','.')}}</h3>
        </div>
        @endif
        @foreach ($order_items as $item)
        <div class="mb-4">
            <div class="flex justify-between items-center bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow">
                <div class="flex items-center">
                    <img src="{{ Storage::url($item['image']) ?? 'https://images.unsplash.com/photo-1464226184884-fa280b87c399?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'}}" alt="Product Image"
                        class="w-10 h-10 object-cover rounded-lg mr-2">
                    <div class="px-2">
                        <h3 class="text-sm font-semibold">{{ $item['name']}}</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-xs">Rp {{number_format($item['price'], 0, ',','.')}}</p>
                    </div>
                </div>
                <div class="flex items-center">
                    <x-filament::button wire:click="decQty({{ $item['product_id'] }})" color="warning">-</x-filament::button>
                    <span class="px-4">{{ $item['qty']}}</span>
                    <x-filament::button wire:click="incQty({{ $item['product_id'] }})" color="success">+</x-filament::button>
                </div>
            </div>
        </div>
        @endforeach

        <form wire:submit="checkout">
            {{ $this->form }}
            <x-filament::button type="submit" color="success" class="w-full bg-success-500 mt-3 text-white py-2 rounded">Checkout</x-filament::button>
        </form>
        <div class="mt-2">

        </div>
    </div>
</div>


#Mengaktifkan cdn qr-code html5
<script src="https://unpkg.com/html5-qrcode"></script>