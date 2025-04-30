<!-- resources/views/reports/attendance-report.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            height: 80px;
        }

        .header h1 {
            font-size: 16pt;
            margin: 5px 0;
            text-transform: uppercase;
            font-weight: bold;
        }

        .header p {
            margin: 2px 0;
            font-size: 11pt;
        }

        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }

        .info {
            margin-bottom: 20px;
        }

        .info td {
            padding: 4px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9pt;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .table th {
            background-color: #f2f2f2;
        }

        .footer {
            text-align: right;
            margin-top: 30px;
        }

        .signature {
            margin-top: 80px;
        }

        .page-break {
            page-break-after: always;
        }

        .hadir {
            background-color: #E8F5E9;
        }

        .tidak_hadir {
            background-color: #FFEBEE;
        }

        .sakit {
            background-color: #FFF8E1;
        }

        .izin {
            background-color: #E3F2FD;
        }
    </style>
</head>

<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td style="width: 15%; text-align: center;">
                    <img src="{{ $logoProvData }}" height="80px">
                </td>
                <td style="width: 70%; text-align: center;">
                    <h1>PEMERINTAH PROVINSI JAWA BARAT</h1>
                    <h1>DINAS PENDIDIKAN</h1>
                    <h1>SMK NURUSSALAM SALOPA</h1>
                    <p>Jl. Raya Salopa Desa Kawitan Kecamatan Salopa Kabupaten Tasikmalaya 46192</p>
                </td>
                <td style="width: 15%; text-align: center;">
                    <img src="{{ $logoSekolahData }}" height="80px">
                </td>
            </tr>
        </table>
        <hr style="border: 1px solid #000; margin: 3px 0;">
        <hr style="border: 2px solid #000; margin: 2px 0 20px 0;">
    </div>

    <div class="title">LAPORAN KEHADIRAN MATA PELAJARAN</div>

    <table class="info">
        <tr>
            <td style="width: 150px;">KELAS</td>
            <td>: {{ $className }}</td>
        </tr>
        <tr>
            <td>MATA PELAJARAN</td>
            <td>: {{ $subjectName }}</td>
        </tr>
        <tr>
            <td>SEMESTER</td>
            <td>: {{ $semester }}</td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th rowspan="2" width="3%">No</th>
                <th rowspan="2" width="20%">Nama Siswa</th>
                <th colspan="{{ count($sessions) }}">TANGGAL</th>
                <th colspan="3" width="9%">KEHADIRAN</th>
                <th rowspan="2" width="3%">JML</th>
            </tr>
            <tr>
                @foreach ($sessions as $session)
                    <th width="{{ 68 / count($sessions) }}%">
                        {{ Carbon\Carbon::parse($session->class_date)->format('d/m') }}</th>
                @endforeach
                <th width="3%">S</th>
                <th width="3%">I</th>
                <th width="3%">A</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="text-align: left;">{{ $student->user->name }}</td>

                    @foreach ($sessions as $session)
                        <td class="{{ $attendanceData[$student->id][$session->id] ?? 'tidak_hadir' }}">
                            @switch($attendanceData[$student->id][$session->id] ?? 'tidak_hadir')
                                @case('hadir')
                                    H
                                @break

                                @case('sakit')
                                    S
                                @break

                                @case('izin')
                                    I
                                @break

                                @default
                                    A
                            @endswitch
                        </td>
                    @endforeach

                    <td>{{ $attendanceSummary[$student->id]['sakit'] }}</td>
                    <td>{{ $attendanceSummary[$student->id]['izin'] }}</td>
                    <td>{{ $attendanceSummary[$student->id]['tidak_hadir'] }}</td>
                    <td>{{ $attendanceSummary[$student->id]['hadir'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Guru Mata Pelajaran,</p>
        <div class="signature"></div>
        <p style="text-decoration: underline;">{{ $teacherName }}</p>
    </div>
</body>

</html>
