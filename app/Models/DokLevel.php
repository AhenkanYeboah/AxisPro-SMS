<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokLevel extends Model
{
    protected $fillable = [
        'level', 'name', 'description', 'representative_verbs',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'representative_verbs' => 'array',
        ];
    }

    // Formats one level as a block for the generation prompt - keeps that
    // prompt-building logic in one place rather than duplicated wherever
    // DOK context gets assembled.
    public function toPromptBlock(): string
    {
        $verbs = implode(', ', $this->representative_verbs);

        return "DOK {$this->level} - {$this->name}: {$this->description} Representative verbs: {$verbs}.";
    }
}
