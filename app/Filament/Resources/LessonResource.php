<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonResource\Pages;
use App\Models\Course;
use App\Models\Lesson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Привязка к курсу')
    ->schema([
        // 1. ВЫБОР КУРСА (Виртуальное поле)
        Forms\Components\Select::make('course_id')
            ->label('Курс')
            ->options(function () {
                $query = \App\Models\Course::query();
                
                // Фильтр: Учитель видит только свои, Админ - все
                if (!auth()->user()->hasRole('Super Admin')) {
                    $query->where('teacher_id', auth()->id());
                }
                
                return $query->pluck('title', 'id');
            })
            ->required()
            ->live() // ВАЖНО: обновляет форму при изменении
            ->afterStateUpdated(fn (Forms\Set $set) => $set('module_id', null)) // Сбрасываем модуль при смене курса
            // Магия для режима Редактирования: вычисляем курс из модуля урока
            ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\Lesson $record) {
                if ($record && $record->module) {
                    $component->state($record->module->course_id);
                }
            })
            ->dehydrated(false), // Не пытаемся сохранить course_id в таблицу lessons

        // 2. ВЫБОР МОДУЛЯ (Зависит от курса)
        Forms\Components\Select::make('module_id')
            ->label('Модуль')
            ->options(function (Forms\Get $get) {
                // Берем ID выбранного выше курса
                $courseId = $get('course_id');

                if (!$courseId) {
                    return []; // Если курс не выбран — список пуст
                }

                // Грузим модули ТОЛЬКО этого курса
                return \App\Models\CourseModule::where('course_id', $courseId)
                    ->pluck('title', 'id');
            })
            ->searchable()
            ->required()
            // Если курс не выбран, блокируем выбор модуля
            ->disabled(fn (Forms\Get $get) => !$get('course_id')),
    ])->columns(2),

                Forms\Components\Section::make('Настройки урока')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Тема урока')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Длительность (мин)')
                            ->numeric()
                            ->default(15),

                        Forms\Components\Toggle::make('is_stop_lesson')
                            ->label('Стоп-урок')
                            ->helperText('Студент не пройдет дальше, пока не сдаст ДЗ')
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
        // 1. ВЫБОР ТИПА
        Forms\Components\Select::make('type')
            ->label('Тип блока')
            ->options([
                'text'      => 'Текст (HTML)',
                'image'     => 'Изображение',
                'file'      => 'Файл для скачивания',
                'separator' => '--- Разделитель ---', // Бонус: визуальная линия
                
                // Видео сервисы
                'video_youtube'   => 'Видео: YouTube',
                'video_rutube'    => 'Видео: RuTube',
                'video_vk'        => 'Видео: VK Видео',
                'video_kinescope' => 'Видео: Kinescope',
            ])
            ->default('text')
            ->live() // Важно: обновляет форму мгновенно при смене типа
            ->required(),
        
        // 2. ПОЛЯ ДЛЯ ТЕКСТА
        Forms\Components\RichEditor::make('content.html')
            ->label('Текст')
            ->visible(fn (Get $get) => $get('type') === 'text')
            ->required(fn (Get $get) => $get('type') === 'text')
            ->columnSpanFull(),

        // 3. ПОЛЕ ДЛЯ ССЫЛКИ (Работает для всех видео)
        Forms\Components\TextInput::make('content.url')
            ->label(fn (Get $get) => match($get('type')) {
                'video_kinescope' => 'ID видео или Ссылка',
                default => 'Ссылка на видео'
            })
            ->helperText(fn (Get $get) => match($get('type')) {
                'video_youtube'   => 'Например: https://www.youtube.com/watch?v=...',
                'video_rutube'    => 'Например: https://rutube.ru/video/.../',
                'video_vk'        => 'Важно: Нажмите "Поделиться" -> "Экспортировать" и скопируйте ссылку из src="..." (например: https://vk.com/video_ext.php?oid=...)',
                'video_kinescope' => 'Ссылка на плеер или ID видео',
                default => null,
            })
            // Показываем поле, если тип начинается со слова "video_"
            ->visible(fn (Get $get) => str_starts_with($get('type') ?? '', 'video_'))
            ->required(fn (Get $get) => str_starts_with($get('type') ?? '', 'video_'))
            ->columnSpanFull(),

        // 4. ПОЛЯ ДЛЯ КАРТИНКИ
        Forms\Components\FileUpload::make('content.image_path')
            ->label('Загрузить изображение')
            ->image()
            ->directory('lesson-images')
            ->visible(fn (Get $get) => $get('type') === 'image')
            ->columnSpanFull(),

        // 5. ПОЛЯ ДЛЯ ФАЙЛА
        Forms\Components\Grid::make(2)
            ->visible(fn (Get $get) => $get('type') === 'file')
            ->schema([
                Forms\Components\FileUpload::make('content.file_path')
                    ->label('Файл')
                    ->directory('lesson-files')
                    ->required(),
                Forms\Components\TextInput::make('content.file_name')
                    ->label('Название файла (для кнопки скачивания)')
                    ->required(),
            ]),
    ])
    ->collapsible()
    ->itemLabel(fn (array $state): ?string => 
        match($state['type'] ?? '') {
            'text' => 'Текст',
            'image' => 'Картинка',
            'file' => 'Файл: ' . ($state['content']['file_name'] ?? ''),
            'video_youtube' => 'YouTube',
            'video_rutube' => 'RuTube',
            'video_vk' => 'VK Video',
            'video_kinescope' => 'Kinescope',
            default => 'Блок'
        }
    ),
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
            // Сюда потом добавим Домашки
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('Super Admin')) {
            return $query;
        }

        if (auth()->user()->hasRole('Teacher')) {
            // Показываем уроки, у которых Модуль привязан к Курсу этого учителя
            // whereHas умеет работать через точку ('module.course')
            return $query->whereHas('module.course', function ($q) {
                $q->where('teacher_id', auth()->id());
            });
        }

        return $query->where('id', -1);
    }
}