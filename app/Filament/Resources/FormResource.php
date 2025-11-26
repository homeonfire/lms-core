<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FormResource\Pages;
use App\Filament\Resources\FormResource\RelationManagers; // <--- Правильный импорт
use App\Models\Form as FormModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FormResource extends Resource
{
    protected static ?string $model = FormModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $navigationLabel = 'Анкеты и Формы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ОСНОВНОЕ
                Forms\Components\Section::make('Настройки')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Название анкеты')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Ссылка (URL)')
                            ->prefix(url('/f/'))
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),
                    ])->columns(2),

                // КОНСТРУКТОР ПОЛЕЙ
                Forms\Components\Section::make('Поля анкеты')
                    ->schema([
                        Forms\Components\Repeater::make('schema')
                            ->label('Список полей')
                            ->schema([
                                // Тип поля
                                Forms\Components\Select::make('type')
                                    ->label('Тип')
                                    ->options([
                                        'text' => 'Текст (Строка)',
                                        'textarea' => 'Текст (Абзац)',
                                        'email' => 'Email (для регистрации)',
                                        'phone' => 'Телефон (в профиль)',
                                        'select' => 'Выпадающий список',
                                    ])
                                    ->required()
                                    ->live(),

                                // Название поля
                                Forms\Components\TextInput::make('label')
                                    ->label('Вопрос / Название')
                                    ->required()
                                    ->placeholder('Например: Ваш опыт в годах?'),

                                // Техническое имя (ключ)
                                Forms\Components\TextInput::make('name')
                                    ->label('Переменная (English)')
                                    ->required()
                                    ->placeholder('experience_years')
                                    ->helperText('Уникальное имя поля латиницей'),

                                // Обязательность
                                Forms\Components\Toggle::make('required')
                                    ->label('Обязательное поле')
                                    ->default(true),

                                // Опции (только для Select)
                                Forms\Components\TagsInput::make('options')
                                    ->label('Варианты ответов')
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'select')
                                    ->placeholder('Введите вариант и нажмите Enter'),
                            ])
                            ->columns(2)
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                    ]),

                // ДОП. НАСТРОЙКИ
                Forms\Components\Section::make('Тексты')
                    ->schema([
                        Forms\Components\TextInput::make('settings.submit_text')
                            ->label('Текст на кнопке')
                            ->default('Отправить'),
                        Forms\Components\TextInput::make('settings.success_message')
                            ->label('Сообщение после отправки')
                            ->default('Спасибо! Ваша заявка принята.'),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Название')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Ссылка')->prefix('/f/'),
                Tables\Columns\TextColumn::make('submissions_count')
                    ->counts('submissions')
                    ->label('Заявок'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Кнопка открытия
                Tables\Actions\Action::make('open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (FormModel $record) => route('public.form.show', $record->slug))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Теперь Filament найдет этот класс
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForms::route('/'),
            'create' => Pages\CreateForm::route('/create'),
            'edit' => Pages\EditForm::route('/{record}/edit'),
        ];
    }
}