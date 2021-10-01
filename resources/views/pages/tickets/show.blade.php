@extends('layouts.full')
@section('content')
    <div class="container-fluid">
        <div class="header mt-md-5">
            <div class="header-body">
                <div class="row align-items-center">
                    <div class="col">
                        <h6 class="header-pretitle">
                            <a href="{{route('tickets.index')}}">{{utrans("headers.tickets")}}</a>
                        </h6>
                        <h1 class="header-title">
                            {{ $ticket->subject }}
                        </h1>
                        <h5 class="header-subtitle">
                            {{ (string)$account }}
                        </h5>
                    </div>
                    <div class="col-auto text-right">
                        <!-- Button -->
                        @if ($ticket->status !== 'CLOSED')
                        <form action="{{ route('ticket.close', $ticket->id) }}" method="post">
                            @csrf
                            <button class="btn" role="button" type="submit">
                                {{utrans("tickets.closeTicket")}} <span class="fe fe-check"></span>
                            </button>
                        </form>
                        @else
                        <div class="d-inline-flex align-items-center">
                            <span class="fe fe-info mr-1"></span>
                            Ticket closed on {{ $ticket->closedAt->format('M jS, Y') }}.
                        </div>
                        <br>
                        <form action="{{ route('ticket.reopen', $ticket->id) }}" id="reopen" method="post">
                            @csrf
                            <button class="btn" type="submit">
                                {{utrans("tickets.reopenTicket")}}
                                <span class="fe fe-repeat"></span>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col ml-n2">
                            @if(count($ticket->ticketReplies) > 0)
                                @foreach($ticket->ticketReplies as $reply)
                                    <div class="row">
                                        <div class="col ml-n2">
                                            @if($reply->incoming == true)
                                                <div class="comment-right mb-4">
                                                    <div class="comment-body-sent">
                                                        <div class="row">
                                                            <div class="col">
                                                                <h5 class="comment-title">
                                                                    {{utrans("tickets.youWrote")}}
                                                                </h5>
                                                            </div>
                                                            <div class="col-auto">
                                                                <time class="comment-time-light">
                                                                    {{$reply->createdAt->diffForHumans()}} <i
                                                                            class="fe fe-clock ml-1"></i>
                                                                </time>
                                                            </div>
                                                        </div>
                                                        @else
                                                            <div class="comment-left mb-4">
                                                                <div class="comment-body">
                                                                    <div class="row">
                                                                        <div class="col">
                                                                            <h5 class="comment-title">
                                                                                {{utrans("tickets.ispWrote",['companyName' => Config::get("customer_portal.company_name")])}}
                                                                            </h5>
                                                                        </div>
                                                                        <div class="col-auto">
                                                                            <time class="comment-time-dark">
                                                                                {{$reply->createdAt->diffForHumans()}}
                                                                                <i class="fe fe-clock ml-1"></i>
                                                                            </time>
                                                                        </div>
                                                                    </div>
                                                                    @endif
                                                                    <div class="comment-text">
                                                                        {!! $reply->body !!}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>

                            {!! Form::open(['route' => ['ticket.post-reply', 'ticketId' => $ticket->id], 'id' => 'replyForm', 'method' => 'post']) !!}
                            <div class="form-group">
                                {!! Form::textarea("reply",null,['class' => 'form-control', 'rows' => 5, 'id' => 'reply', 'placeholder' => utrans("tickets.postAReplyPlaceholder")]) !!}
                            </div>
                            <button type="submit" class="btn btn-outline-primary">{{utrans("actions.postReply")}}</button>
                            {!! Form::close() !!}
                        </div>
                    </div>
                </div>

            </div>
@endsection
@section('additionalCSS')
@endsection
@section('additionalJS')
<script type="text/javascript" src="/assets/libs/js-validation/jsvalidation.min.js"></script>
{!! JsValidator::formRequest('App\Http\Requests\TicketReplyRequest','#replyForm') !!}
@endsection
