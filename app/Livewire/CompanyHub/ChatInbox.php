<?php

declare(strict_types=1);

namespace App\Livewire\CompanyHub;

use App\Livewire\Shared\ChatInbox as SharedChatInbox;
use Livewire\Attributes\Title;

#[Title('Messages')]
final class ChatInbox extends SharedChatInbox
{
    public string $portal = 'hub';
}
