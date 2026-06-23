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
            <!-- KANBAN BOARD -->
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
                                    class="p-4 bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-850 rounded-lg shadow-sm hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-600 transition duration-200 cursor-pointer active:cursor-grabbing relative group"
                                    draggable="true"
                                    x-on:dragstart="event.dataTransfer.setData('text/plain', '{{ $order->id }}')"
                                    wire:click="selectOrder({{ $order->id }})"
                                >
                                    <!-- Order ID & Edit Link -->
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-bold text-gray-400">Заказ #{{ $order->id }}</span>
                                        <a 
                                            href="{{ \App\Filament\Resources\OrderResource::getUrl('edit', ['record' => $order]) }}" 
                                            x-on:click.stop
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

                                    <!-- Tags List on Card -->
                                    @if(!empty($order->tags))
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($order->tags as $tag)
                                                <span class="px-1.5 py-0.5 bg-indigo-50 dark:bg-indigo-950/40 text-[9px] font-medium text-indigo-600 dark:text-indigo-400 rounded border border-indigo-100 dark:border-indigo-900/50">
                                                    {{ $tag }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

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
            class="fixed inset-0 bg-gray-950/60 backdrop-blur-sm transition-opacity"
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
            class="relative w-full max-w-5xl h-[90vh] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 flex flex-col overflow-hidden z-10 transition-all"
        >
            <!-- Header -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white flex items-center gap-2">
                        <span>Сделка #{{ $selectedOrderId }}</span>
                        @if($selectedOrder)
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400">
                                {{ $selectedOrder->course?->title }}
                            </span>
                        @endif
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Создан: {{ $selectedOrder?->created_at?->format('d.m.Y H:i') ?? '-' }}</p>
                </div>
                <button 
                    wire:click="closeOrder" 
                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6" />
                </button>
            </div>

            <!-- Body -->
            <div class="flex-grow overflow-y-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left side (2/3 width) - Customer & UTMS & Logs -->
                <div class="md:col-span-2 flex flex-col gap-6">
                    <!-- Customer Details -->
                    <div class="bg-gray-50/50 dark:bg-gray-950/20 p-5 rounded-xl border border-gray-200/60 dark:border-gray-800">
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                            <span>👤 Данные клиента</span>
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                          <div>
                              <span class="text-xs text-gray-400 block">ФИО</span>
                              <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                  {{ $selectedOrder?->user?->name ?? 'Удаленный клиент' }}
                              </span>
                          </div>
                          <div>
                              <span class="text-xs text-gray-400 block">Email</span>
                              @if($selectedOrder?->user?->email)
                                  <a href="mailto:{{ $selectedOrder->user->email }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                      {{ $selectedOrder->user->email }}
                                  </a>
                              @else
                                  <span class="text-sm text-gray-400">-</span>
                              @endif
                          </div>
                          <div>
                              <span class="text-xs text-gray-400 block">Телефон</span>
                              <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                  {{ $selectedOrder?->user?->phone ?? $selectedOrder?->phone ?? '-' }}
                              </span>
                          </div>
                          <div>
                              <span class="text-xs text-gray-400 block">Тариф курса</span>
                              <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                  {{ $selectedOrder?->tariff?->name ?? 'Без тарифа' }}
                              </span>
                          </div>
                        </div>
                    </div>

                    <!-- UTM Parameters -->
                    <div class="bg-gray-50/50 dark:bg-gray-950/20 p-5 rounded-xl border border-gray-200/60 dark:border-gray-800">
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3">🔗 UTM-метки (Источник трафика)</h4>
                        @if($selectedOrder && !empty($selectedOrder->utm_data))
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                @foreach(['utm_source' => 'Source', 'utm_medium' => 'Medium', 'utm_campaign' => 'Campaign', 'utm_content' => 'Content', 'utm_term' => 'Term'] as $key => $label)
                                    <div class="p-2 bg-white dark:bg-gray-900 rounded border border-gray-100 dark:border-gray-800">
                                        <span class="text-[9px] uppercase tracking-wider text-gray-400 block">{{ $label }}</span>
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 break-all">
                                            {{ $selectedOrder->utm_data[$key] ?? '-' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic">Нет доступных UTM-меток</p>
                        @endif
                    </div>

                    <!-- History log / Timeline -->
                    <div class="bg-gray-50/50 dark:bg-gray-950/20 p-5 rounded-xl border border-gray-200/60 dark:border-gray-800 flex-grow">
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-4">📜 Лог истории изменений</h4>
                        @if($selectedOrder && !empty($selectedOrder->history_log))
                            <div class="flex flex-col gap-3 max-h-[220px] overflow-y-auto pr-2">
                                @foreach($selectedOrder->history_log as $log)
                                    <div class="flex gap-3 text-xs leading-relaxed">
                                        <span class="text-gray-400 whitespace-nowrap">{{ $log['date'] ?? '-' }}</span>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">
                                            {{ $log['message'] ?? '-' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400 italic">История изменений пуста</p>
                        @endif
                    </div>
                </div>

                <!-- Right side (1/3 width) - Edit Form & Tags -->
                <div class="flex flex-col gap-6 border-l border-gray-200 dark:border-gray-800 pl-0 md:pl-6">
                    <!-- Deal parameters -->
                    <div>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-4">⚙️ Параметры сделки</h4>
                        <form wire:submit.prevent="saveOrderDetails" class="flex flex-col gap-4">
                            <!-- Amount -->
                            <div>
                                <label class="text-xs text-gray-400 block mb-1">Сумма заказа (₽)</label>
                                <input 
                                    type="number" 
                                    wire:model="editingOrderData.amount" 
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                    step="0.01"
                                    required
                                />
                            </div>

                            <!-- Manager -->
                            <div>
                                <label class="text-xs text-gray-400 block mb-1">Ответственный менеджер</label>
                                <select 
                                    wire:model="editingOrderData.manager_id" 
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <option value="">Без менеджера</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Stage -->
                            <div>
                                <label class="text-xs text-gray-400 block mb-1">Этап воронки</label>
                                <select 
                                    wire:model="editingOrderData.funnel_stage_id" 
                                    class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                                    required
                                >
                                    @foreach($stages as $stage)
                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    <!-- Tagging section -->
                    <div class="mt-2 border-t border-gray-100 dark:border-gray-800 pt-4">
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white mb-3">🏷️ Теги сделки</h4>
                        <!-- Tag badges -->
                        <div class="flex flex-wrap gap-1.5 mb-3">
                            @if(!empty($editingOrderData['tags']))
                                @foreach($editingOrderData['tags'] as $tag)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900">
                                        <span>{{ $tag }}</span>
                                        <button 
                                            type="button" 
                                            wire:click="removeTag('{{ $tag }}')"
                                            class="hover:text-red-500 focus:outline-none"
                                        >
                                            ×
                                        </button>
                                    </span>
                                @endforeach
                            @else
                                <span class="text-xs text-gray-400 italic">Теги не добавлены</span>
                            @endif
                        </div>

                        <!-- Add tag form -->
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                wire:model="newTag" 
                                wire:keydown.enter.prevent="addTag"
                                placeholder="Новый тег" 
                                class="flex-grow text-xs rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 text-gray-900 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                            />
                            <button 
                                type="button" 
                                wire:click="addTag"
                                class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-800 flex justify-end gap-3">
                <button 
                    type="button" 
                    wire:click="closeOrder" 
                    class="px-4 py-2 border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-850 rounded-lg text-sm font-semibold text-gray-700 dark:text-gray-300 transition"
                >
                    Отмена
                </button>
                <button 
                    type="button" 
                    wire:click="saveOrderDetails" 
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition"
                >
                    Сохранить
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
