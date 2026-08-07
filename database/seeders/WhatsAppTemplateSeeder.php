<?php

namespace Database\Seeders;

use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;

class WhatsAppTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Sapaan Awal',
                'category' => 'cold',
                'message' => 'Halo {nama}, terima kasih sudah order di Utapes! 😊 Admin *{handler}* disini. Mau dibantu cek order atau info produknya?',
            ],
            [
                'name' => 'Follow-Up Stok',
                'category' => 'cold',
                'message' => 'Halo {nama}, order kamu {order_id} sudah kami proses. Untuk size {size}, stok masih tersedia. Ada yang bisa kami bantu?',
            ],
            [
                'name' => 'Nunggu Gajian',
                'category' => 'hot',
                'message' => 'Halo {nama}, gimana kabarnya? Order {order_id} masih kami tahan untuk kamu. Kalau sudah siap transfer, kabarin admin *{handler}* ya 😊',
            ],
            [
                'name' => 'Konfirmasi Closing',
                'category' => 'hot',
                'message' => 'Halo {nama}! Terima kasih sudah bayar untuk order {order_id} 🎉 Sepatu akan segera kami kirim. Mohon tunggu update resi ya!',
            ],
            [
                'name' => 'Reaktivasi (Cold)',
                'category' => 'cold',
                'message' => 'Halo {nama}, ini admin *{handler}* dari Utapes 👋 Beberapa waktu lalu kamu sempat chat soal sepatu. Sekarang ada promo menarik, mau lihat-lihat lagi?',
            ],
        ];

        foreach ($templates as $template) {
            WhatsAppTemplate::firstOrCreate(
                ['name' => $template['name']],
                [
                    'category' => $template['category'],
                    'message' => $template['message'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('✅ WhatsApp templates seeded (' . count($templates) . ' templates)');
    }
}
