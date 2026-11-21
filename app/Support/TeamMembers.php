<?php

namespace App\Support;

/**
 * tim pengembang kolabora yang ditampilkan di halaman publik.
 * tentang memakai nama + foto (inisial bila tanpa foto) dan keterangan tim; kontak memakai nama
 * dan email bila ada. email kontak tim; foto belum ada (tampil inisial) (jangan mengarang).
 */
class TeamMembers
{
    /**
     * @return array<int, array{name: string, team: string, campus: string, email: ?string, photo: ?string}>
     */
    public static function all(): array
    {
        return [
            [
                'name' => 'Kenzie Raffa Ardhana',
                'team' => 'Solo No Problem',
                'campus' => 'Institut Teknologi Bandung',
                'email' => 'kolabora@gmail.com',
                'photo' => null,
            ],
        ];
    }
}
