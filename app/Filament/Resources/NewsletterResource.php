<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterResource\Pages;
use App\Jobs\SendNewsletterJob;
use App\Models\Course;
use App\Models\Newsletter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class NewsletterResource extends Resource
{
    protected static ?string $model = Newsletter::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Маркетинг';
    protected static ?string $navigationLabel = 'Рассылки';

    protected static ?string $modelLabel = 'Рассылку';
protected static ?string $pluralModelLabel = 'Рассылки';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ЛЕВАЯ КОЛОНКА: Контент
                Forms\Components\Section::make('Содержание')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Тема письма')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\RichEditor::make('content')
                            ->label('Текст рассылки')
                            ->required(),
                    ])->columnSpan(2),

                // ПРАВАЯ КОЛОНКА: Настройки
                Forms\Components\Section::make('Настройки отправки')
                    ->schema([
                        // Дата отправки
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Запланировать отправку')
                            ->helperText('Оставьте пустым для мгновенной отправки.')
                            ->minDate(now()),
                            
                        Forms\Components\Placeholder::make('status')
                            ->label('Текущий статус')
                            ->content(fn (?Newsletter $record) => $record ? $record->status : 'Черновик'),

                        Forms\Components\Toggle::make('recipients_filter.ignore_marketing')
                            ->label('Игнорировать согласие на маркетинг')
                            ->helperText('Включите для отправки важных системных уведомлений (согласие пользователей на рассылку будет проигнорировано).')
                            ->default(false)
                            ->live(),

                        Forms\Components\Placeholder::make('recipients_count')
                            ->label('Получателей к отправке')
                            ->content(function (Forms\Get $get) {
                                $filters = $get('recipients_filter') ?? [];
                                $count = self::getFilteredUsersCount($filters);
                                return self::pluralize($count);
                            }),
                    ])->columnSpan(1),

                // НИЖНЯЯ СЕКЦИЯ: Сегментация получателей
                Forms\Components\Section::make('Сегментация получателей')
                    ->description('Настройте правила включения и исключения пользователей для рассылки. Если правила не заданы, письмо получат все пользователи, согласившиеся на рассылку.')
                    ->schema([
                        Forms\Components\Tabs::make('FilterTabs')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Отправить группам (Включение)')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('recipients_filter.include_courses')
                                                    ->label('Студенты курсов')
                                                    ->options(fn () => \App\Models\Course::all()->pluck('title', 'id'))
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->live(),

                                                Forms\Components\Select::make('recipients_filter.include_tariffs')
                                                    ->label('Студенты тарифов')
                                                    ->options(fn () => \App\Models\Tariff::with('course')->get()->mapWithKeys(function ($t) {
                                                        return [$t->id => ($t->course?->title ?? 'Курс') . ' - ' . $t->name];
                                                    }))
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->live(),

                                                Forms\Components\Select::make('recipients_filter.include_roles')
                                                    ->label('Пользователи с ролями')
                                                    ->options(fn () => \Spatie\Permission\Models\Role::all()->pluck('name', 'name'))
                                                    ->multiple()
                                                    ->preload()
                                                    ->live(),

                                                Forms\Components\Select::make('recipients_filter.include_forms')
                                                    ->label('Заполнившие анкеты')
                                                    ->options(fn () => \App\Models\Form::all()->pluck('title', 'id'))
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->live(),
                                            ]),
                                    ]),

                                Forms\Components\Tabs\Tab::make('Исключить группы (Исключение)')
                                    ->icon('heroicon-o-x-circle')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('recipients_filter.exclude_courses')
                                                    ->label('Исключить студентов курсов')
                                                    ->options(fn () => \App\Models\Course::all()->pluck('title', 'id'))
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->live(),

                                                Forms\Components\Select::make('recipients_filter.exclude_tariffs')
                                                    ->label('Исключить студентов тарифов')
                                                    ->options(fn () => \App\Models\Tariff::with('course')->get()->mapWithKeys(function ($t) {
                                                        return [$t->id => ($t->course?->title ?? 'Курс') . ' - ' . $t->name];
                                                    }))
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->live(),

                                                Forms\Components\Select::make('recipients_filter.exclude_roles')
                                                    ->label('Исключить пользователей с ролями')
                                                    ->options(fn () => \Spatie\Permission\Models\Role::all()->pluck('name', 'name'))
                                                    ->multiple()
                                                    ->preload()
                                                    ->live(),

                                                Forms\Components\Select::make('recipients_filter.exclude_forms')
                                                    ->label('Исключить заполнивших анкеты')
                                                    ->options(fn () => \App\Models\Form::all()->pluck('title', 'id'))
                                                    ->multiple()
                                                    ->preload()
                                                    ->searchable()
                                                    ->live(),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('Тема')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'scheduled',
                        'primary' => 'processing',
                        'success' => 'sent',
                    ]),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Запланировано')
                    ->dateTime('d.m.Y H:i'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Отправлено')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // КНОПКА: Отправить сейчас / Запланировать
                Tables\Actions\Action::make('send')
                    ->label('Запустить рассылку')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Newsletter $record) => $record->status === 'draft')
                    ->action(function (Newsletter $record) {
                        
                        // Если есть дата - ставим статус Scheduled
                        if ($record->scheduled_at && $record->scheduled_at > now()) {
                            $record->update(['status' => 'scheduled']);
                            
                            // Используем задержку Laravel Queue
                            SendNewsletterJob::dispatch($record)->delay($record->scheduled_at);
                            
                            Notification::make()->title('Рассылка запланирована')->success()->send();
                        } 
                        // Иначе отправляем сразу
                        else {
                            SendNewsletterJob::dispatch($record);
                            
                            Notification::make()->title('Рассылка запущена в обработку')->success()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletters::route('/'),
            'create' => Pages\CreateNewsletter::route('/create'),
            'edit' => Pages\EditNewsletter::route('/{record}/edit'),
        ];
    }

    public static function getFilteredUsersCount(?array $filters): int
    {
        $query = \App\Models\User::query()
            ->whereNotNull('email');

        if (!$filters) {
            return $query->whereNotNull('accepted_marketing_at')->count();
        }

        if (empty($filters['ignore_marketing']) || !$filters['ignore_marketing']) {
            $query->whereNotNull('accepted_marketing_at');
        }

        // === ВКЛЮЧЕНИЯ (INCLUDES) ===

        // 1. Курсы и тарифы
        if (!empty($filters['include_courses']) || !empty($filters['include_tariffs'])) {
            $query->where(function ($q) use ($filters) {
                if (!empty($filters['include_courses'])) {
                    $q->whereHas('orders', function ($oq) use ($filters) {
                        $oq->whereIn('course_id', $filters['include_courses'])
                           ->where('status', 'paid');
                    });
                }
                if (!empty($filters['include_tariffs'])) {
                    if (!empty($filters['include_courses'])) {
                        $q->orWhereHas('orders', function ($oq) use ($filters) {
                            $oq->whereIn('tariff_id', $filters['include_tariffs'])
                               ->where('status', 'paid');
                        });
                    } else {
                        $q->whereHas('orders', function ($oq) use ($filters) {
                            $oq->whereIn('tariff_id', $filters['include_tariffs'])
                               ->where('status', 'paid');
                        });
                    }
                }
            });
        }

        // 2. Роли
        if (!empty($filters['include_roles'])) {
            $query->role($filters['include_roles']);
        }

        // 3. Анкеты
        if (!empty($filters['include_forms'])) {
            $query->whereHas('formSubmissions', function ($q) use ($filters) {
                $q->whereIn('form_id', $filters['include_forms']);
            });
        }

        // === ИСКЛЮЧЕНИЯ (EXCLUDES) ===

        // 1. Исключить курсы
        if (!empty($filters['exclude_courses'])) {
            $query->whereDoesntHave('orders', function ($q) use ($filters) {
                $q->whereIn('course_id', $filters['exclude_courses'])
                  ->where('status', 'paid');
            });
        }

        // 2. Исключить тарифы
        if (!empty($filters['exclude_tariffs'])) {
            $query->whereDoesntHave('orders', function ($q) use ($filters) {
                $q->whereIn('tariff_id', $filters['exclude_tariffs'])
                  ->where('status', 'paid');
            });
        }

        // 3. Исключить роли
        if (!empty($filters['exclude_roles'])) {
            $query->whereDoesntHave('roles', function ($q) use ($filters) {
                $q->whereIn('name', $filters['exclude_roles']);
            });
        }

        // 4. Исключить анкеты
        if (!empty($filters['exclude_forms'])) {
            $query->whereDoesntHave('formSubmissions', function ($q) use ($filters) {
                $q->whereIn('form_id', $filters['exclude_forms']);
            });
        }

        return $query->count();
    }

    private static function pluralize(int $count): string
    {
        $cases = [2, 0, 1, 1, 1, 2];
        $titles = ['пользователь', 'пользователя', 'пользователей'];
        return $count . ' ' . $titles[($count % 100 > 4 && $count % 100 < 20) ? 2 : $cases[min($count % 10, 5)]];
    }
}