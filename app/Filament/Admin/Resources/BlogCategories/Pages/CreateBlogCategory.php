<?php

namespace App\Filament\Admin\Resources\BlogCategories\Pages;

use App\Filament\Admin\Resources\BlogCategories\BlogCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CreateBlogCategory extends CreateRecord
{
    protected static string $resource = BlogCategoryResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        if ($record->attachments) {
            $newAttachments = [];
            $disk = Storage::disk('public');
            $finalDir = "attachments/blog_categories/{$record->id}/images";

            // Ensure Final Directory Exists
            if (!$disk->exists($finalDir)) {
                $disk->makeDirectory($finalDir);
            }
            if (!$disk->exists("{$finalDir}/thumbs")) {
                $disk->makeDirectory("{$finalDir}/thumbs");
            }

            $manager = null;
            if (class_exists(ImageManager::class)) {
                $manager = new ImageManager(new Driver());
            }

            foreach ($record->attachments as $attachment) {
                // Check if it's in the temp directory
                if (str_contains($attachment, 'attachments/blog_categories/temp')) {
                    $filename = basename($attachment);
                    $newPath = "{$finalDir}/{$filename}";
                    $thumbPath = "{$finalDir}/thumbs/{$filename}";

                    if ($disk->exists($attachment)) {

                        $disk->move($attachment, $newPath);
                        $newAttachments[] = $newPath;

                        // Generate Thumbnail
                        if ($manager) {
                            try {
                                // full path needed for intervention
                                $fullPath = $disk->path($newPath);
                                $thumbFullPath = $disk->path($thumbPath);

                                $image = $manager->read($fullPath);
                                $image->scale(width: 150); // Resize
                                $image->save($thumbFullPath);

                            } catch (\Exception $e) {
                                // Fail silently or log
                            }
                        }
                    } else {
                        $newAttachments[] = $attachment;
                    }
                } else {
                    $newAttachments[] = $attachment;
                }
            }

            $record->update(['attachments' => $newAttachments]);
        }
    }
}
