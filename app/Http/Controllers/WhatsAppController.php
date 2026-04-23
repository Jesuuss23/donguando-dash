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
        'from_me' => $isFromMe
    ]);

    // ========== CONTADOR IA ==========
    \Log::info('Evaluando condición:', [
        'count_ia' => $request->count_ia,
        'is_intervened' => $contact->is_intervened,
        'ambas_true' => ($request->count_ia && $contact->is_intervened)
    ]);

$iaActiva = ($contact->is_intervened == 0); // 0 = IA activa

if ($request->count_ia && $iaActiva) {
    $contact->incrementIaCount();
    \Log::info('Contador incrementado a: ' . $contact->ia_messages_count);
}

    return response()->json(['status' => 'success']);
}
}