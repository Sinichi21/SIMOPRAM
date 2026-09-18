<x-mail.layout title="Akses peserta kegiatan">
    <h1>Pendaftaran kegiatan berhasil</h1>
    <p>Halo {{ $name }}, Anda terdaftar pada {{ $activity }}.</p>
    <p>Akses berlaku {{ $startsAt }} sampai {{ $endsAt }} ({{ config('app.timezone') }}).</p>
    <x-mail.button :url="$url">Buka akses kegiatan</x-mail.button>
    <p>Tautan ini khusus untuk Anda. Jangan bagikan kepada orang lain.</p>
</x-mail.layout>
