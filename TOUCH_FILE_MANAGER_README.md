# Touch File Manager

Touch File Manager, Laravel 12 ve Filament 4 kullanılarak oluşturulmuş profesyonel bir dosya yönetim sistemidir.

## Özellikler

### 📁 Klasör Yönetimi
- Klasör oluşturma ve silme
- Hiyerarşik klasör yapısı (parent-child ilişkisi)
- Klasör silme işleminde tüm alt klasörler ve dosyalar otomatik olarak silinir

### 📄 Dosya Yönetimi
- Çoklu dosya yükleme
- Desteklenen dosya tipleri:
  - 🖼️ **Görseller** (image/*)
  - 🎬 **Videolar** (video/*)
  - 📝 **Dökümanlar** (PDF, Word, Text)
  - 📊 **Tablolar** (Excel)
  - 📊 **Sunumlar** (PowerPoint)
  - 📦 **Arşivler** (ZIP, RAR, 7Z)
- Filament'in yerleşik görsel editörü ile görselleri düzenleme
- Dosya önizleme
- Dosya indirme
- Dosya silme

### 🎨 Kullanıcı Arayüzü
- Windows dosya gezgini benzeri arayüz
- Dosya tipine göre renkli badge'ler
- Dosya tipine göre ikonlar
- Görsel dosyalar için thumbnail önizleme
- Dosya boyutu gösterimi (human-readable format)
- Gelişmiş filtreleme seçenekleri

### 🔍 Arama ve Filtreleme
- Global arama desteği
- Dosya tipine göre filtreleme
- Klasör/dosya tipine göre filtreleme
- Parent klasöre göre filtreleme

## Dosya Yapısı

```
app/
├── Models/
│   └── TouchFile.php                    # Model dosyası
├── Filament/
    └── Resources/
        └── TouchFileManager/
            ├── TouchFileManagerResource.php    # Ana resource dosyası
            ├── Pages/
            │   ├── ListTouchFiles.php         # Liste sayfası
            │   ├── CreateTouchFile.php        # Oluşturma sayfası
            │   └── EditTouchFile.php          # Düzenleme sayfası
            ├── Schemas/
            │   └── TouchFileForm.php          # Form şeması
            └── Tables/
                └── TouchFileManagerTable.php   # Tablo şeması
```

## Veritabanı Yapısı

### touch_files tablosu

| Sütun | Tip | Açıklama |
|-------|-----|----------|
| id | bigint | Primary key |
| name | string | Dosya/klasör adı |
| path | string | Dosya yolu (storage'da) |
| type | string | Dosya tipi (image, video, document, vb.) |
| mime_type | string | MIME tipi |
| size | bigint | Dosya boyutu (bytes) |
| parent_id | bigint | Parent klasör ID'si |
| is_folder | boolean | Klasör mü dosya mı? |
| metadata | json | Ek metadata |
| created_at | timestamp | Oluşturulma zamanı |
| updated_at | timestamp | Güncellenme zamanı |

## Kullanım

### Klasör Oluşturma
1. Touch File Manager sayfasına gidin
2. "New Folder" butonuna tıklayın
3. Klasör adını girin
4. İsteğe bağlı olarak parent klasör seçin
5. Kaydedin

### Dosya Yükleme
1. Touch File Manager sayfasına gidin
2. "Upload Files" butonuna tıklayın
3. İsteğe bağlı olarak parent klasör seçin
4. Dosyaları seçin veya sürükle-bırak yapın
5. Görseller için image editor ile düzenleyebilirsiniz
6. Kaydedin

### Dosya/Klasör Silme
- Tek dosya/klasör silme: Satırdaki çöp kutusu ikonuna tıklayın
- Toplu silme: Checkbox'ları seçin ve "Delete selected" butonuna tıklayın
- ⚠️ Klasör silindiğinde içindeki tüm dosyalar ve alt klasörler de silinir

## Özellikler

### Model Özellikleri

#### İlişkiler
- `parent()` - Parent klasörü getirir
- `children()` - Tüm alt öğeleri getirir
- `folders()` - Sadece alt klasörleri getirir
- `files()` - Sadece dosyaları getirir

#### Accessor'lar
- `full_path` - Tam klasör yolunu getirir
- `url` - Dosya URL'ini getirir (sadece dosyalar için)
- `human_size` - İnsan okunabilir dosya boyutu
- `icon` - Dosya tipine göre ikon

#### Otomatik İşlemler
- Dosya silindiğinde storage'dan da otomatik olarak silinir
- Klasör silindiğinde tüm alt öğeler recursive olarak silinir

### Güvenlik
- Dosya yükleme sırasında dosya adları slug'lanır
- Maksimum dosya boyutu: 100MB
- Sadece belirli dosya tipleri kabul edilir

## Kurulum

Migration zaten çalıştırılmıştır. Herhangi bir ek kurulum gerekmemektedir.

## Notlar

- Dosyalar `storage/app/public/attachments/` dizininde saklanır
- Klasör hiyerarşisi dosya sisteminde de korunur
- Filament'in FileUpload bileşeni kullanıldığı için görsel düzenleme özellikleri mevcuttur
