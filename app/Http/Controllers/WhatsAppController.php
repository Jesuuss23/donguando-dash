<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Message;

class WhatsAppController extends Controller
{
public function receive(Request $request)
{
    $type = $request->input('type');
    $from = $request->input('from');
    $fromName = $request->input('from_name', 'Cliente');
    $fromMe = filter_var($request->input('from_me', false), FILTER_VALIDATE_BOOLEAN);
    
    $contact = Contact::firstOrCreate(
        ['whatsapp_id' => $from],
        ['name' => $fromName]
    );
    
    $messagePayload = [
        'from_me' => $fromMe,
        'type' => $type,
        'body' => ''
    ];
    
    if ($type === 'text') {
        $messagePayload['body'] = $request->input('body', '');
    }
    elseif ($type === 'image') {
        $messagePayload['image_preview'] = $request->input('preview');
        $messagePayload['image_size'] = $request->input('file_size');
        $messagePayload['file_name'] = 'imagen_' . time() . '.jpg';
        $messagePayload['body'] = $request->input('caption', ' ');
    }
    elseif ($type === 'link_preview') {
        $messagePayload['link_url'] = $request->input('url');
        $messagePayload['link_title'] = $request->input('title');
        $messagePayload['body'] = $request->input('body', '');
    }
    
    $contact->messages()->create($messagePayload);
    
    if ($request->count_ia && $contact->is_intervened == 0) {
        $contact->incrementIaCount();
    }
    
    return response()->json(['status' => 'success']);
}
}