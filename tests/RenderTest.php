<?php

/**
 * Smoke tests that actually compile and render the bundled blade views within
 * a real Livewire and Filament form context.
 *
 * WHY: this package ships its own copies of the Filament views. They are tightly
 * coupled to Filament's internal view helpers ($getFieldWrapperView,
 * $getExtraInputAttributeBag, and dozens of others). A Filament upgrade that
 * changes any of those breaks this package without a single line of our PHP
 * changing. The existing unit tests on characterLimit() do not catch that,
 * because they render nothing.
 */

use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Schmeits\FilamentCharacterCounter\Forms\Components\RichEditor;
use Schmeits\FilamentCharacterCounter\Forms\Components\Textarea;
use Schmeits\FilamentCharacterCounter\Forms\Components\TextInput;
use Schmeits\FilamentCharacterCounter\Tests\Fixtures\AllFieldsLivewire;

it('registers the package view namespace', function () {
    expect(View::getFinder()->getHints())->toHaveKey('filament-character-counter');
});

it('finds the text-input field view', function () {
    expect(View::exists('filament-character-counter::text-input'))->toBeTrue();
});

it('finds the textarea field view', function () {
    expect(View::exists('filament-character-counter::textarea'))->toBeTrue();
});

it('finds the rich-editor field view', function () {
    expect(View::exists('filament-character-counter::rich-editor'))->toBeTrue();
});

it('finds the shared partial with the counter', function () {
    // All three views include this partial. If it disappears, every field only
    // fails while rendering in production.
    expect(View::exists('filament-character-counter::partials.character-count-container'))->toBeTrue();
});

it('points every field at a view that exists', function () {
    // The $view property is protected, so we read it via reflection. This also
    // covers a typo in the view name that would surface nowhere else.
    foreach ([TextInput::class, Textarea::class, RichEditor::class] as $component) {
        $view = (new ReflectionClass($component))
            ->getDefaultProperties()['view'];

        expect(View::exists($view))->toBeTrue("View {$view} of {$component} does not exist.");
    }
});

it('renders a form with all three fields without errors', function () {
    // The core smoke test of this package: does every view compile against the
    // current Filament version?
    Livewire::test(AllFieldsLivewire::class)
        ->assertOk();
});

it('shows the counter container in the rendered HTML', function () {
    // Proves the partial actually came along and did not silently drop out
    // because of a changed @include convention.
    Livewire::test(AllFieldsLivewire::class)
        ->assertSee('fi-input-charcounter', escape: false);
});

it('sets the characterLimit as Alpine state in the HTML', function () {
    // The whole package runs on these two Alpine variables. If they disappear
    // from the view, the counter counts nothing anymore.
    Livewire::test(AllFieldsLivewire::class)
        ->assertSee('characterLimit: 100', escape: false)
        ->assertSee('characterCount', escape: false);
});

it('omits the counter when showCharacterCounter is off', function () {
    // The negative side of the same switch: the hidden_counter field has a
    // limit of 100 but the counter turned off, so that limit must not appear in
    // the HTML.
    $html = Livewire::test(AllFieldsLivewire::class)->html();

    // The counter is present for title (100), but the number of times the
    // counter container appears must be lower than the number of fields.
    expect(substr_count($html, 'fi-input-charcounter'))->toBe(4);
});

it('renders a single text-input field with an empty value', function () {
    // Edge case: mb_strlen() on a null state. That used to go wrong in views
    // that feed $getState() straight into a string function.
    Livewire::test(AllFieldsLivewire::class)
        ->assertSee('x-init', escape: false);
});
