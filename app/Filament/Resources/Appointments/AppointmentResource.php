<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Filament\Resources\Appointments\Pages\AttendanceReports;
use App\Models\Appointment;
use App\Models\Professional;
use BackedEnum;
use Filament\Navigation\NavigationItem;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $modelLabel = 'Consulta';
    protected static ?string $pluralModelLabel = 'Terapias Realizadas';
    protected static ?string $navigationLabel = 'Terapias Realizadas';
    protected static string|UnitEnum|null $navigationGroup = 'Frequência';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user?->is_admin) {
            return true;
        }

        $profissional = Professional::withoutGlobalScopes()
                            ->where('email', $user->email)
                            ->first();

        // A CHAVE DE OURO: Adicionamos o ->value depois de role
        if ($profissional && in_array($profissional->role->value, ['coordinator', 'supervisor'])) {
            return false; 
        }
        
        return true;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // Restaurando o getPages para como era antes
    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'edit' => EditAppointment::route('/{record}/edit'),
            'reports' => AttendanceReports::route('/reports'),
        ];
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->url(self::getUrl('index'))
                ->icon(static::$navigationIcon)
                ->group(self::$navigationGroup)
                ->isActiveWhen(fn (): bool => request()->routeIs(self::getRouteBaseName() . '.index')),

            NavigationItem::make('Relatórios de Atendimento')
                ->url(self::getUrl('reports'))
                ->icon('heroicon-o-chart-pie')
                ->group(self::$navigationGroup)
                ->isActiveWhen(fn (): bool => request()->routeIs(self::getRouteBaseName() . '.reports')),
        ];
    }
}