<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Message;

class WhatsAppController extends Controller
{
    public function receive(Request $request)
{
    $contact = Contact::firstOrCreate(
        ['whatsapp_id' => $request->from],
        ['name' => $request->name ?? 'Cliente Nuevo']
    );

    $isFromMe = filter_var($request->from_me, FILTER_VALIDATE_BOOLEAN);

    $contact->messages()->create([
        'body' => $request->body,
        'from_me' => filter_var($request->from_me, FILTER_VALIDATE_BOOLEAN)
    ]);

    return response()->json(['status' => 'success']);
}

}