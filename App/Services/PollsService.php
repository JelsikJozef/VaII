<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PollsRepository;
use PDOException;

/**
 * Polls business logic (validation, permissions, workflows).
 *
 * HTTP-agnostic: returns DomainResult only.
 */
class PollsService
{
    public function __construct(
        private readonly PollsRepository $polls = new PollsRepository(),
    ) {
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array}
     */
    public function index(array $user): array
    {
        $role = isset($user['role']) ? (string)$user['role'] : null;
        $polls = $this->polls->findAll();

        $pollsWithPresentation = array_map(function (array $poll): array {
            $isActive = ((int)($poll['is_active'] ?? 0)) === 1;
            return $poll + $this->statusPresentation($isActive);
        }, $polls);

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'polls',
                'polls' => $pollsWithPresentation,
                'canManage' => $this->canManagePolls($role),
            ],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array,flash?:array}
     */
    public function show(array $user, int $pollId): array
    {
        if ($pollId <= 0) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Poll not found.'],
            ];
        }

        $poll = $this->polls->findPollById($pollId);
        if ($poll === null) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Poll not found.'],
            ];
        }

        $options = $this->polls->listOptions($pollId);
        $results = $this->polls->getResults($pollId);

        $userId = $user['userId'] ?? null;
        $hasVoted = $userId !== null ? $this->polls->hasUserVoted($pollId, (int)$userId) : false;
        $isOpen = ((int)($poll['is_active'] ?? 0)) === 1;

        $role = isset($user['role']) ? (string)$user['role'] : null;
        $poll += $this->statusPresentation($isOpen);

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'polls',
                'pollId' => $pollId,
                'poll' => $poll,
                'options' => $options,
                'results' => $results,
                'errors' => [],
                'selectedOptionId' => 0,
                'hasVoted' => $hasVoted,
                'canVote' => $isOpen && !$hasVoted,
                'canManage' => $this->canManagePolls($role),
            ],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array,flash?:array}
     */
    public function newForm(array $user): array
    {
        $role = isset($user['role']) ? (string)$user['role'] : null;
        if (!$this->canManagePolls($role)) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Forbidden.'],
            ];
        }

        return [
            'ok' => true,
            'payload' => [
                'activeModule' => 'polls',
                'errors' => [],
                'question' => '',
                'status' => 'open',
                'optionsInput' => '',
            ],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @param array{question?:string|null,status?:string|null,options?:string|null} $input
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function store(array $user, array $input): array
    {
        $role = isset($user['role']) ? (string)$user['role'] : null;
        if (!$this->canManagePolls($role)) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Forbidden.'],
            ];
        }

        $question = trim((string)($input['question'] ?? ''));
        $status = trim((string)($input['status'] ?? 'open'));
        $optionsInput = (string)($input['options'] ?? '');

        [$errors, $normalizedOptions, $isActive] = $this->validatePollInput($question, $status, $optionsInput);

        if (!empty($errors)) {
            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'polls',
                    'question' => $question,
                    'status' => $status,
                    'optionsInput' => $optionsInput,
                ],
                'errors' => $errors,
            ];
        }

        $creatorId = $user['userId'] ?? null;

        try {
            $pollId = $this->polls->createPollWithOptions([
                'question' => $question,
                'is_active' => $isActive,
                'created_by' => $creatorId !== null ? (int)$creatorId : null,
            ], $normalizedOptions);
        } catch (PDOException $e) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Unable to create poll. Please try again.'],
            ];
        }

        return [
            'ok' => true,
            'payload' => ['id' => $pollId],
            'flash' => ['type' => 'success', 'message' => 'Poll created successfully.'],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @param array{pollId?:int|null, optionId?:int|null} $input
     * @return array{ok:bool,payload:array,errors?:array,flash?:array}
     */
    public function vote(array $user, array $input): array
    {
        $pollId = (int)($input['pollId'] ?? 0);
        $optionId = (int)($input['optionId'] ?? 0);

        if ($pollId <= 0) {
            return [
                'ok' => false,
                'payload' => ['pollId' => $pollId],
                'flash' => ['type' => 'error', 'message' => 'Poll not found.'],
            ];
        }

        $poll = $this->polls->findPollById($pollId);
        if ($poll === null) {
            return [
                'ok' => false,
                'payload' => ['pollId' => $pollId],
                'flash' => ['type' => 'error', 'message' => 'Poll not found.'],
            ];
        }

        $pollIdCanonical = (int)($poll['id'] ?? $pollId);

        $userId = $user['userId'] ?? null;
        if ($userId === null) {
            return [
                'ok' => false,
                'payload' => ['pollId' => $pollIdCanonical],
                'flash' => ['type' => 'error', 'message' => 'You must be logged in to vote.'],
            ];
        }

        $options = $this->polls->listOptions($pollIdCanonical);
        $validOptionIds = array_map(static fn($opt) => (int)($opt['id'] ?? 0), $options);
        $hasVoted = $this->polls->hasUserVoted($pollIdCanonical, (int)$userId);

        $errors = [];
        $isOpen = ((int)($poll['is_active'] ?? 0)) === 1;
        if (!$isOpen) {
            $errors['option_id'][] = 'This poll is not open for voting.';
        }
        if ($hasVoted) {
            $errors['option_id'][] = 'You already voted in this poll.';
        }
        if ($optionId <= 0) {
            $errors['option_id'][] = 'Please select an option.';
        } elseif (!in_array($optionId, $validOptionIds, true)) {
            $errors['option_id'][] = 'Selected option is invalid.';
        }

        $poll += $this->statusPresentation($isOpen);

        if (!empty($errors)) {
            $results = $this->polls->getResults($pollIdCanonical);
            $role = isset($user['role']) ? (string)$user['role'] : null;

            return [
                'ok' => false,
                'payload' => [
                    'activeModule' => 'polls',
                    'pollId' => $pollIdCanonical,
                    'poll' => $poll,
                    'options' => $options,
                    'results' => $results,
                    'selectedOptionId' => $optionId,
                    'hasVoted' => $hasVoted,
                    'canVote' => $isOpen && !$hasVoted,
                    'canManage' => $this->canManagePolls($role),
                ],
                'errors' => $errors,
            ];
        }

        try {
            $this->polls->addVote($pollIdCanonical, $optionId, (int)$userId);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return [
                    'ok' => false,
                    'payload' => ['pollId' => $pollIdCanonical],
                    'flash' => ['type' => 'error', 'message' => 'You already voted in this poll.'],
                ];
            }
            throw $e;
        }

        return [
            'ok' => true,
            'payload' => ['pollId' => $pollIdCanonical],
            'flash' => ['type' => 'success', 'message' => 'Thanks for voting!'],
        ];
    }

    /**
     * @param array{userId?:int|null, role?:string|null, isLoggedIn?:bool} $user
     * @return array{ok:bool,payload:array,flash?:array}
     */
    public function delete(array $user, int $pollId): array
    {
        $role = isset($user['role']) ? (string)$user['role'] : null;
        if (!$this->canManagePolls($role)) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Forbidden.'],
            ];
        }

        if ($pollId <= 0) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Poll not found.'],
            ];
        }

        $poll = $this->polls->findPollById($pollId);
        if ($poll === null) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Poll not found.'],
            ];
        }

        try {
            $this->polls->deletePoll($pollId);
        } catch (PDOException) {
            return [
                'ok' => false,
                'payload' => [],
                'flash' => ['type' => 'error', 'message' => 'Unable to delete poll.'],
            ];
        }

        return [
            'ok' => true,
            'payload' => ['id' => $pollId],
            'flash' => ['type' => 'success', 'message' => 'Poll deleted.'],
        ];
    }

    private function canManagePolls(?string $role): bool
    {
        return $role === 'admin';
    }

    private function statusPresentation(bool $isActive): array
    {
        return [
            'statusLabel' => $isActive ? 'Open' : 'Closed',
            'statusClass' => $isActive
                ? 'bg-success-subtle text-success border-success-subtle'
                : 'bg-danger-subtle text-danger border-danger-subtle',
        ];
    }

    /**
     * @return array{0:array<string,array<int,string>>,1:array<int,string>,2:bool}
     */
    private function validatePollInput(string $question, string $status, string $optionsInput): array
    {
        $errors = [];
        $normalizedOptions = [];

        if ($question === '') {
            $errors['question'][] = 'Question is required.';
        } elseif (mb_strlen($question) < 5) {
            $errors['question'][] = 'Question must be at least 5 characters.';
        } elseif (mb_strlen($question) > 300) {
            $errors['question'][] = 'Question must be at most 300 characters.';
        }

        $allowedStatuses = ['open', 'closed'];
        $isActive = $status === 'open';
        if ($status === '') {
            $errors['status'][] = 'Status is required.';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $errors['status'][] = 'Status must be open or closed.';
        }

        $rawOptions = preg_split('/\r\n|\r|\n/', $optionsInput) ?: [];
        $seen = [];
        $hasDuplicate = false;
        foreach ($rawOptions as $line) {
            $value = trim((string)$line);
            if ($value === '') {
                continue;
            }
            if (mb_strlen($value) > 200) {
                $errors['options'][] = 'Each option must be at most 200 characters.';
            }
            $key = mb_strtolower($value);
            if (isset($seen[$key])) {
                $hasDuplicate = true;
            } else {
                $seen[$key] = true;
                $normalizedOptions[] = $value;
            }
        }

        if ($hasDuplicate) {
            $errors['options'][] = 'Options must be unique.';
        }

        if (count($normalizedOptions) < 2) {
            $errors['options'][] = 'Please provide at least two options.';
        }

        return [$errors, $normalizedOptions, $isActive];
    }
}
