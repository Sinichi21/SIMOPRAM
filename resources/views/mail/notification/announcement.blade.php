<x-mail.layout :title="$title" :preheader="$schoolName">
    <h1>{{ $title }}</h1>
    <p>{{ $schoolName }}</p>
    <p style="white-space: pre-wrap">{{ $body }}</p>
    <x-mail.button :url="$url">Buka pengumuman saya</x-mail.button>
</x-mail.layout>
