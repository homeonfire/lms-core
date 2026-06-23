<x-filament-panels::page>
    <div class="flex flex-col gap-6">
        <!-- HEADER CONTROLS -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Выберите воронку:</span>
                <select wire:model.live="funnelId" class="rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm focus:ring-primary-500 focus:border-primary-500 min-w-[200px] text-gray-900 dark:text-gray-100">
                    @foreach($funnels as $funnel)
                        <option value="{{ $funnel->id }}">{{ $funnel->name }} {{ $funnel->is_active ? ' (Активна)' : '' }}</option>
                    @endforeach
                    @if($funnels->isEmpty())
                        <option value="">Нет созданных воронок</option>
                    @endif
                </select>
            </div>
            
            <div class="flex items-center gap-2">
                <x-filament::button 
                    href="{{ \App\Filament\Resources\OrderResource::getUrl('index') }}" 
                    tag="a" 
                    color="gray" 
                    icon="heroicon-o-table-cells"
                >
                    Табличный вид
                </x-filament::button>
            </div>
        </div>

        @if($stages->isEmpty())
            <div class="text-center py-12 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="text-gray-400 mb-2">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-12 w-12 mx-auto" />
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Воронка пуста</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Добавьте этапы для выбранной воронки продаж.</p>
            </div>
        @else
            <!-- KANBAN BOARD BOARD -->
            <div class="flex gap-4 overflow-x-auto pb-6" style="min-height: 600px;">
                @foreach($stages as $stage)
                    <div 
                        class="flex-shrink-0 w-80 flex flex-col bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200/80 dark:border-gray-800 overflow-hidden shadow-sm"
                        style="border-top: 4px solid {{ $stage->color ?? '#94a3b8' }}"
                    >
                        <!-- Column Header -->
                        <div class="p-3 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                            <span class="font-bold text-sm text-gray-800 dark:text-gray-200 truncate pr-2">{{ $stage->name }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                {{ $stage->orders->count() }}
                            </span>
                        </div>

                        <!-- Column Body (Draggable Drop Area) -->
                        <div 
                            class="flex-grow p-3 flex flex-col gap-3 overflow-y-auto"
                            style="min-height: 450px;"
                            x-on:dragover.prevent="$el.classList.add('bg-indigo-50/50', 'dark:bg-indigo-950/20')"
                            x-on:dragleave="$el.classList.remove('bg-indigo-50/50', 'dark:bg-indigo-950/20')"
                            x-on:drop="$el.classList.remove('bg-indigo-50/50', 'dark:bg-indigo-950/20'); const orderId = event.dataTransfer.getData('text/plain'); $wire.updateOrderStage(orderId, '{{ $stage->id }}')"
                        >
                            @foreach($stage->orders as $order)
                                <div 
                                    class="p-4 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-850 rounded-lg shadow-sm hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-600 transition duration-200 cursor-grab active:cursor-grabbing relative group"
                                    draggable="true"
                                    x-on:dragstart="event.dataTransfer.setData('text/plain', '{{ $order->id }}')"
                                >
                                    <!-- Order ID & Edit Link -->
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-bold text-gray-400">Заказ #{{ $order->id }}</span>
                                        <a 
                                            href="{{ \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $order]) }}" 
                                            class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-0.5"
                                        >
                                            Изменить
                                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3 w-3" />
                                        </a>
                                    </div>

                                    <!-- Client Name -->
                                    <h4 class="font-bold text-sm text-gray-900 dark:text-gray-100 line-clamp-1">
                                        {{ $order->user?->name ?? 'Удаленный клиент' }}
                                    </h4>

                                    <!-- Email / Phone -->
                                    @if($order->user?->email)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ $order->user->email }}</p>
                                    @endif

                                    <!-- Course Title -->
                                    <div class="mt-3">
                                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-[10px] font-bold text-gray-700 dark:text-gray-300 rounded-md block truncate">
                                            {{ $order->course?->title ?? 'Курс удален' }}
                                        </span>
                                        @if($order->tariff)
                                            <span class="text-[10px] text-indigo-500 dark:text-indigo-400 font-bold block mt-1">
                                                Тариф: {{ $order->tariff->name }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Amount & Manager -->
                                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                                        <span class="font-bold text-sm text-gray-900 dark:text-gray-100">
                                            {{ number_format($order->amount / 100, 0, '.', ' ') }} ₽
                                        </span>
                                        
                                        @if($order->manager)
                                            <span 
                                                class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1 bg-gray-50 dark:bg-gray-900 px-1.5 py-0.5 rounded border border-gray-200/50 dark:border-gray-700/50"
                                                title="Ответственный менеджер: {{ $order->manager->name }}"
                                            >
                                                👤 {{ explode(' ', $order->manager->name)[0] }}
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-400">Без мен.</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if($stage->orders->isEmpty())
                                <div class="flex-grow flex items-center justify-center border-2 border-dashed border-gray-200 dark:border-gray-800 rounded-lg py-8 text-center text-gray-400 text-xs">
                                    Перетащите заказ сюда
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
