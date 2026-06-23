<?php

namespace App\Filament\Resources\FunnelResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class StagesRelationManager extends RelationManager
{
    protected static string $relationship = 'stages';
    protected static ?string $title = 'Этапы воронки';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название этапа')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\Select::make('type')
                    ->label('Тип этапа')
                    ->options([
                        'regular' => 'Обычный этап',
                        'won' => 'Успешно реализован (Финальный)',
                        'lost' => 'Нереализован (Финальный)',
                    ])
                    ->required()
                    ->native(false),

                Forms\Components\ColorPicker::make('color')
                    ->label('Цвет этапа')
                    ->default('#3b82f6'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Порядок сортировки')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название этапа')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Тип этапа')
                    ->badge()
                    ->colors([
                        'success' => 'won',
                        'danger' => 'lost',
                        'primary' => 'regular',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'won' => 'Успешно реализован',
                        'lost' => 'Нереализован',
                        default => 'Обычный этап',
                    }),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Цвет'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Порядок')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
