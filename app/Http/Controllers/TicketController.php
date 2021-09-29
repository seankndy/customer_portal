<?php

namespace App\Http\Controllers;

use App\Actions\CreateAccountTicketAction;
use App\Actions\CreateTicketReplyAction;
use App\DataTransferObjects\AccountTicketData;
use App\DataTransferObjects\TicketReplyData;
use App\Http\Requests\TicketReplyRequest;
use App\Http\Requests\TicketRequest;
use App\SonarApi\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TicketController extends Controller
{
    private const CACHE_TTL = 10;

    private Client $sonarClient;

    public function __construct(Client $sonarClient)
    {
        $this->sonarClient = $sonarClient;
    }

    public function index(): View
    {
        $tickets = $this->getTickets();

        $ticketAccounts = $this->associateTicketsToAccounts($tickets);

        return view("pages.tickets.index", [
            'tickets' => $tickets,
            'ticketAccounts' => $ticketAccounts,
        ]);
    }

    /**
     * Show an individual ticket
     * @return \Illuminate\Http\RedirectResponse|View
     */
    public function show(int $id)
    {
        $tickets = $this->getTickets();

        $ticketAccounts = $this->associateTicketsToAccounts($tickets);

        if ($ticket = $tickets->filter(fn($t) => $t->id === $id)->first()) {
            return view("pages.tickets.show", [
                'ticket' => $ticket,
                'account' => $ticketAccounts[$id],
            ]);
        }

        return redirect()->action("TicketController@index")
            ->withErrors(utrans("errors.invalidTicketID"));
    }

    /**
     * Show ticket creation page
     * @return \Illuminate\Http\RedirectResponse|View
     */
    public function create()
    {
        if (!get_user()->email_address) {
            return redirect()->action("ProfileController@show")
                ->withErrors(utrans("errors.mustSetEmailAddress"));
        }

        return view('pages.tickets.create', [
            'accounts' => [
                session()->get('account'),
                ...session()->get('child_accounts')->toArray()
            ]
        ]);
    }

    /**
     * Create a new ticket
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(
        TicketRequest $request,
        CreateAccountTicketAction $createAccountTicketAction,
        CreateTicketReplyAction $createTicketReplyAction
    ) {
        try {
            $ticket = $createAccountTicketAction(AccountTicketData::fromRequest($request));

            $ticketReply = $createTicketReplyAction(new TicketReplyData([
                'ticketId' => $ticket->id,
                'body' => $request->input('description'),
                'author' => get_user()->contact_name,
                'authorEmail' => get_user()->email_address,
            ]));

            \array_unshift($ticket->ticketReplies, $ticketReply);
            $tickets = $this->getTickets()->prepend($ticket);
            Cache::tags('tickets')->put(get_user()->account_id, $tickets, self::CACHE_TTL);

            return redirect()->action("TicketController@index")->with('success', utrans("tickets.ticketCreated"));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(utrans("errors.failedToCreateTicket"))->withInput();
        }
    }

    /**
     * Post a reply to a ticket
     * @param TicketReplyRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postReply(
        int $ticketId,
        TicketReplyRequest $request,
        CreateTicketReplyAction $createTicketReplyAction
    ) {
        $tickets = $this->getTickets();

        if (!($ticket = $tickets->filter(fn($t) => $t->id === $ticketId)->first())) {
            return redirect()->action("TicketController@index")
                ->withErrors(utrans("errors.invalidTicketID"));
        }

        try {
            $ticketReply = $createTicketReplyAction(new TicketReplyData([
                'ticketId' => $ticketId,
                'body' => $request->input('body'),
                'author' => get_user()->contact_name,
                'authorEmail' => get_user()->email_address,
            ]));

            // prepend the ticket reply and update cache
            \array_unshift($ticket->ticketReplies, $ticketReply);
            Cache::tags('tickets')->put(get_user()->account_id, $tickets, self::CACHE_TTL);

            return redirect()->back()->with('success', utrans("tickets.replyPosted"));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(utrans("errors.failedToPostReply"));
        }
    }

    /**
     * Get tickets for current user accounts, cache them if currently uncached, otherwise return from cache
     * @return mixed
     */
    private function getTickets()
    {
        return Cache::tags('tickets')->remember(get_user()->account_id, self::CACHE_TTL, function () {
            try {
                return $this->sonarClient
                    ->tickets()
                    ->where('ticketable_id', [
                        session()->get('account')->id,
                        ...session()->get('child_accounts')->map(fn($account) => $account->id)->toArray()
                    ])
                    ->where('ticketable_type', 'Account')
                    ->sortBy('updated_at', 'DESC')
                    ->get();
            } catch (\Exception $e) {
                return collect([]);
            }
        });
    }

    /**
     * @param \App\SonarApi\Resources\Ticket[] $tickets
     * @return array
     */
    private function associateTicketsToAccounts(iterable $tickets): array
    {
        $accounts = collect([
            session()->get('account'),
            ...session()->get('child_accounts')->toArray()
        ])->keyBy(fn($account) => $account->id);

        // associate ticket to account
        $ticketAccounts = [];
        foreach ($tickets as $ticket) {
            $ticketAccounts[$ticket->id] = $accounts[$ticket->ticketableId];
        }

        return $ticketAccounts;
    }
}
