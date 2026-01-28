# YouTube to Blog

YouTube videolarını Chrome extension aracılığıyla blog yazısına dönüştüren Laravel/Filament paketi.

## Kurulum

### 1. Local Path (Geliştirme için)

`composer.json` dosyanıza repository ekleyin:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "./packages/aytackayin/youtube-to-blog"
        }
    ]
}
```

Ve paketi yükleyin:

```bash
composer require aytackayin/youtube-to-blog:@dev
```

### 2. GitHub'dan (Yayınlandıktan sonra)

```bash
composer require aytackayin/youtube-to-blog
```

### 3. Migration'ları Çalıştırın

```bash
php artisan migrate
```

### 4. (Opsiyonel) Config Dosyasını Publish Edin

```bash
php artisan vendor:publish --tag=youtube-to-blog-config
```

### 5. User Modeline Trait Ekleyin

`App\Models\User` modeline trait'i ekleyin:

```php
use Aytackayin\YoutubeToBlog\Traits\HasYouTubeApiToken;

class User extends Authenticatable
{
    use HasYouTubeApiToken;
    
    // fillable array'e eklemenize gerek yok - trait otomatik ekler
    protected $fillable = [
        'name',
        'email',
        'password',
        // 'chrome_token' - trait tarafından otomatik eklenir
    ];
}
```

### 6. Profil Sayfasına API Key Yönetimi Ekleyin

Filament profil sayfanıza API key yönetim sekmesini ekleyin:

```php
use Aytackayin\YoutubeToBlog\Filament\Components\YouTubeApiKeyComponent;

// Tab olarak eklemek için:
Tab::make('Extension')
    ->label('Chrome Eklentisi')
    ->icon('heroicon-o-puzzle-piece')
    ->visible(fn() => auth()->user()->canAccessYouTubeExtension())
    ->schema(YouTubeApiKeyComponent::getSchema()),

// Veya hazır Tab component'ini kullanın:
YouTubeApiKeyComponent::getTab(),
```

## Gereksinimler

Paketin çalışması için projenizde aşağıdakiler bulunmalıdır:

- **Laravel 11+** veya **Laravel 12**
- **Filament 4.x**
- `Blog` modeli (`App\Models\Blog` veya config'de belirtilen)
- `BlogCategory` modeli (`App\Models\BlogCategory` veya config'de belirtilen)
- `TouchFile` modeli (opsiyonel, dosya yönetimi için)
- **Filament Shield** (permission kontrolü için - opsiyonel)

### Gerekli Model Özellikleri

#### Blog Modeli
- `user_id`, `language_id`, `title`, `slug`, `content`, `is_published`, `attachments`, `tags` kolonları
- `categories()` ilişkisi (opsiyonel)
- `generateUniqueSlug()` static metodu (opsiyonel)
- `getStorageFolder()` static metodu (opsiyonel)

#### BlogCategory Modeli
- `user_id`, `language_id`, `title`, `slug`, `parent_id`, `is_published`, `sort` kolonları
- `generateUniqueSlug()` static metodu (opsiyonel)

## Özellikler

- ✅ Chrome extension ile YouTube video bilgilerini API üzerinden alır
- ✅ API key tabanlı kimlik doğrulama (users tablosuna otomatik migration)
- ✅ Blog ve kategori oluşturma API'leri
- ✅ yt-dlp ile video indirme desteği (opsiyonel)
- ✅ YouTube thumbnail otomatik indirme
- ✅ Filament Shield permission kontrolü (`AccessChromeExtension`)
- ✅ Profil sayfası için API key yönetim componenti
- ✅ Konfigüre edilebilir model class'ları

## Chrome Extension Kurulumu

1. Extension dosyalarını publish edin:
   ```bash
   php artisan vendor:publish --tag=youtube-to-blog-extension
   ```
   
2. `extensions/youtube-to-blog` klasörünü Chrome'a yükleyin:
   - Chrome'da `chrome://extensions` adresine gidin
   - "Developer mode" açın
   - "Load unpacked" tıklayın ve klasörü seçin

3. Profilinizden API key oluşturun

4. Extension ayarlarında:
   - Site URL: `https://your-site.com`
   - API Key: Profilinizden kopyaladığınız key

## Konfigürasyon

`config/youtube-to-blog.php`:

```php
return [
    // YouTube video indirme için yt-dlp.exe yolu
    'yt_dlp_path' => env('YT_DLP_PATH', base_path('extensions/youtube-to-blog/yt-dlp.exe')),
    
    // Video indirme aktif mi?
    'video_download_enabled' => env('YT_VIDEO_DOWNLOAD', true),
    
    // Varsayılan dil ID
    'default_language_id' => env('YT_DEFAULT_LANGUAGE_ID', 1),
    
    // Kullanılacak disk
    'disk' => 'attachments',
    
    // Model class'ları (projenize göre değiştirin)
    'blog_model' => \App\Models\Blog::class,
    'blog_category_model' => \App\Models\BlogCategory::class,
    'touch_file_model' => \App\Models\TouchFile::class, // null yapmak dosya sync'i devre dışı bırakır
    
    // Shield permission adı
    'permission_name' => 'AccessChromeExtension',
    
    // Users tablosundaki API token kolon adı
    'api_token_column' => 'chrome_token',
];
```

## API Endpoints

| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/api/youtube/categories` | Kategori listesi (tree yapısında) |
| POST | `/api/youtube/categories` | Yeni kategori oluştur |
| POST | `/api/youtube/store` | Blog yazısı oluştur |

### Request Headers

Tüm API isteklerinde:
```
X-API-KEY: {your-api-token}
```

### POST /api/youtube/store Body

```json
{
    "title": "Video Başlığı",
    "video_id": "dQw4w9WgXcQ",
    "description": "Video açıklaması...",
    "category_ids": [1, 2],
    "note": "Kişisel notlar",
    "add_to_attachments": true
}
```

## Permission Yönetimi

Filament Shield kullanıyorsanız, `AccessChromeExtension` permission'ını oluşturun ve ilgili rollere atayın:

```php
// DatabaseSeeder veya Shield generator
Spatie\Permission\Models\Permission::create(['name' => 'AccessChromeExtension']);
```

## Lisans

MIT License
