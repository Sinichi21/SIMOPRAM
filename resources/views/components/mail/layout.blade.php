@props([
    'title' => 'SIMPRAM',
    'preheader' => null,
])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>{{ $title }}</title>
</head>

<body
    style="
        margin: 0;
        padding: 0;
        background-color: #f4f4f5;
        font-family: Arial, Helvetica, sans-serif;
        color: #27272a;
    "
>
    @if ($preheader)
        <div
            style="
                display: none;
                max-height: 0;
                overflow: hidden;
                opacity: 0;
            "
        >
            {{ $preheader }}
        </div>
    @endif

    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        border="0"
        style="background-color: #f4f4f5;"
    >
        <tr>
            <td
                align="center"
                style="padding: 32px 16px;"
            >

                <table
                    role="presentation"
                    width="100%"
                    cellspacing="0"
                    cellpadding="0"
                    border="0"
                    style="
                        max-width: 600px;
                        background-color: #ffffff;
                        border-radius: 12px;
                        overflow: hidden;
                    "
                >

                    {{-- Header --}}
                    <tr>
                        <td
                            align="center"
                            style="
                                padding: 28px;
                                background-color: #18181b;
                            "
                        >
                            <div
                                style="
                                    font-size: 24px;
                                    font-weight: bold;
                                    color: #ffffff;
                                "
                            >
                                SIMPRAM
                            </div>

                            <div
                                style="
                                    margin-top: 6px;
                                    font-size: 13px;
                                    color: #d4d4d8;
                                "
                            >
                                Sistem Informasi Manajemen Pramuka
                            </div>
                        </td>
                    </tr>

                    {{-- Content --}}
                    <tr>
                        <td
                            style="
                                padding: 32px;
                                font-size: 15px;
                                line-height: 1.7;
                            "
                        >

                            {{ $slot }}

                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td
                            style="
                                padding: 24px 32px;
                                background-color: #fafafa;
                                border-top: 1px solid #e4e4e7;
                                font-size: 12px;
                                line-height: 1.6;
                                color: #71717a;
                                text-align: center;
                            "
                        >
                            Email ini dikirim otomatis oleh SIMPRAM.

                            <br>

                            Mohon tidak membalas email ini jika tidak diperlukan.

                            <br><br>

                            &copy; {{ now()->year }} SIMPRAM.
                            Seluruh hak cipta dilindungi.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>
</html>