<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SecurityEventResource\Pages;
use App\Models\SecurityEvent;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityEventResource extends Resource
{
    protected static ?string $model          = SecurityEvent::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static string|\UnitEnum|null    $navigationGroup = 'Monitoring';
    protected static ?int    $navigationSort  = 2;

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function form(Schema $schema): Schema { return $schema->schema([]); }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->colors([
                        'danger'  => fn ($state) => str_contains((string) $state, 'failed') || str_contains((string) $state, 'invalid') || str_contains((string) $state, 'suspended') || str_contains((string) $state, 'banned'),
                        'warning' => fn ($state) => str_contains((string) $state, 'regenerated') || str_contains((string) $state, 'rate_limit'),
                        'success' => fn ($state) => str_contains((string) $state, 'success'),
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
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->subDay()))
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
