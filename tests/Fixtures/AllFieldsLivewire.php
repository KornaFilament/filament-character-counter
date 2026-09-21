<?php

namespace Schmeits\FilamentCharacterCounter\Tests\Fixtures;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Livewire\Component;
use Schmeits\FilamentCharacterCounter\Forms\Components\RichEditor;
use Schmeits\FilamentCharacterCounter\Forms\Components\Textarea;
use Schmeits\FilamentCharacterCounter\Forms\Components\TextInput;

/**
 * Fixture that renders all three of the package's fields at once.
 *
 * WHY: this package's blade views are copies of the Filament views with the
 * counter added. As soon as Filament renames a view helper, our views no longer
 * compile. Only by actually rendering them does that come to light.
 */
class AllFieldsLivewire extends Component implements HasForms
{
    use InteractsWithForms;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function form(Schema $form): Schema
    {
        return $form
            ->statePath('data')
            ->schema([
                TextInput::make('title')
                    ->characterLimit(100),

                TextInput::make('subtitle')
                    ->maxLength(60)
                    ->showInsideControl(),

                Textarea::make('description')
                    ->characterLimit(500),

                RichEditor::make('content')
                    ->characterLimit(1000),

                TextInput::make('hidden_counter')
                    ->characterLimit(100)
                    ->showCharacterCounter(false),
            ]);
    }

    public function mount(): void
    {
        $this->form->fill([
            'title' => '1234567890',
        ]);
    }

    public function render(): string
    {
        return <<<'HTML'
        <div>
            {{ $this->form }}
        </div>
        HTML;
    }
}
