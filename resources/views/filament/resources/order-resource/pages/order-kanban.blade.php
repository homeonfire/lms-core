<x-filament-panels::page>
    <!-- CUSTOM KANBAN STYLES -->
    <style>
        .kanban-board-container {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.3) transparent;
        }
        .kanban-board-container::-webkit-scrollbar {
            height: 6px;
        }
        .kanban-board-container::-webkit-scrollbar-track {
            background: transparent;
        }
        .kanban-board-container::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.3);
            border-radius: 10px;
        }
        .kanban-board-container::-webkit-scrollbar-thumb:hover {
            background-color: rgba(148, 163, 184, 0.5);
        }
        .kanban-card {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            padding-left: 18px !important; /* Spacing for stage color line */
        }
        .kanban-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: var(--stage-color, #94a3b8);
            border-radius: 12px 0 0 12px;
        }
        .kanban-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -8px rgba(99, 102, 241, 0.15), 0 8px 12px -6px rgba(0, 0, 0, 0.05);
        }
        .dark .kanban-card:hover {
            box-shadow: 0 12px 20px -8px rgba(99, 102, 241, 0.4), 0 8px 12px -6px rgba(0, 0, 0, 0.3);
        }
        .timeline-item {
            position: relative;
            padding-left: 24px;
            padding-bottom: 16px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 4px;
            top: 5px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            z-index: 1;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: 7px;
            top: 12px;
            bottom: 0;
            width: 2px;
            background-color: #e2e8f0;
        }
        .dark .timeline-item::after {
            background-color: #334155;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-item:last-child::after {
            display: none;
        }

        /* Explicit Modal Styling to ensure perfect visibility and premium aesthetics */
        .custom-modal-backdrop {
            background-color: rgba(15, 23, 42, 0.65) !important;
            backdrop-filter: blur(8px) !important;
            -webkit-backdrop-filter: blur(8px) !important;
        }
        .custom-modal-content {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
        }
        .dark .custom-modal-content {
            background-color: #111827 !important;
            border: 1px solid #1f2937 !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important;
        }
        .custom-modal-header {
            background-color: #f8fafc !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }
        .dark .custom-modal-header {
            background-color: #111827 !important;
            border-bottom: 1px solid #1f2937 !important;
        }
        .custom-modal-footer {
            background-color: #f8fafc !important;
            border-top: 1px solid #e2e8f0 !important;
        }
        .dark .custom-modal-footer {
            background-color: #111827 !important;
            border-top: 1px solid #1f2937 !important;
        }
        .custom-modal-section {
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%) !important;
            border: 1px solid #e2e8f0 !important;
        }
        .dark .custom-modal-section {
            background: linear-gradient(135deg, rgba(31, 41, 55, 0.2) 0%, rgba(17, 24, 39, 0.4) 100%) !important;
            border: 1px solid #1f2937 !important;
        }
        .custom-utm-card {
            background-color: #ffffff !important;
            border: 1px solid #e5e7eb !important;
        }
        .dark .custom-utm-card {
            background-color: #111827 !important;
            border: 1px solid #1f2937 !important;
        }
        .custom-input {
            background-color: #ffffff !important;
            border: 1px solid #d1d5db !important;
            color: #111827 !important;
        }
        .dark .custom-input {
            background-color: #111827 !important;
            border: 1px solid #1f2937 !important;
            color: #f3f4f6 !important;
        }
        
        /* Premium Buttons explicitly colored to prevent default style overrides */
        .custom-btn-primary {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border-radius: 0.75rem !important;
            padding: 0.625rem 1.5rem !important;
            font-size: 0.875rem !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: none !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1) !important;
        }
        .custom-btn-primary:hover {
            background-color: #4338ca !important;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3), 0 4px 6px -2px rgba(79, 70, 229, 0.15) !important;
            transform: translateY(-1px) !important;
        }
        .custom-btn-primary:active {
            transform: translateY(0) !important;
        }
        .custom-btn-primary-sm {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            border-radius: 0.75rem !important;
            padding: 0.5rem 1.25rem !important;
            font-size: 0.75rem !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: none !important;
            cursor: pointer !important;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.1) !important;
        }
        .custom-btn-primary-sm:hover {
            background-color: #4338ca !important;
        }
        .custom-btn-secondary {
            background-color: #ffffff !important;
            color: #374151 !important;
            font-weight: 700 !important;
            border-radius: 0.75rem !important;
            padding: 0.625rem 1.5rem !important;
            font-size: 0.875rem !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border: 1px solid #d1d5db !important;
            cursor: pointer !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .custom-btn-secondary:hover {
            background-color: #f3f4f6 !important;
            color: #111827 !important;
        }
        .dark .custom-btn-secondary {
            background-color: #1f2937 !important;
            color: #e5e7eb !important;
            border: 1px solid #4b5563 !important;
        }
        .dark .custom-btn-secondary:hover {
            background-color: #374151 !important;
            color: #ffffff !important;
        }

        /* Timeline and scrollbar styling */
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.3);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: rgba(99, 102, 241, 0.5);
        }
    </style>

    <div class="flex flex-col gap-6">
        <!-- HEADER CONTROLS -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 shadow-sm">
            <div class="flex items-center gap-4">
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Воронка:</span>
                <div class="relative">
                    <select wire:model.live="funnelId" class="rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-950 text-sm font-bold focus:ring-2 focus:ring-primary-500 focus:border-primary-500 min-w-[240px] text-gray-800 dark:text-gray-100 pr-10 pl-4 py-2 appearance-none">
                        @foreach($funnels as $funnel)
                            <option value="{{ $funnel->id }}">{{ $funnel->name }} {{ $funnel->is_active ? ' (Активна)' : '' }}</option>
                        @endforeach
                        @if($funnels->isEmpty())
                            <option value="">Нет созданных воронок</option>
                        @endif
                    </select>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <x-filament::button 
                    href="{{ \App\Filament\Resources\OrderResource::getUrl('index') }}" 
                    tag="a" 
                    color="gray" 
                    icon="heroicon-o-table-cells"
                    class="rounded-xl"
                >
                    Табличный вид
                </x-filament::button>
            </div>
        </div>

        @if($stages->isEmpty())
            <div class="text-center py-16 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm">
                <div class="text-gray-400 mb-3">
                    <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-14 w-14 mx-auto" />
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Воронка пуста</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Добавьте этапы для выбранной воронки продаж.</p>
            </div>
        @else
            <!-- KANBAN BOARD -->
            <div class="kanban-board-container flex gap-4 overflow-x-auto pb-6" style="min-height: 650px;">
                @foreach($stages as $stage)
                    <div 
                        class="flex-shrink-0 w-80 flex flex-col bg-gray-50/80 dark:bg-gray-900/30 rounded-2xl border border-gray-200/60 dark:border-gray-800/80 overflow-hidden shadow-sm backdrop-blur-sm"
                        style="border-top: 4px solid {{ $stage->color ?? '#94a3b8' }}; flex-shrink: 0;"
                    >
                        <!-- Column Header -->
                        <div class="px-4 py-3 bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800/50 flex justify-between items-center">
                            <span class="font-bold text-sm text-gray-800 dark:text-gray-200 truncate pr-2" title="{{ $stage->name }}">{{ $stage->name }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-gray-100 dark:bg-gray-850 text-gray-600 dark:text-gray-400">
                                {{ $stage->orders->count() }}
                            </span>
                        </div>

                        <!-- Column Body (Draggable Drop Area) -->
                        <div 
                            class="flex-grow p-3 flex flex-col gap-3 overflow-y-auto"
                            style="min-height: 480px;"
                            x-on:dragover.prevent="$el.classList.add('bg-indigo-50/50', 'dark:bg-indigo-950/10')"
                            x-on:dragleave="$el.classList.remove('bg-indigo-50/50', 'dark:bg-indigo-950/10')"
                            x-on:drop="$el.classList.remove('bg-indigo-50/50', 'dark:bg-indigo-950/10'); const orderId = event.dataTransfer.getData('text/plain'); $wire.updateOrderStage(orderId, '{{ $stage->id }}')"
                        >
                            @foreach($stage->orders as $order)
                                <div 
                                    class="kanban-card p-4 bg-white dark:bg-gray-950 border border-gray-200/80 dark:border-gray-850 rounded-xl shadow-sm cursor-pointer active:cursor-grabbing relative"
                                    style="--stage-color: {{ $stage->color ?? '#94a3b8' }};"
                                    draggable="true"
                                    x-on:dragstart="event.dataTransfer.setData('text/plain', '{{ $order->id }}')"
                                    wire:click="selectOrder({{ $order->id }})"
                                >
                                    <!-- Order ID & Edit Link -->
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-bold text-gray-400">#{{ $order->id }}</span>
                                        <a 
                                            href="{{ \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $order]) }}" 
                                            x-on:click.stop
                                            class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline flex items-center gap-0.5 transition"
                                        >
                                            Карточка
                                            <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3 w-3" />
                                        </a>
                                    </div>

                                    <!-- Client Name -->
                                    <h4 class="font-extrabold text-sm text-gray-900 dark:text-gray-100 line-clamp-1">
                                        {{ $order->user?->name ?? 'Удаленный клиент' }}
                                    </h4>

                                    <!-- Email / Phone -->
                                    @if($order->user?->email)
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">{{ $order->user->email }}</p>
                                    @endif

                                    <!-- Course Title -->
                                    <div class="mt-3">
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-800/80 text-[10px] font-semibold text-gray-700 dark:text-gray-300 rounded-lg block truncate" title="{{ $order->course?->title }}">
                                            📚 {{ $order->course?->title ?? 'Курс удален' }}
                                        </span>
                                        @if($order->tariff)
                                            <span class="text-[9px] text-indigo-500 dark:text-indigo-400 font-bold block mt-1">
                                                ★ Тариф: {{ $order->tariff->name }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Tags List on Card -->
                                    @if(!empty($order->tags))
                                        <div class="flex flex-wrap gap-1 mt-2.5">
                                            @foreach($order->tags as $tag)
                                                <span class="px-2 py-0.5 bg-indigo-50/50 dark:bg-indigo-950/30 text-[9px] font-bold text-indigo-600 dark:text-indigo-400 rounded-full border border-indigo-100/50 dark:border-indigo-900/50">
                                                    {{ $tag }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- Amount & Manager -->
                                    <div class="flex justify-between items-center mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                                        <span class="font-black text-sm text-indigo-600 dark:text-indigo-400">
                                            {{ number_format($order->amount / 100, 0, '.', ' ') }} ₽
                                        </span>
                                        
                                        @if($order->manager)
                                            <span 
                                                class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1 bg-gray-50 dark:bg-gray-900 px-2 py-0.5 rounded-full border border-gray-200/50 dark:border-gray-700/50"
                                                title="Ответственный менеджер: {{ $order->manager->name }}"
                                            >
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                {{ explode(' ', $order->manager->name)[0] }}
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">Без мен.</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if($stage->orders->isEmpty())
                                <div class="flex-grow flex items-center justify-center border-2 border-dashed border-gray-200 dark:border-gray-800/80 rounded-xl py-10 text-center text-gray-400 text-xs">
                                    Перетащите заказ сюда
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- AMO_CRM DETAILS MODAL -->
    <div 
        x-data="{ isOpen: @entangle('selectedOrderId') }"
        x-show="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 md:p-10"
        style="display: none;"
    >
        <!-- Backdrop -->
        <div 
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 custom-modal-backdrop transition-opacity"
            wire:click="closeOrder"
        ></div>

        <!-- Modal Card Content -->
        <div 
            x-show="isOpen"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-5xl h-[88vh] custom-modal-content rounded-2xl flex flex-col overflow-hidden z-10 transition-all"
        >
            <!-- Header -->
            <div class="px-6 py-5 custom-modal-header flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="text-xl font-black text-gray-900 dark:text-white">
                            Сделка #{{ $selectedOrderId }}
                        </h3>
                        @if($selectedOrder)
                            <span class="px-3 py-1 text-xs font-extrabold rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-100/50 dark:border-indigo-900/50">
                                {{ $selectedOrder->course?->title }}
                            </span>
                        @endif
                    </div>
                    <p class="text-[10px] uppercase tracking-wider font-extrabold text-gray-400 dark:text-gray-500 mt-2">
                        Создан: <span class="text-gray-600 dark:text-gray-300 font-bold">{{ $selectedOrder?->created_at?->format('d.m.Y H:i') ?? '-' }}</span>
                    </p>
                </div>
                <button 
                    wire:click="closeOrder" 
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-grow overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left side (2/3 width) - Customer & UTMS & Logs -->
                <div class="md:col-span-2 flex flex-col gap-6">
                    <!-- Customer Details -->
                    <div class="custom-modal-section p-6 rounded-2xl shadow-sm">
                        <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-gray-100 dark:border-gray-800/80">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white uppercase tracking-wider">Данные клиента</h4>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">
                            <div class="flex flex-col gap-1 p-3 bg-gray-50/50 dark:bg-gray-950/20 rounded-xl border border-gray-100/50 dark:border-gray-800/30">
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">ФИО</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                    {{ $selectedOrder?->user?->name ?? 'Удаленный клиент' }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 p-3 bg-gray-50/50 dark:bg-gray-950/20 rounded-xl border border-gray-100/50 dark:border-gray-800/30">
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Email</span>
                                @if($selectedOrder?->user?->email)
                                    <a href="mailto:{{ $selectedOrder->user->email }}" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:underline truncate">
                                        {{ $selectedOrder->user->email }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400 font-bold">-</span>
                                @endif
                            </div>
                            <div class="flex flex-col gap-1 p-3 bg-gray-50/50 dark:bg-gray-950/20 rounded-xl border border-gray-100/50 dark:border-gray-800/30">
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Телефон</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                    {{ $selectedOrder?->user?->phone ?? $selectedOrder?->phone ?? '-' }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 p-3 bg-gray-50/50 dark:bg-gray-950/20 rounded-xl border border-gray-100/50 dark:border-gray-800/30">
                                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Тариф курса</span>
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200">
                                    {{ $selectedOrder?->tariff?->name ?? 'Без тарифа' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- UTM Parameters -->
                    <div class="custom-modal-section p-6 rounded-2xl shadow-sm">
                        <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-gray-100 dark:border-gray-800/80">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                            </svg>
                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white uppercase tracking-wider">UTM-метки (Источник трафика)</h4>
                        </div>
                        @if($selectedOrder && !empty($selectedOrder->utm_data))
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                @foreach(['utm_source' => 'Source', 'utm_medium' => 'Medium', 'utm_campaign' => 'Campaign', 'utm_content' => 'Content', 'utm_term' => 'Term'] as $key => $label)
                                    <div class="p-3 custom-utm-card rounded-xl shadow-sm flex flex-col gap-1">
                                        <span class="text-[9px] uppercase tracking-wider font-extrabold text-gray-400 dark:text-gray-500">{{ $label }}</span>
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 break-all leading-tight">
                                            {{ $selectedOrder->utm_data[$key] ?? '-' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-center justify-center p-4 bg-gray-50/50 dark:bg-gray-950/20 rounded-xl border border-dashed border-gray-200 dark:border-gray-800 text-center text-gray-400 dark:text-gray-500 text-xs font-bold gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                Нет UTM-меток
                            </div>
                        @endif
                    </div>

                    <!-- History log / Timeline -->
                    <div class="custom-modal-section p-6 rounded-2xl shadow-sm flex-grow">
                        <div class="flex items-center gap-2.5 mb-5 pb-3 border-b border-gray-100 dark:border-gray-800/80">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white uppercase tracking-wider">Лог истории изменений</h4>
                        </div>
                        @if($selectedOrder && !empty($selectedOrder->history_log))
                            <div class="flex flex-col gap-1 max-h-[220px] overflow-y-auto pr-2 custom-scrollbar">
                                @foreach($selectedOrder->history_log as $log)
                                    <div class="timeline-item text-xs relative">
                                        <span class="text-[10px] text-indigo-500 dark:text-indigo-400 font-extrabold block mb-0.5">{{ $log['date'] ?? '-' }}</span>
                                        <span class="font-bold text-gray-700 dark:text-gray-300 leading-normal">
                                            {{ $log['message'] ?? '-' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-center justify-center p-8 bg-gray-50/50 dark:bg-gray-950/20 rounded-xl border border-dashed border-gray-200 dark:border-gray-800 text-center text-gray-400 dark:text-gray-500 text-xs font-bold gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                История изменений пуста
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right side (1/3 width) - Edit Form & Tags -->
                <div class="flex flex-col gap-6 border-l border-gray-205 dark:border-gray-800 pl-0 md:pl-6">
                    <!-- Deal parameters -->
                    <div>
                        <div class="flex items-center gap-2 mb-5 pb-3 border-b border-gray-100 dark:border-gray-800">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                            </svg>
                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white uppercase tracking-wider">Параметры сделки</h4>
                        </div>
                        <div class="flex flex-col gap-5">
                            <!-- Amount -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Сумма заказа (₽)</label>
                                <input 
                                    type="number" 
                                    wire:model="editingOrderData.amount" 
                                    class="w-full text-sm rounded-xl custom-input focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 font-bold py-2.5 px-4"
                                    step="0.01"
                                    required
                                />
                            </div>

                            <!-- Manager -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Ответственный менеджер</label>
                                <select 
                                    wire:model="editingOrderData.manager_id" 
                                    class="w-full text-sm rounded-xl custom-input focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 font-bold py-2.5 px-4"
                                >
                                    <option value="">Без менеджера</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Stage -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Этап воронки</label>
                                <select 
                                    wire:model="editingOrderData.funnel_stage_id" 
                                    class="w-full text-sm rounded-xl custom-input focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 font-bold py-2.5 px-4"
                                    required
                                >
                                    @foreach($stages as $stage)
                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tagging section -->
                    <div class="mt-2 border-t border-gray-200 dark:border-gray-800 pt-5">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white uppercase tracking-wider">Теги сделки</h4>
                        </div>
                        <!-- Tag badges -->
                        <div class="flex flex-wrap gap-1.5 mb-4" style="min-height: 28px;">
                            @if(!empty($editingOrderData['tags']))
                                @foreach($editingOrderData['tags'] as $tag)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900 transition">
                                        <span>{{ $tag }}</span>
                                        <button 
                                            type="button" 
                                            wire:click="removeTag('{{ $tag }}')"
                                            class="hover:text-red-500 focus:outline-none ml-0.5 text-sm font-bold leading-none"
                                        >
                                            &times;
                                        </button>
                                    </span>
                                @endforeach
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">Теги не добавлены</span>
                            @endif
                        </div>

                        <!-- Add tag form -->
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                wire:model="newTag" 
                                wire:keydown.enter.prevent="addTag"
                                placeholder="Новый тег" 
                                class="flex-grow text-sm rounded-xl custom-input focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-200 font-semibold py-2 px-3"
                            />
                            <button 
                                type="button" 
                                wire:click="addTag"
                                class="custom-btn-primary-sm"
                            >
                                Добавить
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 custom-modal-footer flex justify-end gap-3">
                <button 
                    type="button" 
                    wire:click="closeOrder" 
                    class="custom-btn-secondary"
                >
                    Отмена
                </button>
                <button 
                    type="button" 
                    wire:click="saveOrderDetails" 
                    class="custom-btn-primary"
                >
                    Сохранить
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
