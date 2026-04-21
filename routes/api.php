<?php
use Illuminate\Support\Facades\Route;
use App\Models\Contact;

Route::post('/webhook', [App\Http\Controllers\WhatsAppController::class, 'receive']);

Route::post('/sync-intervention', function () {
    $contact = Contact::where('whatsapp_id', request('whatsapp_id'))->first();
    if ($contact) {
        $status = request('status');
        $contact->is_intervened = ($status === true || $status === 'true' || $status === 1 || $status === '1');
        $contact->save();
        return response()->json(['status' => 'updated', 'new_state' => $contact->is_intervened]);
    }
    return response()->json(['status' => 'not_found'], 404);
});

Route::post('/sync-order-data', function () {
    $contact = Contact::where('whatsapp_id', request('whatsapp_id'))->first();
    if ($contact) {
        $contact->update([
            'producto'  => request('producto'),
            'cantidad'  => request('cantidad'),
            'direccion' => request('direccion')
        ]);
        return response()->json(['status' => 'success']);
    }
    return response()->json(['status' => 'not_found'], 404);
});


Route::get('/check-status/{whatsappId}', function ($whatsappId) {
    $contact = \App\Models\Contact::where('whatsapp_id', $whatsappId)->first();
    
    return response()->json([
        'is_intervened' => $contact ? (bool)$contact->is_intervened : false
    ]);
});

Route::get('/contacts/{id}/tags', function ($id) {
    return Contact::findOrFail($id)->tags;
});

// Asignar una etiqueta a un contacto
Route::post('/contacts/{id}/tags', function (Request $request, $id) {
    $contact = Contact::findOrFail($id);
    
    // Buscamos si la etiqueta ya existe por nombre, si no, la creamos
    $tag = Tag::firstOrCreate(
        ['name' => strtoupper($request->name)],
        ['color' => '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)] // Color aleatorio
    );

    // La asociamos al contacto (sin repetir)
    $contact->tags()->syncWithoutDetaching([$tag->id]);

    return response()->json($tag);
});