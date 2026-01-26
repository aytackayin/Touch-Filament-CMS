<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use BackedEnum;

class BreezyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected string $view = 'filament.pages.breezy-profile';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'my-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('ProfileTabs')
                    ->tabs([
                        Tab::make('PersonalInfo')
                            ->label(__('user.label.personal_info'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label(__('filament-breezy::default.fields.avatar'))
                                    ->avatar()
                                    ->imageEditor()
                                    ->circleCropper()
                                    ->directory('avatars')
                                    ->disk('attachments')
                                    ->live(),

                                Grid::make()->schema([
                                    TextInput::make('name')
                                        ->label(__('user.label.name'))
                                        ->required(),
                                    TextInput::make('email')
                                        ->label(__('user.label.email'))
                                        ->required()
                                        ->email()
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
                                    ->defaultItems(0)
                                    ->createItemButtonLabel(__('user.label.add_account'))
                                    ->grid(2)
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
                                    ])
                                    ->default('richtext')
                            ]),

                        Tab::make('Extension')
                            ->label('Chrome Eklentisi')
                            ->icon('heroicon-o-puzzle-piece')
                            ->schema([
                                Grid::make(1)->schema([
                                    Section::make()
                                        ->description('YouTube videolarını tek tıkla blog makalesine dönüştürmek için bu bölümdeki API anahtarını kullanın.')
                                        ->schema([
                                            TextInput::make('chrome_token')
                                                ->label('API Anahtarınız')
                                                ->password()
                                                ->revealable()
                                                ->readOnly()
                                                ->dehydrated()
                                                ->helperText('Bu anahtarı Chrome eklentisi ayarlarında "API Key" alanına yapıştırın.'),

                                            Actions::make([
                                                Action::make('generateToken')
                                                    ->label('Yeni Anahtar Oluştur')
                                                    ->icon('heroicon-o-arrow-path')
                                                    ->color('warning')
                                                    ->action(function (callable $set) {
                                                        $token = \Illuminate\Support\Str::random(40);
                                                        $set('chrome_token', $token);

                                                        Notification::make()
                                                            ->title('Yeni API anahtarı hazırlandı. Kaydet butonu ile kalıcı hale getirebilirsiniz.')
                                                            ->warning()
                                                            ->send();
                                                    })
                                                    ->requiresConfirmation()
                                                    ->modalHeading('Emin misiniz?')
                                                    ->modalDescription('Yeni bir anahtar oluşturduğunuzda, eklenti içindeki eski anahtarınız geçersiz kalacaktır.'),
                                            ]),
                                        ]),
                                ]),
                            ]),

                        Tab::make('Password')
                            ->label(__('user.label.password_section'))
                            ->icon('heroicon-o-key')
                            ->schema([
                                TextInput::make('new_password')
                                    ->label(__('filament-breezy::default.fields.new_password'))
                                    ->password()
                                    ->confirmed()
                                    ->autocomplete('new-password'),
                                TextInput::make('new_password_confirmation')
                                    ->label(__('filament-breezy::default.fields.new_password_confirmation'))
                                    ->password()
                                    ->autocomplete('new-password')
                            ]),
                    ])
                    ->persistTabInQueryString(),

                Actions::make([
                    Action::make('save')
                        ->label(__('filament-breezy::default.profile.personal_info.submit.label'))
                        ->submit('submit') // This triggers form submission
                        ->size('lg')
                        ->extraAttributes([
                            'wire:loading.attr' => 'disabled',
                            'wire:target' => 'data.avatar_url, submit',
                        ]),
                ])
                    ->alignStart()
                    ->extraAttributes([
                        'style' => 'margin-top: 20px;',
                    ]),
            ])
            ->statePath('data')
            ->model(auth()->user());
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!empty($data['new_password'])) {
            $user->password = $data['new_password'];
        }

        if (isset($data['default_editor'])) {
            $user->default_editor = $data['default_editor'];
        }

        $oldAvatar = $user->avatar_url;

        $realData = collect($data)->except([
            'new_password',
            'new_password_confirmation',
            'default_editor'
        ])->toArray();

        $user->fill($realData);

        if ($oldAvatar && $user->isDirty('avatar_url')) {
            Storage::disk('attachments')->delete($oldAvatar);
        }

        $user->save();

        Notification::make()
            ->title(__('filament-breezy::default.profile.personal_info.notify'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('filament-breezy::default.profile.profile');
    }
}
