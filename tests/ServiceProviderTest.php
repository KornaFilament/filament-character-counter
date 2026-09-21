<?php

/**
 * Smoke tests for the service provider, the config, the assets and the
 * translations.
 *
 * WHY: this package is loaded via package discovery into every Filament app.
 * The provider registers a config file, a view namespace, translations and a
 * CSS asset. Each of those four points at a path on disk. If one disappears,
 * the app falls over on startup or silently loses its styling.
 */

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;
use Schmeits\FilamentCharacterCounter\FilamentCharacterCounterServiceProvider;

it('registers the service provider in the application', function () {
    // getLoadedProviders() only contains providers that registered and booted
    // without errors, so this proves both phases at once.
    expect(app()->getLoadedProviders())
        ->toHaveKey(FilamentCharacterCounterServiceProvider::class);
});

it('is a Laravel service provider', function () {
    expect(new FilamentCharacterCounterServiceProvider(app()))
        ->toBeInstanceOf(ServiceProvider::class);
});

it('keeps the package name and view namespace stable', function () {
    // Both drive the names of config, views and translations. If they change,
    // published files break for existing consumers.
    expect(FilamentCharacterCounterServiceProvider::$name)->toBe('filament-character-counter')
        ->and(FilamentCharacterCounterServiceProvider::$viewNamespace)->toBe('filament-character-counter');
});

it('actually ships the config file on disk', function () {
    // The provider only registers the config file if it exists. Without the
    // file it silently skips it, so this must be checked separately.
    expect(file_exists(__DIR__ . '/../config/character-counter.php'))->toBeTrue();
});

it('merges the config into the application', function () {
    // packageBooted() reads config('character-counter.load_css'). If the config
    // is not merged, it falls back to the default and you notice nothing, until
    // someone changes the key in their own app and nothing happens.
    expect(config()->has('character-counter.load_css'))->toBeTrue()
        ->and(config('character-counter.load_css'))->toBeTrue();
});

it('ships the CSS file that the provider registers', function () {
    // This is a dist file produced by a build. If someone forgets to commit it,
    // FilamentAsset only throws an error in production.
    expect(file_exists(__DIR__ . '/../resources/dist/filament-character-counter.css'))->toBeTrue();
});

it('registers the CSS asset with Filament', function () {
    $ids = array_map(
        fn (Css $style): string => $style->getId(),
        FilamentAsset::getStyles(['schmeits/filament-character-counter']),
    );

    expect($ids)->toContain('filament-character-counter-styles');
});

it('points the registered CSS asset at an existing file', function () {
    // Registration says nothing about the file. This covers the case where
    // someone renames the dist file or forgets to commit the build.
    $styles = FilamentAsset::getStyles(['schmeits/filament-character-counter']);

    expect($styles)->not->toBeEmpty();

    foreach ($styles as $style) {
        expect(file_exists($style->getPath()))->toBeTrue(
            'Asset ' . $style->getId() . ' points at a path that does not exist.',
        );
    }
});

it('registers the package translations', function () {
    // The partial calls __('filament-character-counter::character-counter.*').
    // Without a registered namespace the counter shows the key itself.
    expect(Lang::has('filament-character-counter::character-counter.character_label'))->toBeTrue()
        ->and(Lang::has('filament-character-counter::character-counter.character_separator'))->toBeTrue();
});

it('has the same translation keys in every language', function () {
    // A missing key in one language shows that user the raw key on screen. That
    // surfaces nowhere else in the tests.
    $languages = glob(__DIR__ . '/../resources/lang/*', GLOB_ONLYDIR);

    $reference = array_keys(require __DIR__ . '/../resources/lang/en/character-counter.php');

    foreach ($languages as $language) {
        $keys = array_keys(require $language . '/character-counter.php');

        expect($keys)->toEqualCanonicalizing(
            $reference,
            'Language ' . basename($language) . ' has different keys than en.',
        );
    }
});

it('runs the test suite on an in-memory sqlite database', function () {
    // Explicit safeguard: these tests must never touch a real database.
    expect(config('database.default'))->toBe('testing')
        ->and(config('database.connections.testing.driver'))->toBe('sqlite')
        ->and(config('database.connections.testing.database'))->toBe(':memory:');
});
