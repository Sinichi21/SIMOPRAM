<x-layouts::app :title="__('Akun Siswa')">

    <div class="p-6">

        <livewire:student-accounts.manage
            :student-id="$studentId"
        />

    </div>

</x-layouts::app>