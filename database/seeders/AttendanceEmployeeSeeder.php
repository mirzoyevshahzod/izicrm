<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // Format: [person_id, first_name, last_name, department]
        $raw = [
            ['0000000001', 'BAHTIYOR', 'ABDAZIMOV', 'EGS'],
            ['0000000002', 'MALOHAT', 'ERNAZAROVA', 'Navoiy'],
            ['0000000003', 'ABDUKABIR', 'QURBANALIYEV', 'EGS'],
            ['0000000004', 'JAMOLIDDIN', 'ABDUKARIMOV', 'Sebzor'],
            ['0000000005', 'DILNOZA', 'ABDUSAMATOVA', 'EGS'],
            ['0000000006', 'ABROR', 'ISMATULLAYEV', 'EGS'],
            ['0000000007', 'ATHAMJON', 'ODILOV', 'EGS'],
            ['0000000008', 'AKRAM', 'AHMADJONOV', 'EGS'],
            ['0000000009', 'AKMAL AKA KAMERA', '', 'EGS'],
            ['0000000010', 'JAHONGIR', 'ERKINOV', 'EGS'],
            ['0000000011', 'MATLUBA', 'XOLIQOVA', 'Sebzor'],
            ['0000000012', 'BAHODIR', 'ARIPOV', 'Sebzor'],
            ['0000000014', 'DAVRONJON', 'NORMAMATOV', 'EGS'],
            ['0000000015', 'DIANA', 'DJUMANOVA', 'EGS'],
            ['0000000016', 'JALOLIDDIN', 'ASOMIDDINOV', 'EGS'],
            ['0000000017', 'ELNORA', 'ABDURAKHMANOVA', 'EGS'],
            ['0000000018', 'FARHOD', 'ANARKULOV', 'EGS'],
            ['0000000019', 'FARRUX', 'QOSIMOV', 'EGS'],
            ['0000000020', 'FAZLIDDIN', 'SALOHIDDINOV', 'EGS'],
            ['0000000021', 'FIRDAVS', 'RAXMATOV', 'EGS'],
            ['0000000022', 'FARHOD', 'HAMROYEV', 'EGS'],
            ['0000000023', 'BAHODIR', 'IKROMOV', 'EGS'],
            ['0000000024', 'ILHOM', 'AKA', 'EGS'],
            ['0000000025', 'JAMSHID', 'MIRZAUMAROV', 'EGS'],
            ['0000000026', 'ANVAR', 'JUMAYEV', 'EGS'],
            ['0000000027', 'DILSHODBEK', 'AHMATOV', '53 xona Operation'],
            ['0000000028', 'TATYANA', 'KIM', 'EGS'],
            ['0000000029', 'LAYLO', 'HABIBULLAYEVA', 'EGS'],
            ['0000000030', 'XABIBULLAXON', 'MALIKOV', 'EGS'],
            ['0000000031', 'JAMSHIDBEK', 'QODIROV', 'EGS'],
            ['0000000032', 'MAHAMADJON', 'YUSUPOV', 'EGS'],
            ['0000000033', "MIRA'ZAM", 'MIRKHUSAINOV', 'Sebzor'],
            ['0000000034', 'USMON', 'NASRIDDINOV', 'EGS'],
            ['0000000035', 'OYBEK', "TO'LAGANOV", 'EGS'],
            ['0000000036', 'JAHONGIR', 'KAMOLOV', 'EGS'],
            ['0000000037', 'RAHIMJON', 'TOSHMATOV', 'EGS'],
            ['0000000038', 'RAVIL', 'SULAYMANOV', 'EGS'],
            ['0000000039', 'SANJARBEK', 'ROZIYEV', 'EGS'],
            ['0000000040', 'SARDOR', 'YANGIBOYEV', 'EGS'],
            ['0000000041', 'SARVARBEK', 'SULTONOV', 'EGS'],
            ['0000000042', 'SHAXZOD', 'MIRZOYEV', 'EGS'],
            ['0000000043', 'SHOHRUX', 'SAFAROV', 'EGS'],
            ['0000000044', 'SUNNATILLA', 'TUXTAYEV', 'EGS'],
            ['0000000045', 'JAMSHID', 'ABDURAZAQOV', '53 xona Operation'],
            ['0000000046', 'KODIR', 'TUXTAYEV', 'EGS'],
            ['0000000047', "ULUG'BEK", 'SAFAROV', 'EGS'],
            ['0000000048', 'ISLOMXON', 'SHUKURXONOV', 'EGS'],
            ['0000000049', 'ALISHER', 'YULDASHEV', 'EGS'],
            ['0000000050', 'ELBEK', 'MADATOV', 'EGS'],
            ['0000000051', 'ZARSHED', 'SHOKIROV', 'EGS'],
            ['0000000052', 'SEVINCH', "O'KTAMOVA", 'EGS'],
            ['0000000053', 'SABINA', 'DANIYAROVA', 'EGS'],
            ['0000000054', 'NURGUL', 'KALTAEVA', 'EGS'],
            ['0000000055', 'FARHODJON', 'SOBIROV', 'EGS'],
            ['0000000056', 'BAXODIR', 'FAYZULLAYEV', 'EGS'],
            ['0000000057', 'AZIZBEK', 'USMONKULOV', 'EGS'],
            ['0000000058', "G'OFURJON", 'SAPAROV', 'EGS'],
            ['0000000059', 'UMIDA', 'RAJABOVA', 'EGS'],
            ['0000000060', 'AZAMAT', 'ABDUMALIKOV', 'EGS'],
            ['0000000061', 'RASULXON', 'SHUKUROV', 'EGS'],
            ['0000000062', 'ALISHER', "JO'RAQULOV", 'EGS'],
            ['0000000063', 'SIROJIDDIN', 'SHAMSHIYEV', 'EGS'],
            ['0000000064', 'UMID', 'RAHIMOV', 'EGS'],
            ['0000000065', 'BEKMUHAMMAD', '', 'EGS'],
            ['0000000066', 'BARNOKHON', 'ORTIQOVA', 'EGS'],
            ['0000000067', 'RUSHANA', 'SHADIYEVA', 'EGS'],
            ['0000000068', 'SEVARA', 'SOLIYEVA', 'EGS'],
            ['0000000069', 'OZODA', "YO'LDASHEVA", 'EGS'],
            ['0000000070', 'XOSIYAT', 'MAMADALIYEVA', 'EGS'],
            ['0000000071', 'JALOLIDDIN', 'JURAYEV', '53 xona Operation'],
            ['0000000072', 'MUHAMMADAMIN', 'TOSHMATOV', '53 xona Operation'],
            ['0000000073', 'NARZULLA', 'TURDIYEV', 'EGS'],
            ['0000000074', 'KOMILJON', 'OBIDJONOV', 'EGS'],
            ['0000000075', 'KHUSNUDDIN', 'SAPARBAEV', 'EGS'],
            ['0000000076', 'ZUBAYR', 'HUSANOV', 'EGS'],
            ['0000000077', 'DIYORA', 'GOFUROVA', 'EGS'],
            ['0000000078', 'ISMOIL', 'MUSAYEV', 'EGS'],
            ['0000000079', 'JAHONGIR', 'JALILOV', 'Sebzor'],
            ['0000000080', 'ABDUKARIM', 'TOSHMUQUMOV', 'EGS'],
            ['0000000081', 'ANASTASIYA', 'TARASOVA', 'EGS'],
            ['0000000082', 'ANVAR', 'YOQUBJONOV', 'EGS'],
            ['0000000083', 'NODIR', 'TUXTAYEV', 'EGS'],
            ['0000000084', 'FERUZ', 'OQBUTAYEV', 'EGS'],
            ['0000000085', 'ZUKHRA', 'ABDURAKHMANOVA', 'EGS'],
            ['0000000086', 'VAZIRA', 'TURDALIYEVA', 'EGS'],
            ['0000000087', 'DIYORA', 'ISAYEVA', 'EGS'],
            ['0000000088', 'RAMZIDDIN', "YO'LDOSHEV", 'EGS'],
            ['0000000089', 'HILOLA', 'SHARIFJONOVA', 'EGS'],
            ['0000000090', 'MANSUR', 'TUXTAYEV', 'EGS'],
            ['0000000091', 'SHOXSANAM', 'DJURAYEVA', 'EGS'],
            ['0000000092', 'JAMSHID', 'JALILOV', 'EGS'],
            ['0000000093', 'AYDANA', 'ABDIBAYEVA', 'EGS'],
            ['0000000094', 'NURMUXAMMAD', 'ABDUSALOMOV', 'EGS'],
            ['0000000095', 'MUHIDDIN', 'UMAROV', 'EGS'],
            ['0000000096', 'AFZAL', 'PULATOV', 'EGS'],
            ['0000000097', 'SHARIFJON', 'AXMEDOV', 'EGS'],
            ['0000000098', 'BAXTIYOR', 'ABDUYUSUPOV', 'EGS'],
            ['0000000099', 'FARRUX', 'ISAKOV', 'EGS'],
            ['0000000100', 'JAVOHIR', 'NURIDDINOV', 'EGS'],
            ['0000000101', 'DONIYOR', '', 'EGS'],
            ['0000000102', 'ZAMIR', 'JADIGEROV', 'Sebzor'],
            ['0000000103', 'OTABEK', 'SOBIROV', 'EGS'],
            ['0000000104', 'BEHRUZ', 'ERGASHEV', 'EGS'],
            ['0000000105', 'DILOBAR', 'HAYDAROVA', 'Navoiy'],
            ['0000000106', 'SHAXBOZ', 'NABIYEV', 'Sebzor'],
            ['0000000107', 'TEMUR', 'KAMOLOV', 'EGS'],
            ['0000000108', 'OYBEK', 'MIRKARIMOV', 'EGS'],
            ['0000000109', 'ELENA', 'KIM', 'EGS'],
            ['0000000110', 'TAMILA', 'KADIROVA', 'EGS'],
            ['0000000111', 'SARBINAZ', 'KOJAMBERGENOVA', 'EGS'],
            ['0000000112', 'ABBOS', 'RIHSIBOYEV', '53 xona Operation'],
            ['0000000113', 'ZAFAR', 'PRIMOV', 'Sebzor'],
            ['0000000114', 'SHAXLO', 'ASHUROVA', 'EGS'],
            ['0000000115', 'UKTAM', 'TOSHBOTIROV', 'Sebzor'],
            ['0000000116', 'FAHRIDDIN', 'XOLIKOV', 'Sebzor'],
            ['0000000117', 'BAHROM', 'SHAVKATOV', 'Sebzor'],
            ['0000000118', 'AZIM', 'ERGASHOV', 'Sebzor'],
            ['0000000119', 'SHAXZOD', 'IGAMBERDIYEV', 'Sebzor'],
            ['0000000120', 'ASLIDDIN', 'FAYZIYEV', 'Navoiy'],
            ['0000000121', 'JAMSHID', 'BERDIBEKOV', 'EGS'],
            ['0000000122', 'JAMOLIDDIN', 'ABDUKARIMOV', 'Sebzor'],
            ['0000000123', 'DURDONA', 'MADRIMOVA', 'EGS'],
            ['0000000124', 'BAXODIRSHOX', 'SAIDOV', 'Sebzor'],
            ['0000000125', 'MUZAFFAR', 'ESHMIRZAYEV', 'EGS'],
            ['0000000126', 'SAIDNOSIR', 'UMARALIYEV', 'EGS'],
            ['0000000127', 'FARXOD', 'XASANOV', 'Navoiy'],
            ['0000000128', 'DILSHOD', 'QOSIMOV', 'Navoiy'],
            ['0000000129', 'MAKHBUBA', 'RAJABOVA', 'Navoiy'],
            ['0000000130', 'ODILA', 'ADILOVA', 'EGS'],
            ['0000000131', 'DILOROM', 'SAYDIARIPOVA', 'EGS'],

            // ⬇️ Yangi ro'yxatda yo'q, lekin eski ro'yxatdan saqlanib qolgan yozuvlar
            ['0000000013', 'NODIR', 'SAFAROV', 'All Departments'],
            ['0000000154', 'SHUXRAT', 'OCHILOV', 'EGS'],
            ['1234567890', 'Jack', 'Dawson', 'All Department'],
        ];

        $now = now();
        $rows = [];

        foreach ($raw as [$personId, $firstName, $lastName, $department]) {
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
        // chat_id va phone ustunlari upsertga kiritilmagan — mavjud xodimlarning
        // ro'yxatdan o'tgan holati (chat_id/phone) saqlanib qoladi
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('attendance_employees')->upsert(
                $chunk,
                ['person_id'],
                ['first_name', 'last_name', 'department', 'updated_at']
            );
        }

        $this->command->info(count($rows) . ' ta xodim saqlandi/yangilandi.');
    }

    private function titleCase(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
}
