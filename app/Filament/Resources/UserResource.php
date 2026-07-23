<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Plan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model         = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Management';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Account')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()->dehydrated(fn ($v) => filled($v))
                    ->required(fn (string $context) => $context === 'create'),
            ])->columns(2),

            Forms\Components\Section::make('Subscription')->schema([
                Forms\Components\Select::make('plan_id')
                    ->label('Plan')
                    ->options(Plan::pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\DateTimePicker::make('plan_expires_at')
                    ->label('Plan expires at')->nullable(),
                Forms\Components\Toggle::make('is_suspended')
                    ->label('Suspended'),
                Forms\Components\TextInput::make('suspension_reason')
                    ->label('Suspension reason')->nullable(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('plan.name')->label('Plan')->badge()
                    ->color(fn ($state) => match($state) {
                        'Starter'  => 'gray',
                        'Pro'      => 'info',
                        'Business' => 'success',
                        default    => 'gray',
                    }),
                Tables\Columns\TextColumn::make('devices_count')->counts('devices')->label('Devices'),
                Tables\Columns\TextColumn::make('messages_count')->counts('messages')->label('Messages'),
                Tables\Columns\IconColumn::make('is_suspended')->boolean()->label('Suspended'),
                Tables\Columns\TextColumn::make('plan_expires_at')->dateTime()->label('Expires')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')->relationship('plan', 'name'),
                Tables\Filters\TernaryFilter::make('is_suspended')->label('Suspended'),
            ])
            ->actions([
                Tables\Actions\Action::make('generate_keys')
                    ->label('Regen API Keys')
                    ->icon('heroicon-o-key')
                    ->action(fn (User $record) => $record->generateApiKeys())
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('suspend')
                    ->label(fn (User $r) => $r->is_suspended ? 'Unsuspend' : 'Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->action(fn (User $r) => $r->update(['is_suspended' => ! $r->is_suspended]))
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('plan');
    }
}
