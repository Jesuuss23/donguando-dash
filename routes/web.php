<?php

use Illuminate\Support\Facades\Route;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\Product;
use App\Models\QuickResponse;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Ruta principal
Route::get('/dashboard', function () {
    $contacts = Contact::with('messages')->latest()->get();
    return view('chat', compact('contacts'));
});

Route::get('/', function () {
    return redirect('/dashboard');
});

/*
|--------------------------------------------------------------------------
| Rutas de Chat
|--------------------------------------------------------------------------
*/

Route::get('/chat/messages/{contactId}', function ($contactId) {
    return Message::where('contact_id', $contactId)->orderBy('created_at', 'asc')->get();
});

Route::post('/chat/intervene/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    $contact->is_intervened = !$contact->is_intervened;
    $contact->save();
    return response()->json(['is_intervened' => $contact->is_intervened]);
});

Route::get('/check-status-by-id/{id}', function ($id) {
    $contact = Contact::find($id);
    return response()->json(['is_intervened' => $contact ? (bool)$contact->is_intervened : false]);
});

Route::get('/contact-info/{id}', function ($id) {
    return Contact::find($id);
});

Route::delete('/chat/clear/{contactId}', function ($contactId) {
    Message::where('contact_id', $contactId)->delete();
    return response()->json(['status' => 'success']);
});

Route::delete('/chat/delete-contact/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    $contact->messages()->delete();
    $contact->delete();
    return response()->json(['status' => 'success']);
});

Route::post('/chat/clear-order/{contactId}', function ($contactId) {
    if (!is_numeric($contactId)) {
        return response()->json(['error' => 'ID no válido'], 400);
    }

    $updated = DB::table('contacts')
        ->where('id', $contactId)
        ->update([
            'producto' => null,
            'cantidad' => null,
            'direccion' => null,
            'updated_at' => now()
        ]);

    if ($updated) {
        return response()->json(['status' => 'success', 'message' => 'Ficha borrada']);
    }
    return response()->json(['status' => 'error', 'message' => 'No se encontró el contacto'], 404);
});

Route::post('/chat/mark-as-read/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    $contact->unread_count = 0;
    $contact->save();
    return response()->json(['status' => 'success']);
});

/*
|--------------------------------------------------------------------------
| Rutas de Etiquetas
|--------------------------------------------------------------------------
*/

Route::get('/contacts/{id}/tags', function ($id) {
    $contact = Contact::with('tags')->find($id);
    return response()->json($contact ? $contact->tags : []);
});

Route::post('/contacts/{id}/tags', function (Request $request, $id) {
    $contact = Contact::findOrFail($id);
    
    $tag = Tag::firstOrCreate(
        ['name' => strtoupper($request->name)],
        ['color' => '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)]
    );

    $contact->tags()->syncWithoutDetaching([$tag->id]);
    return response()->json($tag);
});

Route::delete('/contacts/{contactId}/tags/{tagId}', function ($contactId, $tagId) {
    $contact = Contact::findOrFail($contactId);
    $contact->tags()->detach($tagId);
    return response()->json(['status' => 'success']);
});

/*
|--------------------------------------------------------------------------
| Rutas de Inventario
|--------------------------------------------------------------------------
*/

Route::get('/inventory/products', function (Request $request) {
    $search = $request->query('search');
    
    if ($search) {
        return Product::where('name', 'LIKE', "%{$search}%")->orderBy('name', 'asc')->get();
    }
    return Product::orderBy('name', 'asc')->get();
});

Route::get('/inventory/product/{id}', function ($id) {
    return Product::findOrFail($id);
});

Route::post('/inventory/save', function (Request $request) {
    $request->validate([
        'name' => 'required',
        'price' => 'required|numeric',
        'stock' => 'required|integer',
        'unit' => 'nullable'
    ]);

    $product = Product::create($request->all());
    return response()->json($product);
});

Route::post('/inventory/update/{id}', function (Request $request, $id) {
    $product = Product::findOrFail($id);
    $product->update($request->all());
    return response()->json($product);
});

Route::delete('/inventory/delete/{id}', function ($id) {
    $product = Product::findOrFail($id);
    $product->delete();
    return response()->json(['status' => 'deleted']);
});

/*
|--------------------------------------------------------------------------
| Rutas de Respuestas Rápidas
|--------------------------------------------------------------------------
*/

Route::get('/quick-responses', function () {
    return QuickResponse::all();
});

Route::post('/quick-responses/save', function (Request $request) {
    $request->validate([
        'title' => 'required',
        'body' => 'required',
    ]);

    $response = QuickResponse::updateOrCreate(
        ['id' => $request->id],
        ['title' => $request->title, 'body' => $request->body]
    );

    return response()->json($response);
});

Route::delete('/quick-responses/delete/{id}', function ($id) {
    $template = QuickResponse::findOrFail($id);
    $template->delete();
    return response()->json(['status' => 'deleted']);
});

/*
|--------------------------------------------------------------------------
| Proxy para n8n (evita CORS)
|--------------------------------------------------------------------------
*/

Route::post('/api/sync-n8n', function (Request $request) {
    $n8nUrl = 'https://malacological-nathalie-unhermitic.ngrok-free.dev/webhook-test/sync-contact-whatsapp';
    
    $data = [
        'name' => $request->input('name'),
        'phone' => $request->input('phone'),
        'body' => $request->input('body'),
    ];
    
    $response = Http::post($n8nUrl, $data);
    
    return response()->json([
        'success' => $response->successful(),
        'status' => $response->status(),
        'data' => $response->json()
    ]);
});

// Buscar contactos por nombre o número
Route::get('/contacts/search', function (Request $request) {
    $query = $request->get('q');
    
    if (empty($query)) {
        return Contact::with('messages')->latest()->get();
    }
    
    return Contact::where('name', 'LIKE', "%{$query}%")
        ->orWhere('whatsapp_id', 'LIKE', "%{$query}%")
        ->with('messages')
        ->latest()
        ->get();
});

// Obtener todas las etiquetas (para los filtros)
Route::get('/tags/all', function () {
    return Tag::orderBy('name', 'asc')->get();
});
// Filtrar contactos por etiqueta
Route::get('/contacts/by-tag/{tagId}', function ($tagId) {
    $tag = Tag::with('contacts.messages')->findOrFail($tagId);
    return $tag->contacts()->with('messages')->latest()->get();
});

// En web.php, agrega al final
Route::get('/chat/ia-stats/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    return response()->json([
        'count' => $contact->ia_messages_count ?? 0,
        'ia_active' => $contact->is_intervened
    ]);
});

Route::get('/contacts/ordered', function () {
    $contacts = Contact::with(['messages' => function($q) {
        $q->latest()->limit(1);
    }])->get();
    
    // Ordenar: anclados primero, luego por último mensaje
    $contacts = $contacts->sortByDesc(function($contact) {
        $lastMessage = $contact->messages->first();
        return [
            $contact->is_pinned ? 1 : 0,  // Anclados primero
            $lastMessage ? $lastMessage->created_at : $contact->created_at
        ];
    });
    
    return response()->json($contacts->values());
});

// Anclar/Desanclar chat
Route::post('/chat/toggle-pin/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    $contact->is_pinned = !$contact->is_pinned;
    $contact->save();
    return response()->json(['is_pinned' => $contact->is_pinned]);
});