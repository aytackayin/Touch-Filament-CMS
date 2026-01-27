<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\User;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('UserTabs')
                    ->tabs([
                        Tab::make('General')
                            ->label(__('user.label.personal_info'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label(__('filament-breezy::default.fields.avatar'))
                                    ->avatar()
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->directory('avatars')
                                    ->disk('attachments'),

                                Grid::make()->schema([
                                    TextInput::make('name')
                                        ->label(__('user.label.name'))
                                        ->required(),
                                    TextInput::make('email')
                                        ->label(__('user.label.email'))
                                        ->email()
                                        ->required()
                                        ->unique('users', 'email', ignoreRecord: true),
                                ]),

                                Grid::make()->schema([
                                    TextInput::make('phone')
                                        ->label(__('user.label.phone'))
                                        ->tel(),
                                    Textarea::make('address')
                                        ->label(__('user.label.address'))
                                        ->rows(3),
                                ]),
                            ]),

                        Tab::make('Security')
                            ->label(__('user.label.password_section'))
                            ->icon('heroicon-o-key')
                            ->schema([
                                Grid::make()->schema([
                                    TextInput::make('password')
                                        ->label(__('user.label.password'))
                                        ->password()
                                        ->confirmed()
                                        ->dehydrated(fn($state) => filled($state))
                                        ->required(fn(string $context): bool => $context === 'create'),
                                    TextInput::make('password_confirmation')
                                        ->label(__('user.label.password_confirmation'))
                                        ->required(fn(string $context): bool => $context === 'create')
                                        ->password()
                                        ->dehydrated(false),
                                ]),

                                Select::make('roles')
                                    ->label(__('filament-shield::filament-shield.column.roles'))
                                    ->searchable()
                                    ->preload()
                                    ->multiple()
                                    ->relationship('roles', 'name', function ($query) {
                                        /** @var User $authUser */
                                        $authUser = auth()->user();
                                        if ($authUser->hasRole('super_admin')) {
                                            return $query;
                                        }
                                        $authUserPermissions = $authUser->getAllPermissions()->pluck('id')->toArray();
                                        return $query->where('name', '!=', 'super_admin')
                                            ->whereDoesntHave('permissions', function ($q) use ($authUserPermissions) {
                                                $q->whereNotIn('id', $authUserPermissions);
                                            });
                                    }),
                            ]),

                        Tab::make('SocialMedia')
                            ->label(__('user.label.social_media'))
                            ->icon('heroicon-o-share')
                            ->schema([
                                Repeater::make('social_links')
                                    ->label(__('user.label.social_links'))
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('platform')
                                                ->label(__('user.label.platform'))
                                                ->options([
                                                    'instagram' => 'Instagram',
                                                    'twitter' => 'Twitter / X',
                                                    'facebook' => 'Facebook',
                                                    'linkedin' => 'LinkedIn',
                                                    'github' => 'GitHub',
                                                    'website' => 'Website',
                                                    'other' => 'Other',
                                                ])
                                                ->required(),
                                            TextInput::make('url')
                                                ->label(__('user.label.link'))
                                                ->placeholder('instagram.com/username')
                                                ->required()
                                                ->prefix('https://'),
                                        ]),
                                    ])
                                    ->createItemButtonLabel(__('user.label.add_account'))
                                    ->grid(2),
                            ]),

                        Tab::make('Preferences')
                            ->label(__('user.label.preferences'))
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Select::make('default_editor')
                                    ->label(__('user.label.default_editor'))
                                    ->options([
                                        'richtext' => 'Rich Text Editor (TinyMCE)',
                                        'markdown' => 'Markdown Editor',
                                        'simple' => 'Simple Textarea',
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }
}
