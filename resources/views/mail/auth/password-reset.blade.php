<x-mail.layout
    title="Reset Password SIMPRAM"
    preheader="Permintaan reset password akun SIMPRAM."
>

    <h2 style="margin-top: 0;">
        Reset Password
    </h2>

    <p>
        Halo <strong>{{ $user->name }}</strong>,
    </p>

    <p>
        Kami menerima permintaan untuk mengatur ulang
        password akun SIMPRAM Anda.
    </p>

    <x-mail.button :url="$resetUrl">
        Reset Password
    </x-mail.button>

    <p>
        Demi keamanan akun, link reset password hanya
        berlaku dalam periode tertentu.
    </p>

    <p>
        Jika Anda tidak meminta reset password,
        abaikan email ini. Password akun Anda tidak akan berubah.
    </p>

    <p>
        Salam,<br>
        <strong>Tim SIMPRAM</strong>
    </p>

</x-mail.layout>