<?php

namespace App\Filament\Pages;

use App\Models\SystemSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Настройки ЮKassa';
    protected static ?string $title = 'Интеграция с ЮKassa';
    protected static ?string $navigationGroup = 'Настройки системы';
    
    protected static string $view = 'filament.pages.payment-settings';

    public ?array $data = [];

    public function mount(): void
    {
        // Загружаем настройки
        $settings = SystemSetting::whereIn('key', ['yookassa_shop_id', 'yookassa_secret_key', 'yookassa_enabled'])->pluck('payload', 'key');
        
        $this->form->fill([
            'yookassa_shop_id' => $settings['yookassa_shop_id'] ?? '',
            'yookassa_secret_key' => $settings['yookassa_secret_key'] ?? '',
            'yookassa_enabled' => (bool) ($settings['yookassa_enabled'] ?? false),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Доступы API')
                    ->description('Данные из личного кабинета ЮKassa (Раздел Интеграция -> Ключи API).')
                    ->schema([
                        Toggle::make('yookassa_enabled')
                            ->label('Включить оплату через ЮKassa на сайте'),

                        TextInput::make('yookassa_shop_id')
                            ->label('Shop ID')
                            ->required(),

                        TextInput::make('yookassa_secret_key')
                            ->label('Секретный ключ (Secret Key)')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('Начинается на live_ (боевой) или test_ (тестовый).'),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['group' => 'payments', 'payload' => $value]
            );
        }

        Notification::make()->title('Настройки сохранены')->success()->send();
    }
    
    protected function getViewData(): array
    {
        return [
            'webhookUrl' => url('/api/webhooks/yookassa'),
        ];
    }
}