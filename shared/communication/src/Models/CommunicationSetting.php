<?php

namespace Shared\Communication\Models;

class CommunicationSetting extends CommunicationModel
{
    protected $casts = [
        'chat_enabled' => 'boolean',
        'allow_direct_messages' => 'boolean',
        'allow_employee_group_creation' => 'boolean',
        'allow_file_sharing' => 'boolean',
        'allow_message_editing' => 'boolean',
        'allow_message_deletion' => 'boolean',
        'enable_read_receipts' => 'boolean',
        'enable_presence' => 'boolean',
        'enable_typing_indicators' => 'boolean',
        'allow_everyone_mentions' => 'boolean',
        'auto_department_channels' => 'boolean',
        'auto_team_channels' => 'boolean',
        'auto_branch_channels' => 'boolean',
        'industry_channel_templates' => 'array',
    ];
}
