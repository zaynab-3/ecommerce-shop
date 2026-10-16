<?php

namespace Database\Seeders;

use App\Models\DeliveryZone;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        StoreSetting::firstOrCreate(['id' => 1]);
        $areas = ['Beirut' => ['Beirut'], 'Mount Lebanon' => ['Baabda', 'Choueifat', 'Hadath', 'Hazmieh', 'Aley', 'Bhamdoun', 'Broummana', 'Bikfaya', 'Antelias', 'Jal El Dib', 'Jdeideh', 'Dbayeh', 'Jounieh', 'Byblos', 'Zouk Mosbeh'], 'South' => ['Saida', 'Tyre', 'Jezzine', 'Ghazieh', 'Maghdouche'], 'Nabatieh' => ['Nabatieh', 'Bint Jbeil', 'Marjayoun', 'Hasbaya', 'Kfar Roummane'], 'North' => ['Tripoli', 'Batroun', 'Chekka', 'Zgharta', 'Bcharre', 'Ehden', 'Koura'], 'Akkar' => ['Halba', 'Bebnine', 'Qoubaiyat'], 'Bekaa' => ['Zahle', 'Chtaura', 'Bar Elias', 'Anjar', 'Joub Jannine'], 'Baalbek-Hermel' => ['Baalbek', 'Hermel', 'Ras Baalbek']];
        foreach ($areas as $governorate => $towns) {
            foreach ($towns as $name) {
                DeliveryZone::firstOrCreate(['name' => $name], ['governorate' => $governorate]);
            }
        }
    }
}
