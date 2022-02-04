<?php

use RalphJSmit\Laravel\SEO\Models\SEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use RalphJSmit\Laravel\SEO\Tests\Fixtures\Page;
use RalphJSmit\Laravel\SEO\Tests\Fixtures\PageWithoutTitleSuffixFunction;
use RalphJSmit\Laravel\SEO\Tests\Fixtures\PageWithoutTitleSuffixProperty;
use RalphJSmit\Laravel\SEO\Tests\Fixtures\PageWithOverrides;

it('can morph a model to the SEO model', function () {
    $page = Page::create();

    $page->addSEO();

    expect($page->seo)->toBeInstanceOf(SEO::class);
});

it('can prepare the SEO for use on a page', function () {
    $seo = Page::create()->addSEO()->seo;

    $export = $seo->prepareForUsage();

    expect($export)->toBeInstanceOf(SEOData::class);
});

it('can add properties to a SEO model', function (string $property, string $input) {
    $page = Page::create()->addSEO();

    $page->seo->{$property} = $input;
    $page->push();

    expect($page->refresh()->seo)
        ->{$property}->toBe($input);

    expect($page->seo->prepareForUsage())
        ->{$property}->toBe($input);
})->with([
    ['description', 'This is a description'],
    ['title', 'My Cool Page Title'],
]);

it('can override certain SEO Data', function (string $overriddenProperty, string $input) {
    $page = PageWithOverrides::create()->addSEO();

    $page->seo->update(
        $defaults = [
            'title' => 'Default title',
            'description' => 'Default description',
        ]
    );

    $page::$overrides = [
        $overriddenProperty => 'Custom override',
    ];

    $page->seo->{$overriddenProperty} = $input;
    $page->push();

    expect($page->refresh()->seo)
        ->{$overriddenProperty}->toBe($input);

    expect($page->seo->prepareForUsage())
        ->{$overriddenProperty}->toBe('Custom override');

    foreach (collect($defaults)->except($overriddenProperty) as $property => $value) {
        expect($page->seo->prepareForUsage())
            ->{$property}->toBe($value);
    }
})->with([
    ['description', 'This is a description'],
    ['title', 'My Cool Page Title'],
]);

it('can give the title of a page a suffix it was specified', function () {
    config()->set('seo.title.suffix', ' | TestCases');

    $seo = Page::create()->addSEO()->seo;

    $seo->update([
        'title' => 'My page title',
    ]);

    expect($seo->prepareForUsage())
        ->title->toBe('My page title | TestCases');
});

it('can disable the suffix in the page model', function () {
    config()->set('seo.title.suffix', ' | TestCases');

    $page = PageWithoutTitleSuffixProperty::create()->addSEO();

    $page->seo->update([
        'title' => 'My page title',
    ]);

    expect($page->seo->prepareForUsage())
        ->title->toBe('My page title');
});

it('can disable the suffix in the page model dynamically via a function', function () {
    config()->set('seo.title.suffix', ' | TestCases');

    $page = PageWithoutTitleSuffixFunction::create()->addSEO();

    $page->seo->update([
        'title' => 'My page title',
    ]);

    expect($page->seo->prepareForUsage())
        ->title->toBe('My page title');
});

it('can use the config default for a custom override', function (string $defaultSiteValueFromConfig, $input) {
    config()->set("seo.fallback_{$defaultSiteValueFromConfig}", $input);

    $seo = Page::create()->addSEO()->seo;

    expect($seo->prepareForUsage())
        ->{$defaultSiteValueFromConfig}->toBe($input);
})->with([
    //    ['title', 'Default Website Title'],
    ['description', 'Default Website Description'],
]);