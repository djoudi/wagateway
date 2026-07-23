<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MessageResource extends Resource
{
    protected static ?string $model          = Message::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Monitoring';
    protected static ?int    $navigationSort  = 1;
    protected static bool    $canCreate      = false;

    public static function canCreate(): bool  { return false; }
    public static function canEdit($record): bool { return false; }

    public static function form(Form $form): Form { return $form->schema([]); }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('ID')->limit(12)->copyable()->copyMessage('Copied!'),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('device.name')
                    ->label('Device')->searchable(),
                Tables\Columns\TextColumn::make('to_number')
                    ->label('To')->searchable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors(['primary' => 'text', 'success' => 'image', 'warning' => 'document']),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => fn ($s) => in_array($s, ['sent','delivered','read']),
                        'danger'  => 'failed',
                        'warning' => 'queued',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent at')->dateTime('d M H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['queued' => 'Queued','sent' => 'Sent','delivered' => 'Delivered','read' => 'Read','failed' => 'Failed']),
                Tables\Filters\SelectFilter::make('type')
                    ->options(['text' => 'Text','image' => 'Image','document' => 'Document','audio' => 'Audio']),
                Tables\Filters\Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->label('Today only'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_content')
                    ->label('Content')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (Message $r) => view('filament.message-content', ['message' => $r]))
                    ->modalHeading('Message content')
                    ->modalSubmitAction(false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessages::route('/'),
        ];
    }
}
