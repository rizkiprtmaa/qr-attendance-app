<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Kehadiran Kelas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
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

        .info-section {
            margin-bottom: 15px;
        }

        .info-section table {
            border: none;
        }

        .info-section td {
            border: none;
            padding: 2px 5px;
            text-align: left;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid black;
            padding: 3px;
            text-align: center;
            font-size: 10px;
        }

        th {
            background-color: #f2f2f2;
        }

        .summary-col {
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
                        46192
                    </div>
                </div>
                <span style="position: absolute; right: 0; top: 10px;"><img src="{{ $logoSekolahData }}" alt=""
                        width="60px"></span>
            </div>
        </div>

        <div class="border-bottom"></div>

        <div class="header-text text-center">LAPORAN KEHADIRAN KELAS</div>

        <div class="info-section">
            <table style="width: 50%;">
                <tr>
                    <td style="width: 100px;">KELAS</td>
                    <td>: {{ $class->name ?? '-' }} - {{ $major }}</td>
                </tr>
                <tr>
                    <td>WALI KELAS</td>
                    <td>: {{ $teacher }}</td>
                </tr>
                <tr>
                    <td>BULAN</td>
                    <td>: {{ $month }} {{ $year }}</td>
                </tr>
            </table>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 30px;">No</th>
                    <th rowspan="2" style="width: 180px;">Nama Siswa</th>
                    <th colspan="{{ is_countable($days) ? count($days) : 0 }}" style="text-align: center;">TANGGAL</th>
                    <th colspan="3" class="summary-col">KEHADIRAN</th>
                    <th rowspan="2" style="width: 40px;">JML</th>
                </tr>
                <tr>
                    @foreach ($days ?? [] as $day)
                        <th
                            style="width: 20px; {{ isset($isWeekend[$day]) && $isWeekend[$day] ? 'background-color: #dadada;' : '' }}">
                            {{ $day }}</th>
                    @endforeach
                    <th class="summary-col" style="width: 30px;">S</th>
                    <th class="summary-col" style="width: 30px;">I</th>
                    <th class="summary-col" style="width: 30px;">A</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($students ?? [] as $index => $student)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="text-align: left;">{{ $student['name'] }}</td>

                        @foreach ($days ?? [] as $day)
                            <td
                                style="{{ isset($isWeekend[$day]) && $isWeekend[$day] ? 'background-color: #dadada;' : '' }}">
                                {{ $student['days'][$day] ?? '' }}</td>
                        @endforeach

                        <td class="summary-col">{{ $student['summary']['sick'] ?? 0 }}</td>
                        <td class="summary-col">{{ $student['summary']['permission'] ?? 0 }}</td>
                        <td class="summary-col">{{ $student['summary']['absent'] ?? 0 }}</td>

                        <td>{{ ($student['summary']['present'] ?? 0) + ($student['summary']['late'] ?? 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature">
            <div>
                <div>Wali Kelas,</div>
                <br><br><br><br>
                <div>{{ $teacher }}</div>
            </div>
        </div>
    </div>
</body>

</html>
