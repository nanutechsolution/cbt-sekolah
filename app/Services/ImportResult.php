<?php

namespace App\Services;

class ImportResult
{
    public int $created = 0;

    public int $updated = 0;

    /**
     * @var array<int, array{row: int, messages: array<int, string>}>
     */
    public array $errors = [];

    /**
     * @param  array<int, string>|string  $messages
     */
    public function addError(int $row, array|string $messages): void
    {
        $this->errors[] = [
            'row' => $row,
            'messages' => is_array($messages) ? array_values($messages) : [$messages],
        ];
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function summary(): string
    {
        $parts = [
            "{$this->created} data ditambahkan",
            "{$this->updated} data diperbarui",
        ];

        if ($this->hasErrors()) {
            $parts[] = count($this->errors).' baris gagal';
        }

        return implode(', ', $parts).'.';
    }

    /**
     * @return array<int, string>
     */
    public function errorMessages(int $limit = 5): array
    {
        return collect($this->errors)
            ->take($limit)
            ->map(fn (array $error): string => 'Baris '.$error['row'].': '.implode(' ', $error['messages']))
            ->all();
    }
}
