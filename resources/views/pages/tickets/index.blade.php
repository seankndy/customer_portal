@extends('layouts.full')
@section('content')
<div class="container-fluid">
<div class="row justify-content-center">
<div class="col-12">
<div class="header mt-md-5">
   <div class="header-body">
      <div class="row align-items-center">
         <div class="col">
            <h6 class="header-pretitle">
             {{utrans("headers.summary")}}
            </h6>
            <h1 class="header-title">
            {{utrans("headers.tickets")}}
            </h1>
         </div>
         <div class="col-auto">
            <!-- Button -->
            <a class="btn btn-primary" href="{{action("TicketController@create")}}" role="button">
            {{utrans("tickets.createNewTicket")}} <span class="fe fe-edit-2"></span> 
            </a>
         </div>
      </div>
      <div class="row align-items-center">
         <div class="col">
            <ul class="nav nav-tabs nav-overflow header-tabs">
               <li class="nav-item @if ($status === 'CLOSED') mr-4 @endif">
                  <a href="{{ url()->current() }}?status=OPEN" class="nav-link @if ($status === 'OPEN') active @endif">
                     Open
                     @if ($status === 'OPEN')
                        <span class="badge badge-pill badge-soft-secondary">{{$tickets->total()}}</span>
                     @endif
                  </a>
               </li>
               <li class="nav-item">
                  <a href="{{ url()->current() }}?status=CLOSED" class="nav-link @if ($status === 'CLOSED') active @endif">
                     Closed
                     @if ($status === 'CLOSED')
                     <span class="badge badge-pill badge-soft-secondary">{{$tickets->total()}}</span>
                     @endif
                  </a>
               </li>
            </ul>
         </div>
      </div>
   </div>
</div>
<div class="row">
   <div class="col-12 col-lg-12 col-md-12 col-sm-12">
      <div class="card">
         <div class="table-responsive">
            <table class="table card-table">
               <thead>
                  <tr>
                     <th>{{utrans("tickets.subject")}}</th>
                     <th>{{utrans("tickets.status")}}</th>
                     <th class="d-none d-md-table-cell">{{utrans("general.date")}}</th>
                     <th class="d-none d-sm-table-cell" width="20"></th>
                  </tr>
               </thead>
               <tbody>
                  @if(count($tickets) == 0)
                  <TR>
                     <TD>{{utrans("tickets.noTickets")}}</TD>
                  </TR>
                  @endif
                  @foreach($tickets as $ticket)
                  <tr>
                     <TD id="ticket-status">
                        <a href="{{action("TicketController@show",['tickets' => $ticket->id])}}">
                           {{$ticket->subject}}
                        </a>
                        @if ($ticketAccounts[$ticket->id]->physicalAddress())
                        <div class="small text-secondary">
                           {{ (string)$ticketAccounts[$ticket->id] }}
                        </div>
                        @endif
                     </TD>
                     @if($ticket->status === 'CLOSED')
                     <TD id="ticket-status">
                        <div class="badge badge-soft-danger">
                           {{utrans("tickets.closed")}}
                        </div>
                     </TD>
                     @else
                     <TD>
                        @if ($ticket->latestReply() && !$ticket->latestReply()->author)
                           <span class="badge badge-info">{{utrans("tickets.waitingYourResponse")}}</span>
                        @else
                           <span class="badge badge-light">{{utrans("tickets.waitingIspResponse", [ 'companyName' => Config::get("customer_portal.company_name")])}}</span>
                        @endif
                     </TD>
                     @endif
                     <TD class="d-none d-md-table-cell">
                        {{ $ticket->updatedAt->diffForHumans() }}
                     </TD>
                     <TD class="d-none d-sm-table-cell" width="20">
                        <span class="badge badge-pill badge-soft-dark">{{count($ticket->ticketReplies)}}</span>
                     </TD>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
      </div>
   </div>

   <div class="col-12">
      <div class="float-right">
         {{ $tickets->links() }}
      </div>
   </div>

   <div class="col-12">
      <!-- Card -->
      <div class="card bg-light border ">
         <div class="card-body">
            <p class="mb-2 text-muted">
               <i class="fe fe-help-circle"></i> {{utrans("tickets.tips")}}
            </p>
            <p class="small text-muted mb-2">
               {{utrans("tickets.whenCreatingTicket")}}
            </p>
            <ul class="small text-muted pl-4 mb-0">
               <li>
                  {{utrans("tickets.beAsDetailed")}}
               </li>
               <li>
                  {{utrans("tickets.includeRelevantInfo")}}
               </li>
               <li>
                  {{utrans("tickets.neverAskForCredit")}}
               </li>
            </ul>
         </div>
      </div>
   </div>
</div>
@endsection