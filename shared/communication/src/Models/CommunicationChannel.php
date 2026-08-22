<?php

namespace Shared\Communication\Models;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

class CommunicationChannel extends CommunicationModel
{
    public const TYPES = ['Direct', 'Group', 'Department', 'Branch', 'Role', 'Industry', 'Team', 'Project', 'Management', 'Announcement', 'Record'];

    protected $casts = [
        'is_private' => 'boolean',
        'settings' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function department() { return $this->belongsTo(Department::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function team() { return $this->belongsTo(Team::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function record() { return $this->morphTo(); }
    public function members() { return $this->hasMany(ChannelMember::class); }
    public function messages() { return $this->hasMany(Message::class); }
    public function announcements() { return $this->hasMany(Announcement::class); }
    public function pins() { return $this->hasMany(ConversationPin::class, 'communication_channel_id'); }
}
