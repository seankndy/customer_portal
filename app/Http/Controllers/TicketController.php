<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketReplyRequest;
use App\Http\Requests\TicketRequest;
use App\SonarApi\Client;
use App\SonarApi\Mutations\CreateTicketReply;
use App\SonarApi\Mutations\Inputs\CreateTicketReplyMutationInput;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use SonarSoftware\CustomerPortalFramework\Controllers\AccountTicketController;
use SonarSoftware\CustomerPortalFramework\Models\Ticket;

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
     * @param TicketRequest $request
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function store(TicketRequest $request)
    {
        try {
            $ticket = new Ticket([
                'account_id' => get_user()->account_id,
                'email_address' => get_user()->email_address,
                'subject' => $request->input('subject'),
                'description' => $request->input('description'),
                'ticket_group_id' => config("customer_portal.ticket_group_id"),
                'priority' => config("customer_portal.ticket_priority"),
                'inbound_email_account_id' => config("customer_portal.inbound_email_account_id"),
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(utrans("errors.failedToCreateTicket"))->withInput();
        }

        $accountTicketController = new AccountTicketController();
        try {
            $accountTicketController->createTicket($ticket, get_user()->contact_name, get_user()->email_address);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return redirect()->back()->withErrors(utrans("errors.failedToCreateTicket"))->withInput();
        }

        $this->clearTicketCache();
        return redirect()->action("TicketController@index")->with('success', utrans("tickets.ticketCreated"));
    }

    /**
     * Post a reply to a ticket
     * @param TicketReplyRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postReply(int $ticketId, TicketReplyRequest $request)
    {
        $tickets = $this->getTickets();

        if (!($ticket = $tickets->filter(fn($t) => $t->id === $ticketId)->first())) {
            return redirect()->action("TicketController@index")
                ->withErrors(utrans("errors.invalidTicketID"));
        }

        try {
            $ticketReply = $this->sonarClient->mutations()->run(
                new CreateTicketReply(
                    new CreateTicketReplyMutationInput([
                        'ticketId' => $ticketId,
                        'body' => $request->input('reply'),
                        'incoming' => true,
                        'author' => get_user()->contact_name,
                        'authorEmail' => get_user()->email_address,
                    ])
                )
            );

            // prepend the ticket reply and update cache
            \array_unshift($ticket->ticketReplies, $ticketReply);
            Cache::tags('tickets')->put(get_user()->account_id, $tickets, self::CACHE_TTL);

            return redirect()->back()->with('success', utrans("tickets.replyPosted"));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(utrans("errors.failedToPostReply"));
        }
    }

    /**
     * Clear the ticket cache
     */
    private function clearTicketCache()
    {
        Cache::tags("tickets")->forget(get_user()->account_id);
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
