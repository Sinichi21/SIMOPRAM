<x-mail.layout
    title="Akun SIMPRAM Disetujui"
    preheader="Akun SIMPRAM Anda telah disetujui."
>

    <h2
        style="
            margin-top: 0;
            color: #18181b;
        "
    >
        Akun Anda Telah Disetujui
    </h2>

    <p>
        Halo <strong>{{ $user->name }}</strong>,
    </p>

    <p>
        Registrasi akun SIMPRAM Anda telah berhasil
        diverifikasi dan disetujui oleh administrator.
    </p>

    <p>
        Akun Anda sekarang sudah aktif dan dapat digunakan
        untuk mengakses SIMPRAM.
    </p>

    <x-mail.button :url="$loginUrl">
        Masuk ke SIMPRAM
    </x-mail.button>

    <p>
        Jika Anda mengalami kendala saat masuk,
        silakan menghubungi administrator sekolah.
    </p>

    <p>
        Salam,<br>
        <strong>Tim SIMPRAM</strong>
    </p>

</x-mail.layout>