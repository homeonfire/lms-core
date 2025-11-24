<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
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

    // Иконка в меню (можно выбрать любую на heroicons.com)
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    // Группа в меню (чтобы не было каши)
    protected static ?string $navigationGroup = 'Управление контентом';

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
    // Показываем только Админу. Учитель себя не выбирает, он и так автор.
    ->visible(fn () => auth()->user()->hasRole('Super Admin'))
    ->required()
    ->default(auth()->id()), // По умолчанию подставляем текущего юзера

                        Forms\Components\TextInput::make('title')
                            ->label('Название курса')
                            ->required()
                            ->maxLength(255)
                            // Магия: при вводе названия обновляем slug
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->label('Ссылка (slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true), // Уникальный, но не ругается на самого себя при редактировании

                        Forms\Components\Textarea::make('description')
                            ->label('Краткое описание')
                            ->columnSpanFull(),
                    ])->columns(2),

                // Секция: Медиа и Настройки
                Forms\Components\Section::make('Настройки и Цена')
                    ->schema([
                        Forms\Components\FileUpload::make('thumbnail_url')
                            ->label('Обложка курса')
                            ->image() // Разрешить только картинки
                            ->directory('course-thumbnails') // Папка хранения
                            ->visibility('public'),

                        Forms\Components\TextInput::make('price')
                            ->label('Цена (в копейках)')
                            ->numeric()
                            ->prefix('RUB')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Обложка')
                    ->circular(), // Круглая картинка

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
                    ->money('rub') // Авто-форматирование валюты
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Статус')
                    ->boolean(), // Покажет галочку или крестик

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Фильтр: показывать только опубликованные
                Tables\Filters\Filter::make('published')
                    ->query(fn ($query) => $query->where('is_published', true))
                    ->label('Только опубликованные'),
            ])
            ->actions([
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
        // Не забудь импортировать этот класс сверху (VS Code должен подсказать)
        // Если не подскажет: use App\Filament\Resources\CourseResource\RelationManagers\ModulesRelationManager;
        CourseResource\RelationManagers\ModulesRelationManager::class,
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

    // Добавь этот метод внутрь класса CourseResource
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Берем стандартный запрос
        $query = parent::getEloquentQuery();

        // Если это Админ — показываем всё
        if (auth()->user()->hasRole('Super Admin')) {
            return $query;
        }

        // Если это Учитель — фильтруем только ЕГО курсы
        if (auth()->user()->hasRole('Teacher')) {
            return $query->where('teacher_id', auth()->id());
        }
        
        // Всем остальным не показываем ничего (на всякий случай)
        return $query->where('id', -1);
    }
}