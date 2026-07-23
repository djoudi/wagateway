<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityEventResource\Pages;
use App\Models\SecurityEvent;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SecurityEventResource extends Resource
{
    protected static ?string $model          = SecurityEvent::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int    $navigationSort  = 2;

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function form(Form $form): Form { return $form->schema([]); }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('event')
                    ->colors([
                        'danger'  => fn ($s) => str_contains($s, 'failed') || str_contains($s, 'invalid') || str_contains($s, 'suspended') || str_contains($s, 'banned'),
                        'warning' => fn ($s) => str_contains($s, 'regenerated') || str_contains($s, 'rate_limit'),
                        'success' => fn ($s) => str_contains($s, 'success'),
                    ]),
                Tables\Columns\TextColumn::make('user.email')->label('User')->searchable()->default('—'),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->searchable(),
                Tables\Columns\TextColumn::make('context')
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : $state)
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')->label('Time')->dateTime('d M H:i:s')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('event')->options([
                    'login_success'        => 'Login success',
                    'login_failed'         => 'Login failed',
                    'api_key_invalid'      => 'Invalid API key',
                    'api_key_regenerated'  => 'API key regenerated',
                ]),
                Tables\Filters\Filter::make('last_24h')
                    ->query(fn ($q) => $q->where('created_at', '>=', now()->subDay()))
                    ->label('Last 24 hours')
                    ->default(),
            ])
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSecurityEvents::route('/')];
    }
}
