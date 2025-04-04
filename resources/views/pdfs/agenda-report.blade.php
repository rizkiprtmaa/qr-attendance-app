<!-- resources/views/reports/agenda-report.blade.php -->
<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
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
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
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
        <hr style="border: 2px solid #000; margin: 0 0 20px 0;">
    </div>

    <div class="title">AGENDA KEGIATAN BELAJAR MENGAJAR</div>

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
                <th width="5%">No</th>
                <th width="30%" style="text-align: center">Hari/Tanggal</th>
                <th width="65%" style="text-align: center">Agenda Pembelajaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $index => $session)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ Carbon\Carbon::parse($session->class_date)->locale('id')->isoFormat('dddd, D MMMM Y') }}</td>
                    <td>{{ $session->subject_title }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center;">Belum ada data pertemuan</td>
                </tr>
            @endforelse


        </tbody>
    </table>

    <div class="footer">
        <p>Guru Mata Pelajaran,</p>
        <div class="signature"></div>
        <p style="text-decoration: underline;">{{ $teacherName }}</p>
    </div>
</body>

</html>
