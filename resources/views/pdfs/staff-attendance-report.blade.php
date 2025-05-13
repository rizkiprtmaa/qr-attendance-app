<!-- resources/views/pdfs/staff-attendance-report.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
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
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .header-address {
            font-size: 8px;
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
            padding: 2px;
            text-align: center;
            font-size: 8px;
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
            padding-right: 20px;
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

        .status-hadir {
            font-weight: bold;
            font-size: 15px;
            padding: none;
        }

        .status-terlambat {
            color: #FF9800;
            font-weight: bold;
        }

        .status-sakit {
            color: #2196F3;
            font-weight: bold;
        }

        .status-izin {
            color: #9C27B0;
            font-weight: bold;
        }

        .status-alpa {
            color: #F44336;
            font-weight: bold;
        }

        .weekend {
            background-color: #dadada;
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
        <div class="header-text text-center">BULAN {{ $month }} {{ $year }}</div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 20px;">NO</th>
                    <th rowspan="2" style="width: 160px;">NAMA</th>
                    <th colspan="{{ count($days) }}" style="text-align: center;">TANGGAL</th>
                    <th colspan="3" class="summary-col">KEHADIRAN</th>
                    <th rowspan="2" style="width: 20px;">JML</th>
                </tr>
                <tr>
                    @foreach ($days as $day)
                        <th style="width: 20px; {{ $isWeekend[$day] ? 'background-color: #dadada;' : '' }}">
                            {{ $day }}</th>
                    @endforeach

                    <th class="summary-col" style="width: 20px;">S</th>
                    <th class="summary-col" style="width: 20px;">I</th>
                    <th class="summary-col" style="width: 20px;">A</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staffs as $index => $staff)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="text-align: left;">{{ $staff['name'] }}</td>

                        @foreach ($days as $day)
                            <td style="{{ $isWeekend[$day] ? 'background-color: #dadada;' : '' }}">
                                @if (isset($staff['days'][$day]))
                                    @switch($staff['days'][$day])
                                        @case('✓')
                                            <span class="status-hadir">✓</span>
                                        @break

                                        @case('S')
                                            <span class="status-sakit">S</span>
                                        @break

                                        @case('I')
                                            <span class="status-izin">I</span>
                                        @break

                                        @case('A')
                                            <span class="status-alpa">A</span>
                                        @break

                                        @default
                                            {{ $staff['days'][$day] }}
                                    @endswitch
                                @endif
                            </td>
                        @endforeach

                        <td class="summary-col">{{ $staff['summary']['sick'] ?? 0 }}</td>
                        <td class="summary-col">{{ $staff['summary']['permission'] ?? 0 }}</td>
                        <td class="summary-col">{{ $staff['summary']['absent'] ?? 0 }}</td>

                        <td>{{ ($staff['summary']['present'] ?? 0) + ($staff['summary']['late'] ?? 0) }}</td>
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
