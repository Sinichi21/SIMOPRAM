@props([
    'url',
])

<table
    role="presentation"
    cellspacing="0"
    cellpadding="0"
    border="0"
    style="margin: 24px auto;"
>
    <tr>
        <td
            align="center"
            style="
                border-radius: 8px;
                background-color: #18181b;
            "
        >
            <a
                href="{{ $url }}"
                style="
                    display: inline-block;
                    padding: 12px 24px;
                    color: #ffffff;
                    text-decoration: none;
                    font-size: 14px;
                    font-weight: bold;
                "
            >
                {{ $slot }}
            </a>
        </td>
    </tr>
</table>