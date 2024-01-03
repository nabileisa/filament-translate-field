<?php

namespace SolutionForest\FilamentTranslateField\Forms\Component;

use Closure;
use Illuminate\Support\Collection;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\Component;

class Translate extends Component
{
    /**
     * @var view-string
     */
    protected string $view = 'filament-translate-field::forms.components.translate';

    protected null|Closure|array|Collection $locales = null;

    protected null|Closure|array|Collection $localeLabels = null;
    
    protected Closure|bool $hasPrefixLocaleLabel = false;

    protected Closure|bool $hasSuffixLocaleLabel = false;

    protected null|Closure $fieldTranslatableLabel = null;

    final public function __construct(array $schema = [])
    {
        $this->schema($schema);
    }

    public static function make(array $schema = []): static
    {
        $static = app(static::class, ['schema' => $schema]);
        $static->configure();

        return $static;
    }

    public function locales(Closure|array|Collection $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function localeLabels(Closure|array|Collection $labels): static
    {
        $this->localeLabels = $labels;

        return $this;
    }

    public function prefixLocaleLabel(Closure|bool $condition = true): static
    {
        $this->hasPrefixLocaleLabel = $condition;

        return $this;
    }

    public function suffixLocaleLabel(Closure|bool $condition = true): static
    {
        $this->hasSuffixLocaleLabel = $condition;

        return $this;
    }

    public function fieldTranslatableLabel(null|Closure $fieldTranslatableLabel = null): static
    {
        $this->fieldTranslatableLabel = $fieldTranslatableLabel;

        return $this;
    }

    public function getLocales(): array | Collection
    {
        return $this->evaluate($this->locales) ?? config('app.locales') ?? [];
    }

    public function getLocaleLabels(): array | Collection
    {
        return $this->evaluate($this->localeLabels) ?? [];
    }

    public function getLocaleLabel(string $locale): string
    {
        $labels =  $this->evaluate($this->localeLabels, [
            'locale' => $locale
        ]);

        $label = null;

        if ($labels && is_array($labels)) {
            $label = data_get($labels, $locale);
        }

        return $label ?? $locale;
    }

    public function hasPrefixLocaleLabel(Component $component, string $locale): bool
    {
        return boolval($this->evaluate($this->hasPrefixLocaleLabel, [
            'field' => $component,
            'locale' => $locale,
            ]) ?? false);
    }

    public function hasSuffixLocaleLabel(Component $component, string $locale): bool
    {
        return boolval($this->evaluate($this->hasSuffixLocaleLabel, [
            'field' => $component,
            'locale' => $locale,
            ]) ?? false);
    }

    public function getFieldTranslatableLabel(Component $component, string $locale): ?string
    {
        return $this->evaluate($this->fieldTranslatableLabel, [
            'field' => $component,
            'locale' => $locale,
        ]);
    }

    /**
     * @return array<ComponentContainer>
     */
    public function getChildComponentContainers(bool $withHidden = false): array
    {
        $containers = [];

        $locales = $this->getLocales();
        $components = $this->getChildComponents();

        foreach ($locales as $locale) {
            $containers[$locale] = ComponentContainer::make($this->getLivewire())
                ->parentComponent($this)
                ->components(collect($components)
                    ->map(fn ($component) => $this->prepareTranslateLocaleComponent($component, $locale))
                    ->all()
                )
                ->getClone();
        }

        return $containers;
    }

    protected function prepareTranslateLocaleComponent(Component $component, string $locale)
    {
        $localeComponent = clone $component;
        
        $localeComponent->label($this->getFieldTranslatableLabel($component, $locale) ?? $component->getLabel());

        $localeLabel = $this->getLocaleLabel($locale);
        if ($this->hasPrefixLocaleLabel($component, $locale)) {
            $localeComponent->label("({$localeLabel}) {$localeComponent->getLabel()}");
        }
        if ($this->hasSuffixLocaleLabel($component, $locale)) {
            $localeComponent->label("{$localeComponent->getLabel()} ({$localeLabel})");
        }

        // Spatie transltable field format
        $localeComponent->name($component->getName().'.'.$locale);
        $localeComponent->statePath($localeComponent->getName());

        return $localeComponent;
    }
    
    /**
     * @return array<mixed>
     */
    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        if ($parameterName == 'locales') {
            return [$this->getLocales()];
        }
        return parent::resolveDefaultClosureDependencyForEvaluationByName($parameterName);
    }
}