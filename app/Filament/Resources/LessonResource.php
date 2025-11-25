<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Models\Lesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Управление контентом';
    protected static ?string $navigationLabel = 'Уроки';

    // Глобальный фильтр (Scope)
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('Super Admin')) {
            return $query;
        }

        if (auth()->user()->hasRole('Teacher')) {
            return $query->whereHas('module.course', function ($q) {
                $q->where('teacher_id', auth()->id());
            });
        }

        return $query->where('id', -1);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Привязка к курсу')
                    ->schema([
                        // 1. ВЫБОР КУРСА
                        Forms\Components\Select::make('course_id')
                            ->label('Курс')
                            ->options(function () {
                                $query = \App\Models\Course::query();
                                if (!auth()->user()->hasRole('Super Admin')) {
                                    $query->where('teacher_id', auth()->id());
                                }
                                return $query->pluck('title', 'id');
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('module_id', null);
                                $set('tariffs', []); 
                            })
                            ->dehydrated(false) // Не сохраняем в БД (виртуальное поле)
                            ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\Lesson $record) {
                                if ($record && $record->module) {
                                    $component->state($record->module->course_id);
                                }
                            }),

                        // 2. ВЫБОР МОДУЛЯ
                        Forms\Components\Select::make('module_id')
                            ->label('Модуль')
                            ->options(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) return [];
                                return \App\Models\CourseModule::where('course_id', $courseId)->pluck('title', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->disabled(fn (Forms\Get $get) => !$get('course_id'))
                            // АВТО-ЗАПОЛНЕНИЕ ПРИ СМЕНЕ МОДУЛЯ
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                $set('tariffs', []); // Сбрасываем
                                
                                if ($state) {
                                    $module = \App\Models\CourseModule::with('tariffs')->find($state);
                                    // Если у модуля жесткие тарифы - копируем их в урок по умолчанию
                                    if ($module && $module->tariffs->isNotEmpty()) {
                                        $set('tariffs', $module->tariffs->pluck('id')->toArray());
                                    }
                                }
                            }),

                        // 3. ВЫБОР ТАРИФОВ (СУЖЕНИЕ ВОРОНКИ)
                        Forms\Components\Select::make('tariffs')
                            ->relationship('tariffs', 'name')
                            ->label('Доступно на тарифах')
                            ->multiple()
                            ->preload()
                            ->options(function (Forms\Get $get) {
                                // Логика: Берем тарифы МОДУЛЯ. 
                                // Если у модуля нет ограничений — берем тарифы КУРСА.
                                
                                $moduleId = $get('module_id');
                                $courseId = $get('course_id');

                                if (!$moduleId) return [];

                                $module = \App\Models\CourseModule::with('tariffs')->find($moduleId);
                                
                                // 1. Если модуль ограничен конкретными тарифами — разрешаем выбирать ТОЛЬКО их
                                if ($module && $module->tariffs->isNotEmpty()) {
                                    return $module->tariffs->pluck('name', 'id');
                                }

                                // 2. Если модуль открыт для всех — показываем все тарифы курса
                                return \App\Models\Tariff::where('course_id', $courseId)->pluck('name', 'id');
                            })
                            ->helperText('Список ограничен тарифами, доступными для выбранного модуля.'),
                            
                    ])->columns(2),

                Forms\Components\Section::make('Настройки урока')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Тема урока')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (мин)')
                            ->numeric()
                            ->default(15),

                        Forms\Components\Toggle::make('is_stop_lesson')
                            ->label('Стоп-урок')
                            ->helperText('Студент не пройдет дальше, пока не сдаст ДЗ и Тесты')
                            ->default(false),
                    ])->columns(2),

                // === КОНСТРУКТОР КОНТЕНТА ===
                Forms\Components\Section::make('Содержание урока')
                    ->schema([
                        Forms\Components\Repeater::make('blocks')
                            ->relationship()
                            ->label('Блоки контента')
                            ->orderColumn('sort_order')
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->label('Тип блока')
                                    ->options([
                                        'text'      => 'Текст (HTML)',
                                        'image'     => 'Изображение',
                                        'file'      => 'Файл для скачивания',
                                        'separator' => '--- Разделитель ---',
                                        'quiz'      => '⚡ Тест / Квиз',
                                        'video_youtube'   => 'Видео: YouTube',
                                        'video_rutube'    => 'Видео: RuTube',
                                        'video_vk'        => 'Видео: VK Видео',
                                        'video_kinescope' => 'Видео: Kinescope',
                                    ])
                                    ->default('text')
                                    ->live()
                                    ->required(),
                                
                                // ТЕКСТ
                                Forms\Components\RichEditor::make('content.html')
                                    ->label('Текст')
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'text')
                                    ->required(fn (Forms\Get $get) => $get('type') === 'text')
                                    ->columnSpanFull(),

                                // ВИДЕО
                                Forms\Components\TextInput::make('content.url')
                                    ->label(fn (Forms\Get $get) => match($get('type')) {
                                        'video_kinescope' => 'ID видео или Ссылка',
                                        default => 'Ссылка на видео'
                                    })
                                    ->helperText(fn (Forms\Get $get) => match($get('type')) {
                                        'video_youtube'   => 'Например: https://www.youtube.com/watch?v=...',
                                        'video_rutube'    => 'Например: https://rutube.ru/video/.../',
                                        'video_vk'        => 'Важно: Нажмите "Поделиться" -> "Экспортировать" и скопируйте ссылку из src="..."',
                                        'video_kinescope' => 'Ссылка на плеер или ID видео',
                                        default => null,
                                    })
                                    ->visible(fn (Forms\Get $get) => str_starts_with($get('type') ?? '', 'video_'))
                                    ->required(fn (Forms\Get $get) => str_starts_with($get('type') ?? '', 'video_'))
                                    ->columnSpanFull(),

                                // КАРТИНКА
                                Forms\Components\FileUpload::make('content.image_path')
                                    ->label('Изображение')
                                    ->image()
                                    ->directory('lesson-images')
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'image')
                                    ->columnSpanFull(),

                                // ФАЙЛ
                                Forms\Components\Grid::make(2)
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'file')
                                    ->schema([
                                        Forms\Components\FileUpload::make('content.file_path')
                                            ->label('Файл')
                                            ->directory('lesson-files')
                                            ->required(),
                                        Forms\Components\TextInput::make('content.file_name')
                                            ->label('Название файла')
                                            ->required(),
                                    ]),

                                // === НАСТРОЙКИ ТЕСТА ===
                                Forms\Components\Group::make()
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'quiz')
                                    ->schema([
                                        Forms\Components\Section::make('Конструктор теста')
                                            ->schema([
                                                Forms\Components\TextInput::make('content.min_score')
                                                    ->label('Минимальный % прохождения')
                                                    ->numeric()
                                                    ->default(70)
                                                    ->minValue(1)
                                                    ->maxValue(100)
                                                    ->required()
                                                    ->helperText('Если студент наберет меньше, тест будет считаться не сданным.'),

                                                Forms\Components\Repeater::make('content.questions')
                                                    ->label('Вопросы')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('question')
                                                            ->label('Вопрос')
                                                            ->required(),
                                                        
                                                        Forms\Components\Repeater::make('answers')
                                                            ->label('Ответы')
                                                            ->schema([
                                                                Forms\Components\TextInput::make('text')
                                                                    ->label('Вариант ответа')
                                                                    ->required(),
                                                                Forms\Components\Toggle::make('is_correct')
                                                                    ->label('Верный')
                                                                    ->default(false),
                                                            ])
                                                            ->minItems(2)
                                                            ->columns(2),
                                                    ])
                                                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                                                    ->collapsed(),
                                            ]),
                                    ]),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => match($state['type'] ?? '') {
                                'text' => 'Текст',
                                'quiz' => 'Тест',
                                'video_youtube' => 'YouTube',
                                'video_rutube' => 'RuTube',
                                'video_vk' => 'VK Video',
                                'video_kinescope' => 'Kinescope',
                                'image' => 'Картинка',
                                'file' => 'Файл: ' . ($state['content']['file_name'] ?? ''),
                                default => 'Блок'
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('module.course.title')
                    ->label('Курс')
                    ->sortable(),

                Tables\Columns\TextColumn::make('module.title')
                    ->label('Модуль'),

                Tables\Columns\TextColumn::make('tariffs.name')
                    ->label('Тарифы')
                    ->badge()
                    ->color('success')
                    ->placeholder('Все'),

                Tables\Columns\IconColumn::make('is_stop_lesson')
                    ->label('Стоп-урок')
                    ->boolean(),

                Tables\Columns\TextColumn::make('blocks_count')
                    ->counts('blocks')
                    ->label('Блоков'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('module.course', 'title')
                    ->label('Фильтр по курсу'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}