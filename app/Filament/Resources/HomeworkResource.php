<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeworkResource\Pages;
use App\Models\Homework;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomeworkResource extends Resource
{
    protected static ?string $model = Homework::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Управление контентом';
    protected static ?string $navigationLabel = 'Домашние задания';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Привязка')
                    ->schema([
                        Forms\Components\Select::make('lesson_id')
                            ->options(function () {
        $query = \App\Models\Lesson::query();

        // Если учитель — показываем только уроки из его курсов
        if (!auth()->user()->hasRole('Super Admin')) {
            $query->whereHas('module.course', function ($q) {
                $q->where('teacher_id', auth()->id());
            });
        }

        return $query->pluck('title', 'id');
    })
                            ->label('К какому уроку')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('В одном уроке может быть только одно задание'),
                        
                        Forms\Components\Toggle::make('is_required')
                            ->label('Обязательное задание')
                            ->default(true)
                            ->helperText('Если включено — стоп-урок не пустит дальше без сдачи'),
                    ])->columns(2),

                Forms\Components\Section::make('Суть задания')
                    ->schema([
                        Forms\Components\RichEditor::make('description')
                            ->label('Текст задания')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Форма ответа студента')
                    ->description('Сконструируйте поля, которые должен заполнить студент')
                    ->schema([
                        Forms\Components\Repeater::make('submission_fields')
                            ->label('Поля для ответа')
                            ->schema([
                                // Выбор типа поля
                                Forms\Components\Select::make('type')
                                    ->label('Тип поля')
                                    ->options([
                                        'text' => 'Длинный текст (Editor)',
                                        'string' => 'Короткая строка',
                                        'file' => 'Загрузка файла',
                                        'url' => 'Ссылка (URL)',
                                    ])
                                    ->required()
                                    ->reactive(), // Чтобы менять иконку или настройки динамически

                                // Подпись поля
                                Forms\Components\TextInput::make('label')
                                    ->label('Название поля')
                                    ->placeholder('Например: Ссылка на GitHub')
                                    ->required(),
                                
                                // Обязательно ли?
                                Forms\Components\Toggle::make('required')
                                    ->label('Обязательно')
                                    ->default(true),
                            ])
                            ->columns(3) // В одну строку для компактности
                            ->addActionLabel('Добавить поле ответа')
                            ->defaultItems(1), // По умолчанию одно текстовое поле
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lesson.module.course.title')
                    ->label('Курс')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lesson.title')
                    ->label('Урок')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_required')
                    ->label('Обязательное')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomework::route('/'),
            'create' => Pages\CreateHomework::route('/create'),
            'edit' => Pages\EditHomework::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('Super Admin')) {
            return $query;
        }

        if (auth()->user()->hasRole('Teacher')) {
            // Показываем только те ДЗ, которые привязаны к курсам этого учителя
            return $query->whereHas('lesson.module.course', function ($q) {
                $q->where('teacher_id', auth()->id());
            });
        }

        return $query->where('id', -1);
    }
}