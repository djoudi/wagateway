<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\SecurityEvent;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class InvoiceResource extends Resource
{
    protected static ?string $model          = Invoice::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';
    protected static string|\UnitEnum|null    $navigationGroup = 'Management';
    protected static ?int    $navigationSort  = 3;

    public static function canCreate(): bool { return false; }
    public static function form(Schema $schema): Schema { return $schema->schema([]); }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Customer')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')->badge(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')->money('DZD')->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')->badge()
                    ->colors([
                        'success' => 'card',
                        'warning' => fn ($s) => in_array($s, ['ccp', 'bank_transfer']),
                        'gray'    => 'coupon',
                    ]),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'paid',
                        'warning' => 'pending',
                        'danger'  => fn ($s) => in_array($s, ['failed', 'expired', 'cancelled']),
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')->dateTime('d M H:i')->sortable(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid at')->dateTime('d M H:i')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending'   => 'Pending',
                    'paid'      => 'Paid',
                    'failed'    => 'Failed',
                    'expired'   => 'Expired',
                    'cancelled' => 'Cancelled',
                ]),
                Tables\Filters\SelectFilter::make('payment_method')->options([
                    'card'          => 'Card (Chargily)',
                    'ccp'           => 'CCP',
                    'bank_transfer' => 'Bank transfer',
                    'coupon'        => 'Coupon',
                ]),
                Tables\Filters\Filter::make('pending_manual')
                    ->label('Pending manual review')
                    ->query(fn ($q) => $q->where('status', 'pending')->whereIn('payment_method', ['ccp', 'bank_transfer']))
                    ->default(),
            ])
            ->actions([
                // Manual confirmation — the ONLY path that activates a plan
                // for CCP/bank_transfer invoices. Card payments are never
                // confirmed here; they only ever activate via the signed
                // Chargily webhook (see ChargilyWebhookController).
                Filament\Actions\Action::make('confirm_payment')
                    ->label('Confirm payment')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status === 'pending'
                        && in_array($record->payment_method, ['ccp', 'bank_transfer']))
                    ->requiresConfirmation()
                    ->modalDescription('This manually activates the plan for this customer. Only confirm after verifying the payment actually landed in the account.')
                    ->action(function (Invoice $record) {
                        DB::transaction(function () use ($record) {
                            $record->markPaid();

                            $periodDays = $record->billing_cycle === 'yearly' ? 365 : 30;
                            $record->user->update([
                                'plan_id'         => $record->plan_id,
                                'plan_expires_at' => now()->addDays($periodDays),
                                'is_suspended'    => false,
                            ]);
                        });

                        SecurityEvent::log('invoice_paid_manual', $record->user_id, [
                            'invoice_number' => $record->invoice_number,
                            'confirmed_by'   => auth()->user()->email,
                        ]);

                        \App\Jobs\GenerateInvoicePdf::dispatch($record);
                        \App\Jobs\SendInvoiceReceiptEmail::dispatch($record);

                        Notification::make()
                            ->title('Payment confirmed — plan activated')
                            ->success()
                            ->send();
                    }),

                Filament\Actions\Action::make('mark_failed')
                    ->label('Mark failed')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Invoice $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(fn (Invoice $record) => $record->update(['status' => 'failed'])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInvoices::route('/')];
    }
}
