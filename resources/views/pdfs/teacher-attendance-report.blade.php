<!-- resources/views/pdfs/teacher-attendance-report.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
        }

        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ public_path('fonts/dejavu-sans-webfont.ttf') }}');
            font-weight: normal;
            font-style: normal;
        }

        .container {
            padding: 10px;
        }

        .text-center {
            text-align: center;
        }

        .header-text {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header-address {
            font-size: 10px;
            margin-bottom: 10px;
        }

        .border-bottom {
            border-bottom: 2px solid black;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            padding: 5px;
            text-align: center;
            font-size: 10px;
        }

        th {
            background-color: #f2f2f2;
        }

        .signature {
            margin-top: 30px;
            text-align: right;
            padding-right: 50px;
        }

        .logo-container {
            position: relative;
            text-align: center;
            margin-bottom: 5px;
        }

        .logo-left {
            position: absolute;
            left: 0;
            top: 0;
            width: 60px;
        }

        .logo-right {
            position: absolute;
            right: 0;
            top: 0;
            width: 60px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo-container">
            <div style="text-align: center;">
                <span style="position: absolute; left: 0; top: 10px;"><img src="{{ $logoProvData }}" alt=""
                        width="60px"></span>
                <div>
                    <div class="header-text">PEMERINTAH PROVINSI JAWA BARAT</div>
                    <div class="header-text">DINAS PENDIDIKAN</div>
                    <div class="header-text">SMK NURUSSALAM SALOPA</div>
                    <div class="header-address">Jl. Raya Salopa Desa Kawitan Kecamatan Salopa Kabupaten Tasikmalaya
                        46192</div>
                </div>
                <span style="position: absolute; right: 0; top: 10px;"><img src="{{ $logoSekolahData }}" alt=""
                        width="60px"></span>
            </div>
        </div>

        <div class="border-bottom"></div>

        <div class="header-text text-center">{{ $title }}</div>
        <div class="header-text text-center">BULAN {{ $month }}</div>
        <div class="header-text text-center">TAHUN PELAJARAN {{ $academic_year }}</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">NO</th>
                    <th style="width: 200px;">NAMA</th>
                    <th style="width: 40px;">NUPTK</th>
                    <th style="width: 50px;">JAM PER MINGGU</th>
                    <th style="width: 50px;">JAM PER BULAN</th>
                    <th style="width: 50px;">JUMLAH TIDAK HADIR</th>
                    <th style="width: 50px;">JUMLAH HADIR</th>
                    <th style="width: 50px;">PERSENTASE</th>
                    <th style="width: 40px;">KET</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($teachers as $teacher)
                    <tr>
                        <td>{{ $teacher['no'] }}</td>
                        <td style="text-align: left;">{{ $teacher['name'] }}</td>
                        <td>{{ $teacher['code'] }}</td>
                        <td>{{ $teacher['weekly_jp'] }}</td>
                        <td>{{ $teacher['monthly_jp'] }}</td>
                        <td>{{ $teacher['absent_count'] }}</td>
                        <td>{{ $teacher['present_count'] }}</td>
                        <td>{{ $teacher['percentage'] }}%</td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature">
            <div>
                <div>Tasikmalaya, {{ Carbon\Carbon::now()->locale('id')->format('d F Y') }}</div>
                <br>
                <div>Mengetahui,</div>
                <div>Kepala Sekolah</div>
                <br><br><br><br>
                <div>Dedi Zafar Mutaqin, S.Mn.</div>
                <div>NUPTK. 5758749650130082</div>
            </div>
        </div>
    </div>
</body>

</html>
