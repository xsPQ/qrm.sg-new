<?php

namespace App\Domain\QrTypes;

use InvalidArgumentException;

class EventQr
{
    public function __construct(
        public readonly string $title,
        public readonly string $start,
        public readonly ?string $end,
        public readonly ?string $location,
        public readonly ?string $description,
        public readonly string $timezone,
    ) {}

    public static function fromArray(array $data): self
    {
        $errors = [];

        $title = $data['title'] ?? null;
        if ($title === null || $title === '') {
            $errors[] = 'title is required';
        } elseif (!is_string($title) || mb_strlen($title) > 255) {
            $errors[] = 'title must be a string of max 255 characters';
        }

        $start = $data['start'] ?? null;
        if ($start === null || $start === '') {
            $errors[] = 'start is required';
        } elseif (!strtotime($start)) {
            $errors[] = 'start must be a valid datetime';
        }

        $end = $data['end'] ?? null;
        if ($end !== null && $end !== '' && !strtotime($end)) {
            $errors[] = 'end must be a valid datetime or null';
        }

        $location = $data['location'] ?? null;
        if ($location !== null && !is_string($location)) {
            $errors[] = 'location must be a string';
        }

        $description = $data['description'] ?? null;
        if ($description !== null && !is_string($description)) {
            $errors[] = 'description must be a string';
        }

        $timezone = $data['timezone'] ?? 'UTC';
        if (!is_string($timezone) || $timezone === '') {
            $timezone = 'UTC';
        }

        if ($errors !== []) {
            throw new InvalidArgumentException('Invalid EventQr content: ' . implode('; ', $errors));
        }

        return new self(
            title: $title,
            start: $start,
            end: $end,
            location: $location,
            description: $description,
            timezone: $timezone,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'start' => $this->start,
            'end' => $this->end,
            'location' => $this->location,
            'description' => $this->description,
            'timezone' => $this->timezone,
        ], fn($v) => $v !== null);
    }

    public static function rules(): array
    {
        return [
            'content.title' => ['required', 'string', 'max:255'],
            'content.start' => ['required', 'date'],
            'content.end' => ['nullable', 'date', 'after:content.start'],
            'content.location' => ['nullable', 'string', 'max:255'],
            'content.description' => ['nullable', 'string', 'max:2000'],
            'content.timezone' => ['nullable', 'string', 'max:64'],
        ];
    }
}
