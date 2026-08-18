<?php

namespace App\Providers\Wirechat;

use Wirechat\Wirechat\Enums\ColorTone;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;
use Wirechat\Wirechat\Support\Color;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;

class AdminChatsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin-chats')
            ->path('admin/chats')
            ->middleware(['web', 'auth'])
            ->heading('Chat Management')
            ->chatsSearch()
            ->unreadIndicator(type: UnreadIndicatorType::Count)
            ->createGroupAction()
            ->deleteChatAction()
            ->clearChatAction()
            ->deleteMessageActions()
            ->colors([
                'primary' => Color::Zinc,
            ])
            ->colorTone(ColorTone::Soft);
    }
}
