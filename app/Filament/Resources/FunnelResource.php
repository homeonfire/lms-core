<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FunnelResource\Pages;
use App\Filament\Resources\FunnelResource\RelationManagers;
use App\Models\Funnel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FunnelResource extends Resource
{
    protected static ?string $model = Funnel::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $navigationGroup = 'Продажи';
    protected static ?string $navigationLabel = 'Воронки продаж';
    protected static ?string $modelLabel = 'Воронка';
    protected static ?string $pluralModelLabel = 'Воронки продаж';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Параметры воронки')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название воронки')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активна')
                            ->helperText('Новые заказы будут автоматически привязываться к первому этапу активной воронки.')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название воронки')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Активна'),

                Tables\Columns\TextColumn::make('stages_count')
                    ->counts('stages')
                    ->label('Количество этапов')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\ToggledFilter::make('is_active')
                    ->label('Только активные'),
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
            RelationManagers\StagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFunnels::route('/'),
            'create' => Pages\CreateFunnel::route('/create'),
            'edit' => Pages\EditFunnel::route('/{record}/edit'),
        ];
    }
}
