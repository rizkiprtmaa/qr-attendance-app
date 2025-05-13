<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Laporan Kehadiran Guru dan Tenaga Kependidikan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #000;
        }

        th,
        td {
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h2,
        .header h3 {
            margin: 5px 0;
        }

        .tepat-waktu {
            color: green;
        }

        .terlambat,
        .tidak-hadir {
            color: red;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>LAPORAN KEHADIRAN GURU DAN TENAGA KEPENDIDIKAN BERDASARKAN ABSEN DIGITAL</h2>
        <h3>PADA HARI {{ strtoupper($date) }}</h3>
        <h3>SMK PK NURUSSALAM</h3>
        <h3>TAHUN PELAJARAN {{ date('Y') }}/{{ date('Y') + 1 }}</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 30%;">NAMA</th>
                <th style="width: 25%;">JAM PELAKSANAAN KBM</th>
                <th style="width: 15%;">JAM DATANG</th>
                <th style="width: 25%;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($teachers as $teacher)
                <tr>
                    <td>{{ $teacher['no'] }}</td>
                    <td class="text-left">{{ $teacher['name'] }}</td>
                    <td>{{ $teacher['schedule_time'] }}</td>
                    <td>{{ $teacher['attendance_time'] }}</td>
                    <td
                        class="{{ strtolower($teacher['status']) === 'hadir' ? 'tepat-waktu' : (strtolower($teacher['status']) === 'terlambat' ? 'terlambat' : 'tidak-hadir') }}">
                        {{ strtolower($teacher['status']) === 'hadir' ? 'TEPAT WAKTU' : strtoupper($teacher['status']) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
