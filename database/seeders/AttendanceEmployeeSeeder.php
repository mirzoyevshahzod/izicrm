<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $raw = [
            ['0000000001', 'BAXTIYOR ABDUAZIMOV', 'EGS'],
            ['0000000002', 'UMARBEK TULAGANOV', 'Navoiy'],
            ['0000000003', 'ABDUKABIR QURBANALIYEV', 'EGS'],
            ['0000000004', 'JAMOLIDDIN ABDUKARIMOV', 'EGS'],
            ['0000000005', 'DILNOZA ABDUSAMATOVA', 'EGS'],
            ['0000000006', 'ABROR ISMATULLAYEV', 'EGS'],
            ['0000000007', 'ATXAM ODILOV', 'EGS'],
            ['0000000008', 'AKRAM AHMADJONOV', 'EGS'],
            ['0000000009', 'AKMAL AKA KAMERA', 'EGS'],
            ['0000000010', 'JAXONGIR IRKINOV', 'EGS'],
            ['0000000011', 'TORABEK YOLDOSHEV', 'Sebzor'],
            ['0000000012', 'BAHODIR ARIPOV', 'Sebzor'],
            ['0000000013', 'NODIR SAFAROV', 'All Departments'],
            ['0000000014', 'DAVRONJON NORMAMATOV', 'EGS'],
            ['0000000015', 'DIANA DJUMANOVA', 'EGS'],
            ['0000000016', 'JALOLIDDIN ASOMIDDINOV', 'EGS'],
            ['0000000017', 'ELNORA ABDURAKHMANOVA', 'EGS'],
            ['0000000018', 'FARHOD ANARKULOV', 'EGS'],
            ['0000000019', 'FARRUX QOSIMOV', 'EGS'],
            ['0000000020', 'FAZLIDDIN SALOHIDDINOV', 'EGS'],
            ['0000000021', 'FIRDAVS RAXMATOV', 'EGS'],
            ['0000000022', 'FARHOD HAMROYEV', 'EGS'],
            ['0000000023', 'BAHODIR IKROMOV', 'EGS'],
            ['0000000024', 'ILHOM AKA', 'EGS'],
            ['0000000025', 'JAMSHID MIRZAUMAROV', 'EGS'],
            ['0000000027', 'MUXLIS PARDAYEVA', 'All Departments'],
            ['0000000028', 'TATYANA KIM', 'EGS'],
            ['0000000029', 'LAYLO HABIBULLAYEVA', 'EGS'],
            ['0000000030', 'HABIBULLOH MALIKOV', 'EGS'],
            ['0000000031', 'JAMSHIDBEK QODIROV', 'EGS'],
            ['0000000033', 'MIRAZAM', 'Sebzor'],
            ['0000000034', 'USMON NASRIDDINOV', 'EGS'],
            ['0000000035', 'OYBEK TOLAGANOV', 'EGS'],
            ['0000000036', 'JAXONGIR KAMOLOV', 'EGS'],
            ['0000000038', 'RAVIL SULAYMANOV', 'EGS'],
            ['0000000039', 'SANJARBEK ROZIYEV', 'EGS'],
            ['0000000040', 'SARDOR YANGIBOYEV', 'EGS'],
            ['0000000041', 'SARVAR SULTONOV', 'EGS'],
            ['0000000042', 'SHAXZOD MIRZOYEV', 'EGS'],
            ['0000000043', 'SHOHRUX SAFAROV', 'EGS'],
            ['0000000044', 'SUNNATILLA TUXTAYEV', 'EGS'],
            ['0000000045', 'JAMSHID ABDURAZAQOV', 'EGS'],
            ['0000000046', 'QODIR TUXTAYEV', 'EGS'],
            ['0000000047', 'ULUGBEK SAFAROV', 'EGS'],
            ['0000000048', 'ISLOMXON SHUKURXONOV', 'EGS'],
            ['0000000049', 'ALISHER YULDASHEV', 'EGS'],
            ['0000000050', 'ELBEK MADATOV', 'EGS'],
            ['0000000051', 'ZARSHED SHOKIROV', 'EGS'],
            ['0000000052', 'SEVINCH OKTAMOVA', 'EGS'],
            ['0000000053', 'SABINA DANIYAROVA', 'EGS'],
            ['0000000054', 'NURGUL KALTAEVA', 'EGS'],
            ['0000000055', 'NURGUL', 'EGS'],
            ['0000000056', 'BAXODIR FAYZULLAYEV', 'EGS'],
            ['0000000057', 'AZIZBEK USMONQULOV', 'EGS'],
            ['0000000058', 'GOFURJON SAPAROV', 'EGS'],
            ['0000000059', 'UMIDA RAJABOVA', 'EGS'],
            ['0000000060', 'AZAMAT ABDUMALIKOV', 'EGS'],
            ['0000000061', 'RASULXON SHUKUROV', 'EGS'],
            ['0000000062', 'ALISHER JURAKULOV', 'EGS'],
            ['0000000063', 'SIROJIDDIN SHAMSIYEV', 'EGS'],
            ['0000000064', 'UMID RAHIMOV', 'EGS'],
            ['0000000065', 'FIRDAVS ABDULLAHONOV', 'EGS'],
            ['0000000066', 'BARNOKHON ORTIQOVA', 'EGS'],
            ['0000000067', 'RUSHANA SHADIYEVA', 'EGS'],
            ['0000000068', 'SEVARA SOLIYEVA', 'EGS'],
            ['0000000069', 'OZODA YOLDOSHEVA', 'EGS'],
            ['0000000070', 'XOSIYAT MAMADALIYEVA', 'EGS'],
            ['0000000071', 'JALOLIDDIN JURAYEV', 'EGS'],
            ['0000000072', 'MUHAMMADAMIN TOSHMATOV', 'EGS'],
            ['0000000073', 'NARZULLA TURDIYEV', 'All Departments'],
            ['0000000074', 'KOMILJON OBIDJONOV', 'EGS'],
            ['0000000075', 'XUSNIDDIN SAPARBAYEV', 'EGS'],
            ['0000000076', 'ZUBAYR HUSANOV', 'EGS'],
            ['0000000077', 'DIYORA GOFUROVA', 'EGS'],
            ['0000000078', 'ISMOIL MUSAYEV', 'EGS'],
            ['0000000079', 'JAXONGIR JALILOV', 'Sebzor'],
            ['0000000080', 'ABDUKARIM TOSHMUQUMOV', 'EGS'],
            ['0000000081', 'ANASTASIYA TARASOVA', 'EGS'],
            ['0000000082', 'ANVAR YOQUBJONOV', 'EGS'],
            ['0000000083', 'NODIR TUXTAYEV', 'EGS'],
            ['0000000084', 'FERUZ OQBUTAYEV', 'EGS'],
            ['0000000085', 'ZUXRA ABDURAXMONOVA', 'EGS'],
            ['0000000086', 'VAZIRA TURDALIYEVA', 'EGS'],
            ['0000000087', 'DIYORA ISAYEVA', 'EGS'],
            ['0000000088', 'RAMZIDDIN YOLDOSHEV', 'EGS'],
            ['0000000089', 'HILOLA SHARIFJONOVA', 'EGS'],
            ['0000000090', 'MANSUR TUXTAYEV', 'EGS'],
            ['0000000091', 'SHOXSANAM DJURAYEVA', 'EGS'],
            ['0000000092', 'JAMSHID JALILOV', 'EGS'],
            ['0000000093', 'AYDANA ABDIBAYEVA', 'EGS'],
            ['0000000094', 'NURMUXAMMAD ABDUSALOMOV', 'EGS'],
            ['0000000095', 'MUHIDDIN UMAROV', 'EGS'],
            ['0000000096', 'AFZALJON POLATJONOV', 'EGS'],
            ['0000000097', 'SHARIFJON AXMEDOV', 'EGS'],
            ['0000000098', 'BAXTIYOR ABDUYUSUPOV', 'EGS'],
            ['0000000099', 'FARRUX ISAKOV', 'EGS'],
            ['0000000100', 'JAVOHIR NURIDDINOV', 'EGS'],
            ['0000000101', 'SARDOR QARSHIBAYEV', 'All Departments'],
            ['0000000102', 'ZAMIR JADIGEROV', 'Sebzor'],
            ['0000000103', 'OTABEK SOBIROV', 'Sebzor'],
            ['0000000104', 'BEHRUZ ERGASHEV', 'EGS'],
            ['0000000106', 'SHAXBOZ NABIYEV', 'Sebzor'],
            ['0000000107', 'TEMUR KAMOLOV', 'EGS'],
            ['0000000108', 'OYBEK MIRKARIMOV', 'EGS'],
            ['0000000109', 'YELENA KIM', 'EGS'],
            ['0000000110', 'TAMILA KADIROVA', 'EGS'],
            ['0000000111', 'SARBINAZ KOJAMBERGENOVA', 'EGS'],
            ['0000000112', 'ABBOS RIHSIBOYEV', 'All Departments'],
            ['0000000113', 'JANNA BECHYUS', 'EGS'],
            ['0000000114', 'SHAXLO ASHUROVA', 'EGS'],
            ['0000000115', 'UKTAM TOSHBOTIROV', 'Sebzor'],
            ['0000000117', 'BAHROM SHAVKATOV', 'Sebzor'],
            ['0000000118', 'AZIM ERGASHOV', 'Sebzor'],
            ['0000000119', 'SHAXZOD IGAMBERDIYEV', 'Sebzor'],
            ['0000000120', 'BEKZOD ABDUSATTAROV', 'All Departments'],
            ['0000000121', 'JAMSHID BERDIBEKOV', 'EGS'],
            ['0000000123', 'DURDONA MADRIMOVA', 'EGS'],
            ['0000000124', 'SABRIYABONU ABDUQAHHOROVA', 'Sebzor'],
            ['0000000125', 'MUZAFFAR ESHMIRZAYEV', 'EGS'],
            ['0000000126', 'SAIDNOSIR UMARALIYEV', 'EGS'],
            ['0000000127', 'SHOXRUZ ZAYNIDDINOV', 'Sebzor'],
            ['0000000130', 'ODILA ADILOVA', 'EGS'],
            ['0000000154', 'SHUXRAT OCHILOV', 'EGS'],
            ['1234567890', 'Jack Dawson', 'All Department'],
        ];

        $now = now();
        $rows = [];

        foreach ($raw as [$personId, $fullName, $department]) {
            $parts = preg_split('/\s+/', trim($fullName));

            $firstName = $parts[0] ?? $fullName;
            $lastName  = count($parts) > 1
                ? implode(' ', array_slice($parts, 1))
                : '';

            $rows[] = [
                'person_id'  => $personId,
                'first_name' => $this->titleCase($firstName),
                'last_name'  => $this->titleCase($lastName),
                'department' => $department,
                'chat_id'    => null,
                'phone'      => null,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // person_id unique bo'lgani uchun mavjud bo'lsa yangilanadi, bo'lmasa qo'shiladi
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('attendance_employees')->upsert(
                $chunk,
                ['person_id'],
                ['first_name', 'last_name', 'department', 'updated_at']
            );
        }

        $this->command->info(count($rows) . ' ta xodim saqlandi.');
    }

    private function titleCase(string $value): string
    {
        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
