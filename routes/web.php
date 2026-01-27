<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Illuminate\Http\Request;

Volt::route('/', 'home')->name('home');
Volt::route('/blog', 'blog-list')->name('blog.index');
Volt::route('/blog/{slug}', 'blog-detail')->name('blog.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/admin/preview', function (Request $request) {
    $modelClass = "App\\Models\\" . $request->query('model');
    $id = $request->query('id');
    $field = $request->query('field', 'content');

    if (!class_exists($modelClass))
        abort(404);

    $record = $modelClass::findOrFail($id);

    // Security: Only allow users with 'view' permission for this specific record
    if (auth()->user()->cannot('view', $record)) {
        abort(403, 'Yetkisiz erişim.');
    }

    return view('admin.universal-preview', [
        'content' => $record->{$field}
    ]);
})->middleware(['auth'])->name('admin.preview');

require __DIR__ . '/auth.php';
