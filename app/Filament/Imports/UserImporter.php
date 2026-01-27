<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Hash;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('ID')
                ->rules(['nullable', 'integer'])
                ->ignoreBlankState(),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('email')
                // requiredMapping'i kaldırdım çünkü bazen sadece ID ile güncelleme yapılıyor.
                ->rules(['nullable', 'email', 'max:255'])
                ->ignoreBlankState(),
            ImportColumn::make('password')
                ->rules(['nullable', 'min:8'])
                ->ignoreBlankState()
                ->castStateUsing(function ($state) {
                    return $state ? Hash::make($state) : null;
                }),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Ham veriyi al
        $id = $this->data['id'] ?? null;
        $email = $this->data['email'] ?? null;

        // Normalizasyon (Boşlukları temizle, e-postayı küçült)
        $email = (!blank($email)) ? strtolower(trim((string) $email)) : null;
        $id = (!blank($id) && is_numeric($id)) ? (int) $id : null;

        // Eğer hem ID hem Email yoksa, bu satırı işlemeye çalışma (hata almamak için)
        if (blank($id) && blank($email)) {
            return null;
        }

        $existingRecord = null;

        // 1. ÖNCE ID İLE ARA: ID varsa en öncelikli eşleşmedir (E-posta değişmiş olsa bile)
        if ($id) {
            $existingRecord = User::find($id);
        }

        // 2. ID İLE BULUNAMADIYSA EMAIL İLE ARA:
        if (!$existingRecord && $email) {
            $existingRecord = User::where('email', $email)->first();
        }

        // --- GÜNCELLEME DURUMU ---
        if ($existingRecord) {
            // Çakışma Kontrolü: Eğer dosyada bir Email gelmişse VE bu email veritabanında başka birine aitse bu satırı atla
            if ($email && $existingRecord->email !== $email) {
                $conflict = User::where('email', $email)->where('id', '!=', $existingRecord->id)->exists();
                if ($conflict) {
                    return null;
                }
            }
            return $existingRecord;
        }

        // --- YENİ KAYIT DURUMU ---
        // Yeni bir kayıt oluşturulacaksa e-posta mutlaka olmalıdır (veritabanı kısıtlaması)
        if (blank($email)) {
            return null; // Email yoksa yeni kayıt oluşturamaz, hata yerine atla
        }

        $user = new User();

        // Yeni kayıt için varsayılan şifre
        if (empty($this->data['password'])) {
            $user->password = Hash::make('12345678');
        }

        return $user;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Aktarım tamamlandı. Başarılı: ' . Number::format($import->successful_rows);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' | Başarısız: ' . Number::format($failedRowsCount);
        }

        return $body;
    }
}
