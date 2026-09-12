<?php

namespace Modules\Chama\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Chama\Models\Ballot;
use Modules\Chama\Models\Member;
use Modules\Chama\Models\Vote;

class VotingService
{
    public function __construct(private ChamaNumberService $numbers, private ChamaAuditService $audit) {}

    public function createBallot(array $data): Ballot
    {
        return DB::transaction(function () use ($data) {
            $ballot = Ballot::create(array_merge($data, [
                'ballot_number' => $data['ballot_number'] ?? $this->numbers->ballotNumber(),
                'status' => $data['status'] ?? 'Draft',
            ]));

            $this->audit->record('ballot.created', $ballot);

            return $ballot;
        });
    }

    public function vote(Ballot $ballot, Member $member, array $data): Vote
    {
        return DB::transaction(function () use ($ballot, $member, $data) {
            if ($ballot->votes()->where('member_id', $member->id)->exists()) {
                throw ValidationException::withMessages(['member_id' => 'This member has already voted on this ballot.']);
            }

            $vote = Vote::create([
                'ballot_id' => $ballot->id,
                'member_id' => $member->id,
                'candidate_member_id' => $data['candidate_member_id'] ?? null,
                'vote_value' => $data['vote_value'] ?? null,
                'metadata' => $ballot->is_secret ? ['secret' => true] : ($data['metadata'] ?? null),
                'voted_at' => now(),
            ]);

            $this->audit->record('vote.cast', $ballot, [], ['member_id' => $member->id]);

            return $vote;
        });
    }
}
