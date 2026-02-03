<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\User;
use App\Models\Therapy;
use App\Models\Agreement;
use App\Models\ServiceType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'teste admin',
            'email' => 'a@a',
            'password' => '123',
            'is_admin' => true,
        ]);

        $terapias = [
            ['name' => 'ABA'],
            ['name' => 'FONOAUDIOLOGIA'],
            ['name' => 'DENVER'],
            ['name' => 'TERAPIA OCUPACIONAL'],
            ['name' => 'PSICOMOTRICIDADE'],
            ['name' => 'PSICOTERAPIA'],
            ['name' => 'FISIOTERAPIA'],
            ['name' => 'PSICOPEDAGOGIA'],
            ['name' => 'TERAPIA ALIMENTAR'],
            ['name' => 'EDUCAÇÃO FÍSICA'],
            ['name' => 'AVALIAÇÃO'],
            ['name' => 'ANAMNESE'],
        ];

        foreach ($terapias as $terapia) {
            Therapy::create($terapia);
        }

        $convenios = [
            ['name' => 'Humana'],
            ['name' => 'Unimed'],
            ['name' => 'Sulamérica'],
            ['name' => 'Central Nacional'],
            ['name' => 'Particular'],
        ];

        foreach ($convenios as $convenio) {
            Agreement::create($convenio);
        }

        $tipos_terapias = [
            ['name' => 'Clínica'],
            ['name' => 'Escolar'],
            ['name' => 'Domiciliar'],
        ];

        foreach ($tipos_terapias as $tipo_terapia) {
            ServiceType::create($tipo_terapia);
        }

        Unit::factory()->create([
            'name' => 'LIMEIRA E CARVALHO',
            'street' => 'Av. João da Escóssia',
            'neighborhood' => 'Bela Vista',
            'number' => '3715',
            'cnpj' => '44.413.090/0001-18',
            'city' => 'Mossoró',
        ]);
        
        Unit::factory()->create([
            'name' => 'LEAL E CARVALHO INTERVENÇÃO COMPORTAMENTAL',
            'street' => 'Av. Prudente de Morais',
            'neighborhood' => 'Lagoa Nova',
            'number' => '5121',
            'cnpj' => '28.955.274/0001-53',
            'city' => 'Natal',
        ]);

        Unit::factory()->create([
            'name' => 'CL INTERVENÇÃO COMPORTAMENTAL',
            'street' => 'Rua Jerônimo Câmara',
            'neighborhood' => 'Bela Vista',
            'number' => '771',
            'cnpj' => '60.001.032/0001.33',
            'city' => 'João Câmara',
        ]);

        Unit::factory()->create([
            'name' => 'Unidade de Santa Cruz',
            'street' => 'Rua José Ferreira de Medeiros',
            'neighborhood' => 'Bela Vista',
            'number' => '53',
            'cnpj' => '44.444.44/0001.44',
            'city' => 'Santa Cruz',
        ]);
    }
}