<?php

namespace Shared\Communication\Models;

class CommunicationPermission extends CommunicationModel
{
    protected $casts = [
        'can_message' => 'boolean',
        'can_create_channels' => 'boolean',
        'can_announce' => 'boolean',
        'rules' => 'array',
    ];
}
