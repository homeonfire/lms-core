<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Настройки системы';
    protected static ?string $navigationLabel = 'Пользователи';

    // === ГЛОБАЛЬНАЯ ФИЛЬТРАЦИЯ (КТО КОГО ВИДИТ) ===
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // 1. Супер-Админ видит всех
        if (auth()->user()->hasRole('Super Admin')) {
            return $query;
        }

        // 2. Учитель видит только СВОИХ студентов
        if (auth()->user()->hasRole('Teacher')) {
            // Логика: Найти пользователей, у которых есть ЗАКАЗ (Order)
            // на КУРС, автором которого является текущий учитель.
            return $query->whereHas('orders.course', function ($q) {
                $q->where('teacher_id', auth()->id());
            })
            // Опционально: можно добавить, чтобы учитель видел и самого себя
            ->orWhere('id', auth()->id());
        }

        // Остальные не видят никого
        return $query->where('id', -1);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Данные пользователя')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            // Уникальность, игнорируя текущего юзера при редактировании
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->maxLength(255)
                            // Показываем поле required только при создании
                            ->required(fn (string $operation): bool => $operation === 'create')
                            // Хэшируем пароль перед сохранением
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            // Если поле пустое при редактировании — не обновляем пароль
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn () => auth()->user()->hasRole('Super Admin')), // Пароли меняет только Админ

                        // Выбор РОЛИ (Только для Админа)
                        Forms\Components\Select::make('roles')
                            ->label('Роль')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn () => auth()->user()->hasRole('Super Admin')),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активен (Бан)')
                            ->default(true)
                            ->visible(fn () => auth()->user()->hasRole('Super Admin')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Аватар')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?background=random'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                // Показываем роль красивым бейджиком
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Роль')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Super Admin' => 'danger',
                        'Teacher' => 'warning',
                        'Student' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Регистрация')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // === ФИЛЬТР ПО РОЛЯМ ===
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Роль')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Удаление доступно ТОЛЬКО Супер-Админу
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('Super Admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('Super Admin')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Не забудь импортировать класс (VS Code подскажет)
            UserResource\RelationManagers\OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}