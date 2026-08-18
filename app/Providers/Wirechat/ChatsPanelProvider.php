<?php

namespace App\Providers\Wirechat;

use Wirechat\Wirechat\Enums\ColorTone;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;
use Wirechat\Wirechat\Support\Color;
use Wirechat\Wirechat\Support\Enums\EmojiPickerPosition;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;

class ChatsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('chats')
            ->path('chats')
            ->middleware(['web', 'auth'])
            ->heading('Messages')
            ->chatsSearch()
            ->unreadIndicator(type: UnreadIndicatorType::Count)
            ->createChatAction()
            ->createGroupAction()
            ->deleteChatAction()
            ->clearChatAction()
            ->deleteMessageActions()
            ->messageReplyAction()
            ->emojiPicker(position: EmojiPickerPosition::Docked)
            ->attachments()
            ->settings()
            ->parseMessageUrls()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->colorTone(ColorTone::Soft)
            ->redirectToHomeAction(url: '/dashboard')
            ->default();
    }
}
