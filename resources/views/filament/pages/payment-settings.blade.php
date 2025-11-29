<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
        
        <div class="flex justify-end">
            <x-filament::button type="submit">
                Сохранить настройки
            </x-filament::button>
        </div>
    </x-filament-panels::form>

    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-6">
        <h3 class="text-lg font-bold text-gray-950 dark:text-white flex items-center gap-2">
            <span>⚙️</span> Настройка Webhook (Notification)
        </h3>
        
        <div class="mt-4 space-y-4 text-sm text-gray-600 dark:text-gray-400">
            <p>Чтобы статус заказа менялся на "Оплачен" автоматически, настройте уведомления в кабинете ЮKassa.</p>
            
            <p class="flex items-center gap-2">
                URL для уведомлений: 
                <code class="px-2 py-1 bg-gray-100 dark:bg-gray-800 rounded text-primary-600 font-mono select-all cursor-pointer"
                      onclick="navigator.clipboard.writeText('{{ $webhookUrl }}'); alert('URL скопирован!')">
                    {{ $webhookUrl }}
                </code>
            </p>

            <div>
                <strong>События для подписки:</strong>
                <ul class="list-disc list-inside mt-2 ml-2 space-y-1">
                    <li><code>payment.succeeded</code> (Платеж прошел успешно)</li>
                    <li><code>payment.canceled</code> (Платеж отменен)</li>
                </ul>
            </div>
        </div>
    </div>
</x-filament-panels::page>