ALGORITMA Membuat_Jus_Avokad

DEKLARASI
    blender, pisau, sendok, gelas
    avokat, susu, gula, es
    rasa: string

DESKRIPSI
MULAI

    SIAPKAN avokad, gula, susu, air, es_batu
    SIAPKAN blender, pisau, sendok, gelas

    BELAH avokad
    BUANG biji_avokad
    AMBIL daging_avokad

    MASUKKAN daging_avokad KE blender
    TAMBAHKAN gula
    TAMBAHKAN susu
    TAMBAHKAN air
    TAMBAHKAN es_batu

    NYALAKAN blender
    BLENDER bahan

    LAKUKAN
        BLENDER bahan
    JIKA jus_belum_halus

    MATIKAN blender
    TUANG jus KE gelas

    TAMPILKAN "Jus avokad siap disajikan"

SELESAI