---
description: Filament 4 ve Laravel 12 Geliştirme Standartları
---

Bu proje **Filament v4** ve **Laravel 12** kullanmaktadır. Tüm geliştirmelerde aşağıdaki teknik kurallar ZORUNLUDUR:

### 1. Namespace ve Import Kuralları
- **Aksiyonlar (Actions):** `EditAction`, `DeleteAction`, `BulkActionGroup`, `DeleteBulkAction`, `ExportBulkAction` gibi tüm aksiyonlar `Filament\Actions` namespace'inden çağrılmalıdır. 
  - *YANLIŞ:* `Filament\Tables\Actions\EditAction`
  - *DOĞRU:* `Filament\Actions\EditAction`
- **Tablo Aksiyonları:** Tablo içinde `->actions()` ve `->bulkActions()` metodları kullanılmalıdır. Eski `recordActions` veya `toolbarActions` tercih edilmemelidir.

### 2. Model ve Cast Yapısı
- Laravel 12 ile gelen yeni `protected function casts(): array` yapısı kullanılmalıdır.
- Şifreleme işlemleri modelin kendi cast yapısına (`hashed`) bırakılmalı, Form sınıflarında manuel `Hash::make` yapılmamalıdır.

### 3. Shield ve Yetkilendirme
- Custom sayfa ve widget'larda Shield traitleri (`HasPageShield`, `HasWidgetShield`) kullanılırken Filament 4 uyumlu metod imzalarına dikkat edilmelidir.
- Policy dosyalarında `deleteAny` metodu her zaman `DeleteAny:Model` iznine bakacak şekilde eklenmelidir.

### 4. Tasarım ve UI
- Tasarımlar modern, premium ve responsive olmalıdır.
- İkon sütunları (IconColumn) her zaman yetki kontrolü (auth()->user()->can('update', $record)) içermelidir. (Not: Global auto-resolver aktifse manuel eklemeye gerek yoktur.)

### 5. Kodlama Standartları
- **Model Kullanımı:** Model sınıfları kod içinde tam yol (\App\Models\Blog::...) yerine her zaman use App\Models\Blog; şeklinde import edilerek (Blog::...) kullanılmalıdır.
- **Heroicon Kullanımı:** İkonlar `\Filament\Support\Icons\Heroicon::...` şeklinde tam yol yerine, dosya başında `use Filament\Support\Icons\Heroicon;` eklenerek `Heroicon::...` şeklinde kullanılmalıdır.
- **Namespace Alias Kullanımı:** `use Filament\Actions as Actions;` yapılıp kod içinde `Actions\CreateAction::...` şeklinde kullanım yasaktır. Her sınıf (`CreateAction`, `EditAction` vb.) ayrı ayrı import edilip doğrudan (`CreateAction::...`) kullanılmalıdır.
- **FQCN Yasağı:** Kod içerisinde (metot gövdeleri dahil) `\App\...`, `\Filament\...`, `\Illuminate\...` gibi tam yol sınıf kullanımları yasaktır. Tüm sınıflar dosya başında `use` ile import edilmelidir.

### 6. Dil ve Çeviri (Localization)
- **Core Çeviriler:** Filament ve Laravel core paketlerinde hali hazırda mevcut olan buton, aksiyon ve modal etiketleri için projeye yeni çeviri eklenmemelidir.
- **Standart Key'ler:** Aşağıdaki standart anahtarlar kullanılmalıdır:
    - *Create:* `__('filament-actions::create.single.modal.actions.create.label')` (Daha açıklayıcı olduğu için - Örn: "Kullanıcı Oluştur" - tercih edilmelidir.)
    - *Edit:* `__('filament-actions::edit.single.label')`
    - *Delete:* `__('filament-actions::delete.single.label')`
    - *View:* `__('filament-actions::view.single.label')`
    - *Bulk Delete:* `__('filament-actions::delete.multiple.label')`
- **Özel Çeviriler:** Sadece modele özgü (label, placeholder, modalHeading gibi) metinler için dil dosyası (`lang/{dil}/{model}.php`) oluşturulup kullanılmalıdır.