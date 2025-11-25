<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseModuleResource\Pages;
use App\Models\CourseModule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseModuleResource extends Resource
{
    protected static ?string $model = CourseModule::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Управление контентом';
    protected static ?string $navigationLabel = 'Все модули';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Настройки модуля')
                    ->schema([
                        // Выбор курса (обязательно)
                        Forms\Components\Select::make('course_id')
                            ->relationship('course', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(), // Обновляет форму при изменении

                        // Выбор родительского модуля (Зависит от выбранного курса!)
                        Forms\Components\Select::make('parent_id')
                            ->label('Родительский модуль (папка)')
                            ->options(function (Get $get, ?CourseModule $record) { // Исправили тип аргумента
                                // Берем ID курса, который выбрали выше
                                $courseId = $get('course_id');
                                if (!$courseId) return [];

                                $query = CourseModule::query()
                                    ->where('course_id', $courseId);

                                // ВАЖНОЕ ИСПРАВЛЕНИЕ:
                                // Если мы редактируем запись ($record существует),
                                // берем у неё ID ($record->id), а не весь объект.
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }

                                return $query->pluck('title', 'id');
                            })
                            ->searchable()
                            ->placeholder('Без родителя (Корневой модуль)'),

                        Forms\Components\Select::make('tariffs')
                            ->relationship('tariffs', 'name')
                            ->label('Доступно на тарифах')
                            ->multiple()
                            ->preload()
                            // ЛОГИКА: Показываем все тарифы ВЫБРАННОГО КУРСА
                            ->options(function (Forms\Get $get) {
                                $courseId = $get('course_id');
                                if (!$courseId) return [];
                                
                                return \App\Models\Tariff::where('course_id', $courseId)
                                    ->pluck('name', 'id');
                            })
                            ->helperText('Если пусто — модуль доступен для всех тарифов курса.'),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('course.title')
                    ->label('Курс')
                    ->sortable(),

                Tables\Columns\TextColumn::make('parent.title')
                    ->label('Вложен в')
                    ->placeholder('—'), // Если null, покажет прочерк

                Tables\Columns\TextColumn::make('lessons_count')
                    ->counts('lessons')
                    ->label('Уроков'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'title'),
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
            // Сюда мы добавим менеджер Уроков позже
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseModules::route('/'),
            'create' => Pages\CreateCourseModule::route('/create'),
            'edit' => Pages\EditCourseModule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('Super Admin')) {
            return $query;
        }

        if (auth()->user()->hasRole('Teacher')) {
            // Показываем модули, только если их курс принадлежит текущему учителю
            return $query->whereHas('course', function ($q) {
                $q->where('teacher_id', auth()->id());
            });
        }

        return $query->where('id', -1); // Остальным запрещаем
    }
}