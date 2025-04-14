<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Kartu ID</title>
    <style>
        /* Reset dasar */
        * {
            margin: 0;
            padding: 0;
        }

        /* Gaya dasar untuk body */
        body {
            width: 220pt;
            /* 85mm */
            height: 153pt;
            /* 54mm */
            font-family: sans-serif;
            font-size: 9pt;
        }

        /* Gaya sederhana */
        .container {
            width: 100%;
            height: 100%;
        }

        .header {
            text-align: center;
            padding: 5pt 0;
            border-bottom: 1pt solid #ddd;
        }

        .school-name {
            font-weight: bold;
            color: #0046b8;
            font-size: 10pt;
        }

        .logos {
            display: inline-block;
            width: 100%;
            text-align: center;
        }

        .logo {
            height: 14pt;
            vertical-align: middle;
        }

        .left-logo {
            margin-right: 10pt;
        }

        .right-logo {
            margin-left: 10pt;
        }

        .content {
            padding: 5pt;
        }

        .name {
            font-weight: bold;
            font-size: 10pt;
            margin-top: 3pt;
        }

        .user-id {
            margin-top: 2pt;
            color: #444;
        }

        .role {
            margin-top: 2pt;
            display: inline-block;
            background-color: #e0e0e0;
            padding: 1pt 3pt;
            font-size: 7pt;
            border-radius: 2pt;
        }

        .qr-space {
            text-align: center;
            margin-top: 10pt;
        }

        .qr-code {
            width: 70pt;
            height: 70pt;
        }

        .footer {
            text-align: center;
            font-size: 6pt;
            margin-top: 5pt;
            color: #0046b8;
        }

        .year {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logos">
                <img src="{{ $logoProvData }}" class="logo left-logo">
                <span class="school-name">SMK NURUSSALAM SALOPA</span>
                <img src="{{ $logoSekolahData }}" class="logo right-logo">
            </div>
        </div>

        <div class="content">
            <div class="name">{{ $name }}</div>
            <div class="user-id">ID: {{ $identifier }}</div>
            <div class="role">{{ $role }}</div>

            <div class="qr-space">
                <img src="{{ $qrCodePath }}" class="qr-code">
            </div>

            <div class="footer">
                <div>Scan QR Code untuk presensi</div>
                <div class="year">TA {{ $academicYear }}</div>
            </div>
        </div>
    </div>
</body>

</html>
