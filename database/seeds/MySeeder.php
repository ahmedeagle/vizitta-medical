<?php

use Illuminate\Database\Seeder;

class MySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // provider types
        DB::table('provider_types')->insert([[
            'name_en' => 'Clinic',
            'name_ar' => 'عيادة'
        ],[
            'name_en' => 'Hospital',
            'name_ar' => 'مستشفى'
        ],[
            'name_en' => 'Dispensary',
            'name_ar' => 'مستوصف'
        ]]);

        // payment methods
        DB::table('payment_methods')->insert([[
            'name_en' => 'Cash',
            'name_ar' => 'نقدى'
        ],[
            'name_en' => 'Mda',
            'name_ar' => 'مدى'
        ],[
            'name_en' => 'Credit / Master Cards',
            'name_ar' => 'بطاقات ائتمانية (فيزا وماستركارد)'
        ]]);

        // cities data
        DB::table('cities')->insert([[
            'name_en' => 'Saudi Arabia',
            'name_ar' => 'المملكه العربية السعودية'
        ],[
            'name_en' => 'Egypt',
            'name_ar' => 'مصر'
        ]]);

        // insurance companies data
        DB::table('insurance_companies')->insert([[
            'name_en' => 'Saudi for insurance',
            'name_ar' => 'السعودية للتأمين'
        ],[
            'name_en' => 'Egypt for insurance',
            'name_ar' => 'مصر للتأمين'
        ]]);

        // agreement data
        DB::table('mix')->insert([
            'agreement_en' => '',
            'agreement_ar' => '',
            'reservation_rules_en' => '',
            'reservation_rules_ar' => ''
        ]);
    }
}
