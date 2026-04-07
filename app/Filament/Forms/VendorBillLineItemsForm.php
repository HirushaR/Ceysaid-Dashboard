<?php

namespace App\Filament\Forms;

use Filament\Forms;

final class VendorBillLineItemsForm
{
    /** Repeater bound to VendorBill.lineItems (Filament resource / relation managers). */
    public static function lineItemsRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('lineItems')
            ->relationship()
            ->schema([
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quantity')->numeric()->default(1)->required(),
                Forms\Components\TextInput::make('rate')
                    ->label('Rate (LKR)')
                    ->numeric()
                    ->prefix('LKR')
                    ->required(),
            ])
            ->defaultItems(1)
            ->minItems(1)
            ->reorderable()
            ->orderColumn('sort_order')
            ->columns(2)
            ->columnSpanFull();
    }

    /** Standalone repeater for modals (e.g. invoice view) before the vendor bill exists. */
    public static function lineItemsRepeaterEmbedded(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('line_items')
            ->schema([
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quantity')->numeric()->default(1)->required(),
                Forms\Components\TextInput::make('rate')
                    ->label('Rate (LKR)')
                    ->numeric()
                    ->prefix('LKR')
                    ->required(),
            ])
            ->defaultItems(1)
            ->minItems(1)
            ->reorderable()
            ->columns(2)
            ->columnSpanFull();
    }
}
