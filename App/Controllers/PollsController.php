<?php
// AI-GENERATED: Polls CRUD and voting controller (GitHub Copilot / ChatGPT), 2026-01-19

namespace App\Controllers;

require_once __DIR__ . '/../../Framework/ClassLoader.php';

use App\Repositories\PollsRepository;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Session;
use PDOException;

class PollsController extends BaseController
{
    private ?PollsRepository $repository = null;
    private ?Session $flashSession = null;

    public function authorize(Request $request, string $action): bool
    {
        $action = strtolower($action);
        $memberRoles = ['member', 'treasurer', 'admin'];

        if (in_array($action, ['index', 'show', 'vote'], true)) {
            return $this->requireRole($memberRoles);
        }

        if (in_array($action, ['new', 'store', 'delete', 'setstatus'], true)) {
            return $this->requireRole(['admin']);
        }

        return $this->requireLogin();
    }

    public function index(Request $request): Response
    {
        $polls = $this->repo()->findAll();

        return $this->html([
            'activeModule' => 'polls',
            'polls' => $polls,
            'canManage' => $this->requireRole(['admin']),
            'successMessage' => $this->consumeFlash('polls.success'),
            'errorMessage' => $this->consumeFlash('polls.error'),
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->flash('polls.error', 'Poll not found.');
            return $this->redirect($this->url('Polls.index'));
        }

        $poll = $this->repo()->findPollById($id);
        if ($poll === null) {
            $this->flash('polls.error', 'Poll not found.');
            return $this->redirect($this->url('Polls.index'));
        }

        $options = $this->repo()->listOptions($id);
        $results = $this->repo()->getResults($id);
        $userId = $this->user?->getIdentity()?->getId();
        $hasVoted = $userId !== null && $this->repo()->hasUserVoted($id, $userId);

        return $this->html([
            'activeModule' => 'polls',
            'poll' => $poll,
            'options' => $options,
            'results' => $results,
            'errors' => [],
            'selectedOptionId' => 0,
            'hasVoted' => $hasVoted,
            'canManage' => $this->requireRole(['admin']),
            'successMessage' => $this->consumeFlash('polls.success'),
            'errorMessage' => $this->consumeFlash('polls.error'),
        ], 'show');
    }

    public function new(Request $request): Response
    {
        return $this->html([
            'activeModule' => 'polls',
            'errors' => [],
            'question' => '',
            'status' => 'open',
            'optionsInput' => '',
        ], 'new');
    }

    public function store(Request $request): Response
    {
        $question = trim((string)($request->post('question') ?? ''));
        $status = trim((string)($request->post('status') ?? 'open'));
        $optionsInput = (string)($request->post('options') ?? '');

        [$errors, $normalizedOptions, $isActive] = $this->validatePollInput($question, $status, $optionsInput);

        if (!empty($errors)) {
            return $this->html([
                'activeModule' => 'polls',
                'errors' => $errors,
                'question' => $question,
                'status' => $status,
                'optionsInput' => $optionsInput,
            ], 'new');
        }

        $creatorId = $this->user?->getIdentity()?->getId();

        try {
            $pollId = $this->repo()->createPoll($question, $isActive, $creatorId);
            foreach ($normalizedOptions as $optionText) {
                $this->repo()->addOption($pollId, $optionText);
            }
        } catch (PDOException $exception) {
            if (isset($pollId)) {
                $this->repo()->deletePoll($pollId);
            }
            $this->flash('polls.error', 'Unable to create poll. Please try again.');

            return $this->redirect($this->url('Polls.new'));
        }

        $this->flash('polls.success', 'Poll created successfully.');

        return $this->redirect($this->url('Polls.show', ['id' => $pollId]));
    }

    public function vote(Request $request): Response
    {
        $pollId = (int)($request->get('id') ?? $request->post('id') ?? 0);
        $optionId = (int)($request->post('option_id') ?? 0);

        if ($pollId <= 0) {
            $this->flash('polls.error', 'Poll not found.');
            return $this->redirect($this->url('Polls.index'));
        }

        $poll = $this->repo()->findPollById($pollId);
        if ($poll === null) {
            $this->flash('polls.error', 'Poll not found.');
            return $this->redirect($this->url('Polls.index'));
        }

        $userId = $this->user?->getIdentity()?->getId();
        if ($userId === null) {
            $this->flash('polls.error', 'You must be logged in to vote.');
            return $this->redirect($this->url('Auth.loginForm'));
        }

        $options = $this->repo()->listOptions($pollId);
        $validOptionIds = array_map(static fn ($opt) => (int)($opt['id'] ?? 0), $options);
        $hasVoted = $this->repo()->hasUserVoted($pollId, $userId);
        $errors = [];

        if ((int)($poll['is_active'] ?? 0) !== 1) {
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

        if (!empty($errors)) {
            $results = $this->repo()->getResults($pollId);

            return $this->html([
                'activeModule' => 'polls',
                'poll' => $poll,
                'options' => $options,
                'results' => $results,
                'errors' => $errors,
                'selectedOptionId' => $optionId,
                'hasVoted' => $hasVoted,
                'canManage' => $this->requireRole(['admin']),
            ], 'show');
        }

        try {
            $this->repo()->addVote($pollId, $optionId, $userId);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $this->flash('polls.error', 'You already voted in this poll.');
                return $this->redirect($this->url('Polls.show', ['id' => $pollId]));
            }
            throw $exception;
        }

        $this->flash('polls.success', 'Thanks for voting!');

        return $this->redirect($this->url('Polls.show', ['id' => $pollId]));
    }

    public function delete(Request $request): Response
    {
        $pollId = (int)($request->get('id') ?? $request->post('id') ?? 0);

        if ($pollId <= 0) {
            $this->flash('polls.error', 'Poll not found.');
            return $this->redirect($this->url('Polls.index'));
        }

        $poll = $this->repo()->findPollById($pollId);
        if ($poll === null) {
            $this->flash('polls.error', 'Poll not found.');
            return $this->redirect($this->url('Polls.index'));
        }

        try {
            $this->repo()->deletePoll($pollId);
            $this->flash('polls.success', 'Poll deleted.');
        } catch (PDOException $exception) {
            $this->flash('polls.error', 'Unable to delete poll.');
        }

        return $this->redirect($this->url('Polls.index'));
    }

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

        $rawOptions = preg_split('/\r\n|\r|\n/', $optionsInput);
        $seen = [];
        $hasDuplicate = false;
        foreach ($rawOptions as $line) {
            $value = trim($line);
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

    private function repo(): PollsRepository
    {
        if ($this->repository === null) {
            $this->repository = new PollsRepository();
        }

        return $this->repository;
    }

    private function session(): Session
    {
        if ($this->flashSession === null) {
            $this->flashSession = $this->app->getSession();
        }

        return $this->flashSession;
    }

    private function flash(string $key, mixed $value): void
    {
        $this->session()->set($key, $value);
    }

    private function consumeFlash(string $key): mixed
    {
        $value = $this->session()->get($key);
        $this->session()->remove($key);

        return $value;
    }
}
