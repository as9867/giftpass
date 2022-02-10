<?php

namespace App\Listeners;
use App\Events\SendMail;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Domains\Auth\Models\User;
use App\Mail\welcomeMail;

use Mail;
class SendMailFired
{
    public function __construct()
    {
        
    }
    public function handle(SendMail $event)
    {


        // echo $event->userId;
        $user = User::where('id',$event->userId)->first();
        // dd($event);
        $users['email'] = $user->email;

         dd($users['email']);
        // $mailData = [
        //             'subject' => 'Listing a card on the marketplace',
        //             'title' => 'Listing a card on the marketplace',
        //             'body' => 'Hello @' . auth()->user()->username . ', you have successfully listed your $100 gift card for sale on the GiftPass Market Place!',
        //         ];

                Mail::to($users['email'])->send(new welcomeMail($event->mailData));
       
    }
}
