<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Управление контентом';

    // === ДОБАВИТЬ ВОТ ЭТО ===
    protected static ?string $modelLabel = 'Курс'; // Ед. число (для кнопки "Создать Курс")
    protected static ?string $pluralModelLabel = 'Курсы'; // Мн. число (для заголовка)
    protected static ?string $navigationLabel = 'Курсы'; // В меню слева

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('Super Admin')) {
            return $query;
        }

        if (auth()->user()->hasRole('Teacher')) {
            return $query->where('teacher_id', auth()->id());
        }
        
        return $query->where('id', -1);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Секция: Основная информация
                Forms\Components\Section::make('О курсе')
                    ->schema([
                        Forms\Components\Select::make('teacher_id')
                            ->relationship('teacher', 'name')
                            ->label('Преподаватель')
                            ->disabled(fn () => !auth()->user()->hasRole('Super Admin'))
                            ->dehydrated() 
                            ->required()
                            ->default(auth()->id())
                            ->searchable()
                            ->preload(),

                        // === ВЕРНУЛИ ВЫБОР КУРАТОРОВ ===
                        Forms\Components\Select::make('curators')
                            ->label('Кураторы курса')
                            ->relationship('curators', 'name', function ($query) {
                                // Показываем только пользователей с ролью Curator
                                return $query->role('Curator');
                            })
                            ->multiple()
                            ->preload()
                            ->searchable(),
                        // ===============================

                        Forms\Components\TextInput::make('title')
                            ->label('Название курса')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Ссылка (slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->label('Краткое описание')
                            ->columnSpanFull(),
                    ])->columns(2),

                // Секция: Медиа и Настройки
                Forms\Components\Section::make('Настройки и Цена')
                    ->schema([
                        // Поле публичной ссылки
                        Forms\Components\TextInput::make('public_link')
                            ->label('Публичная ссылка (Лендинг)')
                            ->formatStateUsing(fn (?Course $record) => $record ? route('public.course.show', $record->slug) : null)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('open')
                                    ->icon('heroicon-m-arrow-top-right-on-square')
                                    ->url(fn (?Course $record) => $record ? route('public.course.show', $record->slug) : null)
                                    ->openUrlInNewTab()
                            )
                            ->visible(fn (?Course $record) => $record !== null),

                        Forms\Components\FileUpload::make('thumbnail_url')
                            ->label('Обложка курса')
                            ->image()
                            ->directory('course-thumbnails')
                            ->visibility('public'),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена (руб)')
                            ->numeric()
                            ->prefix('₽')
                            ->default(0),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('starts_at')
                                    ->label('Дата начала'),
                                Forms\Components\DateTimePicker::make('ends_at')
                                    ->label('Дата окончания'),
                            ]),
                            
                        Forms\Components\Toggle::make('is_published')
                            ->label('Опубликован')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false),
                    ])->columns(2),

                    Forms\Components\Section::make('Сообщество (Telegram)')
                    ->description('Общие ссылки (используются, если у тарифа нет своих ссылок)')
                    ->schema([
                        Forms\Components\TextInput::make('telegram_channel_link')
                            ->label('Канал курса')
                            ->prefix('https://t.me/')
                            ->url()
                            ->placeholder('https://t.me/channel_name'),
                            
                        Forms\Components\TextInput::make('telegram_chat_link')
                            ->label('Чат участников')
                            ->prefix('https://t.me/')
                            ->url()
                            ->placeholder('https://t.me/+InviteLink...'),
                    ])->columns(2)->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Обложка')
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Преподаватель')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('rub')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Статус')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('published')
                    ->query(fn ($query) => $query->where('is_published', true))
                    ->label('Только опубликованные'),
            ])
            ->actions([
                Tables\Actions\Action::make('public_link')
                    ->label('Лендинг')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (Course $record) => route('public.course.show', $record->slug))
                    ->openUrlInNewTab()
                    ->color('gray'),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ModulesRelationManager::class,
            RelationManagers\TariffsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}