<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model          = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int    $navigationSort  = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plan details')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(60),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])->columns(2),

            Forms\Components\Section::make('Limits')->schema([
                Forms\Components\TextInput::make('daily_message_limit')
                    ->label('Daily messages')->numeric()->required()->default(1000),
                Forms\Components\TextInput::make('max_devices')
                    ->label('Max devices')->numeric()->required()->default(2),
                Forms\Components\TextInput::make('max_webhooks')
                    ->label('Max webhooks')->numeric()->default(3),
                Forms\Components\TextInput::make('max_templates')
                    ->label('Max templates')->numeric()->default(20),
                Forms\Components\TextInput::make('bulk_batch_limit')
                    ->label('Bulk batch limit')->numeric()->default(100),
            ])->columns(3),

            Forms\Components\Section::make('Pricing (DZD)')->schema([
                Forms\Components\TextInput::make('price_monthly')
                    ->label('Monthly price')->numeric()->prefix('DZD')->required(),
                Forms\Components\TextInput::make('price_yearly')
                    ->label('Yearly price')->numeric()->prefix('DZD')->required(),
            ])->columns(2),

            Forms\Components\Section::make('Features')->schema([
                Forms\Components\CheckboxList::make('features')
                    ->options([
                        'webhooks'        => 'Webhooks',
                        'templates'       => 'Templates',
                        'bulk_send'       => 'Bulk send',
                        'scheduling'      => 'Message scheduling',
                        'priority_support'=> 'Priority support',
                    ])
                    ->columns(3),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('daily_message_limit')
                    ->label('Daily limit')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('max_devices')->label('Devices'),
                Tables\Columns\TextColumn::make('price_monthly')
                    ->label('Monthly')->money('DZD')->sortable(),
                Tables\Columns\TextColumn::make('price_yearly')
                    ->label('Yearly')->money('DZD'),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')->label('Subscribers'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('sort_order')->label('Order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->reorderable('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit'   => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
