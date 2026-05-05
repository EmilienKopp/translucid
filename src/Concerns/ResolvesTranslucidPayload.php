<?php

namespace Splitstack\Translucid\Concerns;

trait ResolvesTranslucidPayload
{
    private function resolveModelClass(): string
    {
        if (is_string($this->model)) {
            return $this->model;
        }

        if (is_object($this->model)) {
            return get_class($this->model);
        }

        return $this->modelClass ?? 'unknown';
    }

    private function resolveKey(): string
    {
        if (is_string($this->model)) {
            return $this->model;
        }

        if (is_object($this->model) && method_exists($this->model, 'getKey')) {
            return (string) $this->model->getKey();
        }

        if ($this->keyColumn && is_array($this->model) && isset($this->model[$this->keyColumn])) {
            return (string) $this->model[$this->keyColumn];
        }

        return 'unknown';
    }

    private function resolveTableName(): string
    {
        if ($this->table) {
            return $this->table;
        }

        if (is_string($this->model)) {
            return $this->model;
        }

        if (is_object($this->model) && method_exists($this->model, 'getTable')) {
            return $this->model->getTable();
        }

        return 'unknown';
    }
}
