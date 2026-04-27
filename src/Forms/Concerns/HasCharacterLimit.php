<?php

namespace Schmeits\FilamentCharacterCounter\Forms\Concerns;

use Closure;

trait HasCharacterLimit
{
    protected int | Closure | null $characterLimit = 0;

    protected bool | Closure $showCharacterCounter = true;

    public function characterLimit(int | Closure | null $value = null): self
    {
        $this->characterLimit = $value;

        return $this;
    }

    public function getCharacterLimit(): ?int
    {
        $character_limit = (int) $this->evaluate($this->characterLimit);

        if ($this->maxLength && $character_limit === 0) {
            return $this->getMaxLength();
        }

        return $character_limit;
    }

    public function showCharacterCounter(bool | Closure $condition = true): static
    {
        $this->showCharacterCounter = $condition;

        return $this;
    }

    public function getShowCharacterCounter(): bool
    {
        return $this->evaluate($this->showCharacterCounter);
    }
}
