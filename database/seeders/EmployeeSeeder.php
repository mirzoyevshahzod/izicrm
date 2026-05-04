<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('employees')->insert([

            // EGS
            ['company'=>'EGS','full_name'=>'ABDAZIMOV BAXTIYOR GAYRATOVICH','work_phone'=>'+998933892777','personal_phone'=>'+998971399700'],
            ['company'=>'EGS','full_name'=>'ABDIBAYEVA AYDANA GABIT QIZI','work_phone'=>'+998933999225','personal_phone'=>'+998508842204'],
            ['company'=>'EGS','full_name'=>'ABDULLAXONOV FIRDAVS SHUXRAT OGLI','work_phone'=>'+998933891838','personal_phone'=>'+998900187070'],
            ['company'=>'EGS','full_name'=>'ABDUSAMATOVA DILNOZA MAVLANOVNA','work_phone'=>'+998933891898','personal_phone'=>'+998978820503'],
            ['company'=>'EGS','full_name'=>'ANARKULOV FARHOD NARZULLAYEVICH','work_phone'=>'+998946602585','personal_phone'=>'+998998115525'],
            ['company'=>'EGS','full_name'=>'ASILBEKOV AMIRBEK ULUGBEK OGLI','work_phone'=>'+998933891913','personal_phone'=>'+998996940072'],
            ['company'=>'EGS','full_name'=>'AXMEDOV SHARIFJON XASAN OGLI','work_phone'=>'+998946609798','personal_phone'=>'+998900159565'],
            ['company'=>'EGS','full_name'=>'BECHYUS JANNA RAYBENOVNA','work_phone'=>'+998946607727','personal_phone'=>'+998974644542'],
            ['company'=>'EGS','full_name'=>'BOGOMAZOVA ANGELINA VLADIMIROVNA','work_phone'=>'+998946608525','personal_phone'=>'+998934502460'],
            ['company'=>'EGS','full_name'=>'DANABOYEV SHAHZOD DILSHOD OGLI','work_phone'=>'+998946603424','personal_phone'=>'+998775551999'],
            ['company'=>'EGS','full_name'=>'DANIYAROVA SABINA YUNUSOVNA','work_phone'=>'+998946602600','personal_phone'=>'+998915250635'],
            ['company'=>'EGS','full_name'=>'DJURAYEVA SHOXSANAM MANSURJON QIZI','work_phone'=>'+998934033663','personal_phone'=>'+998946602066'],
            ['company'=>'EGS','full_name'=>'FAYZULLAYEV BAXODIR BAXTIYOR OGLI','work_phone'=>'+998946605590','personal_phone'=>'+998903516774'],
            ['company'=>'EGS','full_name'=>'FASXIYEVA KAMILA SALAXITDINOVNA','work_phone'=>'+998946602550','personal_phone'=>'+998909885845'],
            ['company'=>'EGS','full_name'=>'GOFUROVA MAFTUNA ABDULAZIZ QIZI','work_phone'=>'+998946605191','personal_phone'=>'+998911925007'],
            ['company'=>'EGS','full_name'=>'HABIBULLAYEVA LAYLO HABIBULLO QIZI','work_phone'=>'+998946608727','personal_phone'=>'+998991444517'],
            ['company'=>'EGS','full_name'=>'HUSANOV ZUBAYR BOBIR OGLI','work_phone'=>'+998930074370','personal_phone'=>'+998770030444'],
            ['company'=>'EGS','full_name'=>'ISMATULLAYEV ABROR HABIBULLA OGLI','work_phone'=>'+998933999056','personal_phone'=>'+998998284517'],
            ['company'=>'EGS','full_name'=>'ISMOILOVA DIYORA ILXOM QIZI','work_phone'=>'+998947110363','personal_phone'=>'+998908124828'],
            ['company'=>'EGS','full_name'=>'ISROILOVA MUNIRA MUSABEK QIZI','work_phone'=>'+998947441413','personal_phone'=>'+998990996609'],
            ['company'=>'EGS','full_name'=>'JALILOV JAHONGIR TOHIR OGLI','work_phone'=>'+998946602191','personal_phone'=>'+998975787747'],
            ['company'=>'EGS','full_name'=>'JALILOV JAMSHID TOHIR OGLI','work_phone'=>'+998946601514','personal_phone'=>'+998970858747'],
            ['company'=>'EGS','full_name'=>'JORAQULOV ALISHER AKBAR OGLI','work_phone'=>'+998946608101','personal_phone'=>'+998997555628'],
            ['company'=>'EGS','full_name'=>'KAMOLOV TEMUR ANVAR OGLI','work_phone'=>'+998946606202','personal_phone'=>'+998949446744'],
            ['company'=>'EGS','full_name'=>'KARSHIBAYEVA AZIZA ABDULLAYEVNA','work_phone'=>'+998946609323','personal_phone'=>'+998977518534'],
            ['company'=>'EGS','full_name'=>'KIM ELENA GEORGIEVNA','work_phone'=>'+998946602696','personal_phone'=>'+998881885046'],
            ['company'=>'EGS','full_name'=>'KIM TATYANA GENNADIYEVNA','work_phone'=>'+998946600636','personal_phone'=>'+998900115029'],
            ['company'=>'EGS','full_name'=>'KOCHKAROV BAKHRIDIN','work_phone'=>'+998946605560','personal_phone'=>'+998931051263'],
            ['company'=>'EGS','full_name'=>'MAMADALIYEVA XOSIYAT KENJAYEVNA','work_phone'=>'+998946604421','personal_phone'=>'+998958841000'],
            ['company'=>'EGS','full_name'=>'NABIYEV SHOHBOZ FAXRIDDIN OGLI','work_phone'=>'+998946604463','personal_phone'=>'+998971959493'],
            ['company'=>'EGS','full_name'=>'NORMAMATOV DAVRONJON BAHODIR OGLI','work_phone'=>'+998946602484','personal_phone'=>'+998913364257'],
            ['company'=>'EGS','full_name'=>'ODILOV ATHAMJON ROVSHAN OGLI','work_phone'=>'+998946608860','personal_phone'=>'+998977346622'],
            ['company'=>'EGS','full_name'=>'OQBUTAYEV FERUZ NASIRIDDINOVICH','work_phone'=>'+998946600580','personal_phone'=>'+998934327553'],
            ['company'=>'EGS','full_name'=>'RAJABOVA UMIDA AKROM QIZI','work_phone'=>'+998946602808','personal_phone'=>'+998994819781'],
            ['company'=>'EGS','full_name'=>'RAVSHANOV ABDUFATTOX MIRAZIZ OGLI','work_phone'=>'+998946600616','personal_phone'=>'+998330085025'],
            ['company'=>'EGS','full_name'=>'SAFAROV SHOHRUX AGZAMOVICH','work_phone'=>'+998946602424','personal_phone'=>null],
            ['company'=>'EGS','full_name'=>'SALOHIDDINOV FAZLIDDIN SALOHIDDIN OGLI','work_phone'=>'+998946609765','personal_phone'=>'+998900420128'],
            ['company'=>'EGS','full_name'=>'SAPARBAYEV XUSNUDDIN NURIDDIN OGLI','work_phone'=>'+998946605825','personal_phone'=>'+998946651801'],
            ['company'=>'EGS','full_name'=>'SAPAROV GOFURJON UKTAMOVICH','work_phone'=>'+998946608040','personal_phone'=>'+998909293182'],
            ['company'=>'EGS','full_name'=>'SHADIYEVA RUSHANA SHAVKATOVNA','work_phone'=>'+998946600935','personal_phone'=>'+998909092550'],
            ['company'=>'EGS','full_name'=>'SHAMSHIYEV SIROJIDDIN SADRIDDINOVICH','work_phone'=>'+998933811100','personal_phone'=>null],
            ['company'=>'EGS','full_name'=>'SHARIFJONOVA HILOLAXON BAHODIR QIZI','work_phone'=>'+998933891858','personal_phone'=>'+998930050640'],
            ['company'=>'EGS','full_name'=>'SHOKIROV ZARSHED ZAFAROVICH','work_phone'=>'+998946609676','personal_phone'=>'+998917015222'],
            ['company'=>'EGS','full_name'=>'SHUKUROV RASULXON NASRULLAXON OGLI','work_phone'=>'+998935009499','personal_phone'=>null],
            ['company'=>'EGS','full_name'=>'SHUKURXONOV ISLOMXON NEMATULLAXON OGLI','work_phone'=>'+998946601898','personal_phone'=>'+998958821696'],
            ['company'=>'EGS','full_name'=>'SOBIROV SUNNATULLO QOBIL OGLI','work_phone'=>'+998933990226','personal_phone'=>'+998946275638'],
            ['company'=>'EGS','full_name'=>'SOLIYEVA SEVARA SANATJON QIZI','work_phone'=>'+998946606626','personal_phone'=>'+998909714466'],
            ['company'=>'EGS','full_name'=>'TEMIROV ELMUROD ABDUGAFFUR OGLI','work_phone'=>'+998501776786','personal_phone'=>'+998945718333'],
            ['company'=>'EGS','full_name'=>'TOLAGANOV OYBEK SHOKIRJON OGLI','work_phone'=>'+998933999015','personal_phone'=>null],
            ['company'=>'EGS','full_name'=>'TOLIBEKOV DIYORBEK DAVRONBEK OGLI','work_phone'=>'+998501772038','personal_phone'=>'+998949903947'],
            ['company'=>'EGS','full_name'=>'TUXTAYEV MANSUR MURADJANOVICH','work_phone'=>'+998946600677','personal_phone'=>'+998936005599'],
            ['company'=>'EGS','full_name'=>'UMAROV MUXIDDIN MANSUR OGLI','work_phone'=>'+998946608880','personal_phone'=>'+998998161247'],
            ['company'=>'EGS','full_name'=>'UTASHEVA ZARINA RINATOVNA','work_phone'=>'+998930078407','personal_phone'=>'+998909708596'],
            ['company'=>'EGS','full_name'=>'VAXOBOV ABDUFORUH ABDUFOTIH OGLI','work_phone'=>'+998946606608','personal_phone'=>'+998909707006'],
            ['company'=>'EGS','full_name'=>'YOLDASHEV RAMZIDDIN ROVSHAN OGLI','work_phone'=>'+998946601686','personal_phone'=>'+998909284202'],
            ['company'=>'EGS','full_name'=>'YOLDASHEVA OZODA RAVSHAN QIZI','work_phone'=>'+998946602858','personal_phone'=>'+998976218822'],
            ['company'=>'EGS','full_name'=>'YUSUPOV MAXAMADJON ABDUMOMINOVICH','work_phone'=>null,'personal_phone'=>'+998977726711'],

            // EASTLINE EXPRESS
            ['company'=>'EASTLINE EXPRESS','full_name'=>'ABDURAKHMANOVA ZUKHRA XABIBULLAEVNA','work_phone'=>'+998930074170','personal_phone'=>'+998946252048'],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'ABDURAXMANOVA ELNORA RAXMATJON QIZI','work_phone'=>'+998946602112','personal_phone'=>'+998900274201'],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'ASHUROVA SHAXLO ABDURAXMONOVNA','work_phone'=>'+998933999093','personal_phone'=>'+998911910799'],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'ISAYEVA DIYORA XAMID QIZI','work_phone'=>'+998933990313','personal_phone'=>'+998909167727'],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'KAMOLOVA SABRINA ISOMIDDIN QIZI','work_phone'=>'+998933891848','personal_phone'=>'+998930378257'],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'MIRZAUMAROV JAMSHIDBEK DILSHOD OGLI','work_phone'=>'+998946606566','personal_phone'=>'+998977474942'],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'RUSTAMOV AKMAL ISMOILOVICH','work_phone'=>'+998933991639','personal_phone'=>'+998946160904'],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'SAFAROV ULUGBEK AGZAMOVICH','work_phone'=>'+998946602505','personal_phone'=>null],
            ['company'=>'EASTLINE EXPRESS','full_name'=>'TURDALIYEVA VAZIRA XASANJON QIZI','work_phone'=>'+998933999059','personal_phone'=>'+998959665602'],

            // INCOTRUCK
            ['company'=>'INCOTRUCK','full_name'=>'ABDUKARIMOV JAMOLIDDIN NURIDDIN UGLI','work_phone'=>'+998507607037','personal_phone'=>'+998992740077'],
            ['company'=>'INCOTRUCK','full_name'=>'ABDUMALIKOV AZAMAT ABDULAZIZ OGLI','work_phone'=>'+998946603600','personal_phone'=>null],
            ['company'=>'INCOTRUCK','full_name'=>'ABDUSALOMOV NURMUXAMMAD AVAZBEK OGLI','work_phone'=>'+998503016550','personal_phone'=>'+998973469333'],
            ['company'=>'INCOTRUCK','full_name'=>'ASOMIDDINOV JALOLIDDIN KAMALIDDIN OGLI','work_phone'=>'+998933892243','personal_phone'=>'+998943930353'],
            ['company'=>'INCOTRUCK','full_name'=>'MIRKARIMOV OYBEK MIRXADIYEVICH','work_phone'=>'+998946607494','personal_phone'=>'+998977759629'],
            ['company'=>'INCOTRUCK','full_name'=>'MUSAYEV ISMOIL DILSHOD OGLI','work_phone'=>'+998933891467','personal_phone'=>'+998933781307'],
            ['company'=>'INCOTRUCK','full_name'=>'NASRIDDINOV JAHONGIR DONIYOR OGLI','work_phone'=>'+998933893389','personal_phone'=>'+998954336359'],
            ['company'=>'INCOTRUCK','full_name'=>'QOSIMOV FARRUX ASQAR OGLI','work_phone'=>'+998500514151','personal_phone'=>'+998998803300'],
            ['company'=>'INCOTRUCK','full_name'=>'RISTIBAYEV NURISLOM ABDULLAYEVICH','work_phone'=>'+998933810019','personal_phone'=>'+998909197010'],
            ['company'=>'INCOTRUCK','full_name'=>'SAIDKARIMOV SAIDAKROM IBROXIM OGLI','work_phone'=>'+998946600890','personal_phone'=>'+998909406660'],
            ['company'=>'INCOTRUCK','full_name'=>'SULEYMANOV RAVIL SHARIFULLAYEVICH','work_phone'=>null,'personal_phone'=>'+998901743888'],
            ['company'=>'INCOTRUCK','full_name'=>'TOSHMUQUMOV ABDUKARIM FARHOD OGLI','work_phone'=>'+998933890039','personal_phone'=>'+998949122255'],
            ['company'=>'INCOTRUCK','full_name'=>'TUXTAYEV NODIR KOBILOVICH','work_phone'=>'+998935904045','personal_phone'=>null],

            // TRANSCEKA
            ['company'=>'TRANSCEKA','full_name'=>'ABDUYUSUPOV BAXTIYOR SALIMKULOVICH','work_phone'=>'+998946606362','personal_phone'=>'+998998440079'],
            ['company'=>'TRANSCEKA','full_name'=>'ALIMATOVA ZILOLA FAYZULLO QIZI','work_phone'=>'+998930074145','personal_phone'=>'+998998047003'],
            ['company'=>'TRANSCEKA','full_name'=>'ASILXANOVA RUQIYABONU DILMUROD QIZI','work_phone'=>'+998930072990','personal_phone'=>'+998910991111'],
            ['company'=>'TRANSCEKA','full_name'=>'DJUMANOVA DIANA RAVSHANOVNA','work_phone'=>'+998930072646','personal_phone'=>'+998901152244'],
            ['company'=>'TRANSCEKA','full_name'=>'ISAKOV FARRUX FAXRIDIN OGLI','work_phone'=>'+998933990353','personal_phone'=>'+998770101595'],
            ['company'=>'TRANSCEKA','full_name'=>'KOJAMBERGENOVA SARBINAZ AYAPBERGENOVNA','work_phone'=>'+998930075227','personal_phone'=>'+998903498791'],
            ['company'=>'TRANSCEKA','full_name'=>'MADRIMOVA DURDONA POLATOVNA','work_phone'=>'+998930072983','personal_phone'=>'+998772603676'],
            ['company'=>'TRANSCEKA','full_name'=>'ORTIQOVA BARNOXON ALISHER QIZI','work_phone'=>'+998933890218','personal_phone'=>'+998981151500'],
            ['company'=>'TRANSCEKA','full_name'=>'TARASOVA ANASTASIYA ANATOLEVNA','work_phone'=>'+998935902777','personal_phone'=>'+998997281751'],
            ['company'=>'TRANSCEKA','full_name'=>'YOLDASHEVA ROBIYAXON JAXONGIR QIZI','work_phone'=>'+998931113515','personal_phone'=>'+998337075070'],

            // IZISOL
            ['company'=>'IZISOL','full_name'=>'ROZIYEV SANJARBEK','work_phone'=>'+998946607678','personal_phone'=>'+998886299909'],
            ['company'=>'IZISOL','full_name'=>'SHAHZOD MIRZAYEV','work_phone'=>'+998947441415','personal_phone'=>null],

        ]);
    }
}
