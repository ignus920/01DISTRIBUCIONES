<?php

namespace Database\Seeders\Customers;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customers\TypeIdentification;
class TypeIdentificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
      $data = [
            [
                'name' => 'Cédula de Ciudadanía',
                'acronym' => 'CC',
                'api_data_id' => '01',
                'status' => 1,
            ],
            [
                'name' => 'NIT',
                'acronym' => 'NIT',
                'api_data_id' => '02',
                'status' => 1,
            ],
            [
                'name' => 'Registro civil',
                'acronym' => 'RC',
                'api_data_id' => '03',
                'status' => 1,
            ],
            [
                'name' => 'Tarjeta de identidad',
                'acronym' => 'TI',
                'api_data_id' => '04',
                'status' => 1,
            ],
            [
                'name' => 'Tarjeta de extranjería',
                'acronym' => 'TE',
                'api_data_id' => '05',
                'status' => 1,
            ],
            [
                'name' => 'Cédula de extranjería',
                'acronym' => 'CE',
                'api_data_id' => '06',
                'status' => 1,
            ],
            [
                'name' => 'Pasaporte',
                'acronym' => 'PP',
                'api_data_id' => '07',
                'status' => 1,
            ],
            [
                'name' => 'Documento de identificación extranjero',
                'acronym' => 'DIE',
                'api_data_id' => '08',
                'status' => 1,
            ],
            [
                'name' => 'Rut',
                'acronym' => 'RUT',
                'api_data_id' => '09',
                'status' => 1,
            ],

        ];

        foreach ($data as $item) {
            TypeIdentification::firstOrCreate(
                ['acronym' => $item['acronym']], // Busca por acrónimo para evitar duplicados
                $item // Crea o actualiza con el resto de los datos
            );
        }
    }
}
