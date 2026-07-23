<?php
namespace App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource;
class ListUsers extends \Filament\Resources\Pages\ListRecords {
    protected static string $resource = UserResource::class;
}
