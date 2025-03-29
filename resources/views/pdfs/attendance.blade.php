<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Presensi Kelas</title>
    <style>
        /* Reset dan style dasar */
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header styles */
        .header {
            margin-bottom: 24px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 16px;
            text-align: center;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 5px 0;
        }

        .header p {
            font-size: 12px;
            color: #6b7280;
            margin: 0;
        }

        /* Session info styles */
        .session-info {
            margin-bottom: 24px;
        }

        .session-info table {
            width: 100%;
            border-collapse: collapse;
        }

        .session-info td {
            padding: 4px 0;
        }

        .session-info td:first-child {
            width: 150px;
            font-weight: bold;
        }

        /* Stats styles */
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .stats-table td {
            width: 25%;
            padding: 8px;
        }

        .stat-box {
            border-radius: 4px;
            padding: 8px;
            text-align: center;
        }

        .stat-box h4 {
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
            margin: 0;
        }

        .stat-box p {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0 0 0;
        }

        .total-box {
            background-color: #eff6ff;
        }

        .hadir-box {
            background-color: #d1fae5;
        }

        .hadir-box p {
            color: #065f46;
        }

        .izin-box {
            background-color: #fef3c7;
        }

        .izin-box p {
            color: #92400e;
        }

        .absen-box {
            background-color: #fee2e2;
        }

        .absen-box p {
            color: #991b1b;
        }

        /* Attendance table styles */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .attendance-table th {
            background-color: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
        }

        .attendance-table td {
            border-bottom: 1px solid #e5e7eb;
            padding: 8px;
        }

        .attendance-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        /* Status badges */
        .badge {
            display: inline-block;
            border-radius: 9999px;
            padding: 2px 8px;
            font-size: 10px;
            font-weight: 500;
        }

        .badge-hadir {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-tidak-hadir {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-sakit {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-izin {
            background-color: #eff6ff;
            color: #1e40af;
        }

        /* Signature section */
        .signature {
            margin-top: 32px;
            margin-bottom: 24px;
            text-align: right;
        }

        .signature p {
            font-size: 10px;
            color: #6b7280;
            margin: 0 0 5px 0;
        }

        .signature .sign-space {
            margin-bottom: 48px;
        }

        .signature .name {
            font-weight: 500;
        }

        /* Footer */
        .footer {
            margin-top: 24px;
            text-align: right;
        }

        .footer p {
            font-size: 10px;
            color: #9ca3af;
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>LAPORAN PRESENSI SISWA</h1>
            <p>{{ $session->subjectClass->classes->major->name }} - {{ $session->subjectClass->class_name }}</p>
        </div>

        <!-- Session Information -->
        <div class="session-info">
            <table>
                <tr>
                    <td>Mata Pelajaran</td>
                    <td>: {{ $session->subjectClass->class_name }} ({{ $session->subjectClass->class_code }})</td>
                </tr>
                <tr>
                    <td>Pertemuan</td>
                    <td>: {{ $session->subject_title }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ \Carbon\Carbon::parse($session->class_date)->locale('id')->translatedFormat('l, d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td>Waktu</td>
                    <td>: {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }} -
                        {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }} WIB</td>
                </tr>
                <tr>
                    <td>Kelas</td>
                    <td>: {{ $session->subjectClass->classes->name }}</td>
                </tr>
            </table>
        </div>

        <!-- Statistics -->
        <table class="stats-table">
            <tr>
                <td>
                    <div class="stat-box total-box">
                        <h4>Total Siswa</h4>
                        <p>{{ $stats['total'] }}</p>
                    </div>
                </td>
                <td>
                    <div class="stat-box hadir-box">
                        <h4>Hadir</h4>
                        <p>{{ $stats['hadir'] }}</p>
                    </div>
                </td>
                <td>
                    <div class="stat-box izin-box">
                        <h4>Sakit/Izin</h4>
                        <p>{{ $stats['sakit'] + $stats['izin'] }}</p>
                    </div>
                </td>
                <td>
                    <div class="stat-box absen-box">
                        <h4>Tidak Hadir</h4>
                        <p>{{ $stats['tidak_hadir'] }}</p>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Attendance Table -->
        <table class="attendance-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="35%">Nama Siswa</th>
                    <th width="20%">NISN</th>
                    <th width="20%">Status</th>
                    <th width="20%">Waktu Presensi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $index => $attendance)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $attendance->student->user->name }}</td>
                        <td>{{ $attendance->student->nisn }}</td>
                        <td>
                            @switch($attendance->status)
                                @case('hadir')
                                    <span class="badge badge-hadir">Hadir</span>
                                @break

                                @case('tidak_hadir')
                                    <span class="badge badge-tidak-hadir">Tidak Hadir</span>
                                @break

                                @case('sakit')
                                    <span class="badge badge-sakit">Sakit</span>
                                @break

                                @case('izin')
                                    <span class="badge badge-izin">Izin</span>
                                @break
                            @endswitch
                        </td>
                        <td>
                            @if ($attendance->check_in_time)
                                {{ \Carbon\Carbon::parse($attendance->check_in_time)->timezone('Asia/Jakarta')->format('H:i') }}
                                WIB
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="signature">
            <p>..............................., {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('d F Y') }}
            </p>
            <p>Guru Mata Pelajaran</p>
            <p class="sign-space">&nbsp;</p>
            <p class="name">{{ auth()->user()->name }}</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Dicetak pada: {{ $date }}</p>
        </div>
    </div>
</body>

</html>
