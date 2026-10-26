<?php

namespace Database\Seeders;

use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin
        $adminUser = User::create([
            'name' => 'Administrator PKL',
            'email' => 'admin@pkl.ac.id',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Akun Dosen Pembimbing Jurusan Teknologi Informasi dan Komputer (TIK)
        $supervisors = [
            [
                'name' => 'Nunu Nugraha Purnawan, S.Pd., M.Kom.',
                'employee_number' => '197909152015041001',
                'email' => 'nunu.nugraha@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Rian Piarna, S.E., M.M.',
                'employee_number' => '198709032019031009',
                'email' => 'rian.piarna@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Dwi Vernanda, S.T., M.Pd.',
                'employee_number' => '199104302019032018',
                'email' => 'dwi.vernanda@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Mohammad Iqbal, S.Kom., M.T.',
                'employee_number' => '199001262019031025',
                'email' => 'mohammad.iqbal@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Tri Herdiawan Apandi, S.ST., M.T.',
                'employee_number' => '198801052019031008',
                'email' => 'tri.herdiawan@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Slamet Rahayu, S.Pd., M.Pd.',
                'employee_number' => '170900045',
                'email' => 'slamet.rahayu@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Haryati, S.Pd., M.Pd.',
                'employee_number' => '199306142019032021',
                'email' => 'haryati@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer (TRPL)',
            ],
            [
                'name' => 'Nurfitria Khoirunnisa, S.Tr.Kom., M.Kom.',
                'employee_number' => '199603112020122022',
                'email' => 'nurfitria.khoirunnisa@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer (TRPL)',
            ],
            [
                'name' => 'Sari Azhariyah, S.Pd., M.Pd.T.',
                'employee_number' => '199408182022032017',
                'email' => 'sari.azhariyah@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Taufan Abdurrachman, S.T., M.Kom.',
                'employee_number' => '199311112022031006',
                'email' => 'taufan.abdurrachman@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer (TRPL)',
            ],
            [
                'name' => 'Chepy Perdana, M.Pd.',
                'employee_number' => '199109242022031001',
                'email' => 'chepy.perdana@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Lani Nurlani, M.Kom.',
                'employee_number' => '198804032022032002',
                'email' => 'lani.nurlani@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Masesa Angga Wijaya, S.Kom., M.Kom.',
                'employee_number' => '198808132022031004',
                'email' => 'masesa.angga@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Usep Abdul Rosid, S.T., M.Kom.',
                'employee_number' => '198905122022031003',
                'email' => 'usep.abdul@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Erick Febriyanto, S.Kom, M.T.I.',
                'employee_number' => '199002212024061001',
                'email' => 'erick.febriyanto@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer (TRPL)',
            ],
            [
                'name' => 'Abd Rachman Mildan, M.Pd.',
                'employee_number' => '198910182024061001',
                'email' => 'abd.rachman@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Agin Sugiwa, M.Ak.',
                'employee_number' => '198909022024061001',
                'email' => 'agin.sugiwa@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer',
            ],
            [
                'name' => 'Willy Muhammad Fauzi, S.T., M.Kom.',
                'employee_number' => null,
                'email' => 'willy.fauzi@polsub.ac.id',
                'position' => 'Dosen Teknologi Informasi dan Komputer (TRPL)',
            ],
        ];

        foreach ($supervisors as $spv) {
            $user = User::create([
                'name' => $spv['name'],
                'email' => $spv['email'],
                'password' => Hash::make('password'),
                'role' => 'pembimbing',
            ]);

            Supervisor::create([
                'user_id' => $user->id,
                'employee_number' => $spv['employee_number'],
                'position' => $spv['position'],
            ]);
        }
    }
}
