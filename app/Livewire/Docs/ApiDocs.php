<?php

namespace App\Livewire\Docs;

use Livewire\Component;

class ApiDocs extends Component
{
    public string $activeSection = 'authentication';
    public string $activeLanguage = 'curl';

    public array $sections = [
        'authentication' => 'Authentication',
        'devices'        => 'Devices',
        'messages'       => 'Messages',
        'bulk'           => 'Bulk send',
        'webhooks'       => 'Webhooks',
        'errors'         => 'Error codes',
    ];

    public array $languages = ['curl', 'php', 'python', 'javascript'];

    public function setSection(string $section): void
    {
        $this->activeSection = $section;
    }

    public function setLanguage(string $lang): void
    {
        $this->activeLanguage = $lang;
    }

    public function render()
    {
        return view('livewire.docs.api-docs');
    }
}
