<?php
use Illuminate\Support\Facades\Route;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\Product;
use App\Models\QuickResponse;
use Illuminate\Support\Facades\DB; 
Route::get('/dashboard', function () {
    $contacts = Contact::with('messages')->latest()->get();
    return view('chat', compact('contacts'));
});

Route::get('/chat/messages/{contactId}', function ($contactId) {
    return \App\Models\Message::where('contact_id', $contactId)->orderBy('created_at', 'asc')->get();
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
    \App\Models\Message::where('contact_id', $contactId)->delete();
    return response()->json(['status' => 'success']);
});

Route::delete('/chat/delete-contact/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    $contact->messages()->delete();
    $contact->delete();
    return response()->json(['status' => 'success']);
});



Route::post('/chat/clear-order/{contactId}', function ($contactId) {
    // 1. Verificamos que el ID sea un número
    if (!is_numeric($contactId)) {
        return response()->json(['error' => 'ID no válido'], 400);
    }

    // 2. Usamos DB directamente para asegurar el borrado
    $updated = DB::table('contacts')
        ->where('id', $contactId)
        ->update([
            'producto'  => null,
            'cantidad'  => null,
            'direccion' => null,
            'updated_at' => now() // Esto ayuda a verificar cuándo cambió
        ]);

    if ($updated) {
        return response()->json(['status' => 'success', 'message' => 'Ficha borrada']);
    } else {
        return response()->json(['status' => 'error', 'message' => 'No se encontró el contacto o ya estaba vacío'], 404);
    }
});

Route::get('/contacts/{id}/tags', function ($id) {
    $contact = Contact::with('tags')->find($id);
    return response()->json($contact ? $contact->tags : []);
});

// Ruta para guardar etiqueta
Route::post('/contacts/{id}/tags', function (Request $request, $id) {
    $contact = Contact::findOrFail($id);
    
    // Crear o buscar la etiqueta
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

// Obtener todos los productos para el dashboard
Route::get('/inventory/products', function () {
    return Product::orderBy('name', 'asc')->get();
});

// Ruta para actualizar stock o precio rápido (la usaremos luego)
Route::post('/inventory/update/{id}', function (Request $request, $id) {
    $product = Product::findOrFail($id);
    $product->update($request->only(['price', 'stock']));
    return response()->json(['status' => 'updated']);
});

Route::post('/inventory/save', function (Request $request) {
    $request->validate([
        'name' => 'required',
        'price' => 'required|numeric',
        'stock' => 'required|integer',
    ]);

    $product = \App\Models\Product::create($request->all());
    
    return response()->json($product);
});

// Traer datos de un solo producto
Route::get('/inventory/product/{id}', function ($id) {
    return \App\Models\Product::findOrFail($id);
});

// Actualizar producto existente
Route::post('/inventory/update/{id}', function (Request $request, $id) {
    $product = \App\Models\Product::findOrFail($id);
    $product->update($request->all());
    return response()->json($product);
});

Route::delete('/inventory/delete/{id}', function ($id) {
    $product = \App\Models\Product::findOrFail($id);
    $product->delete();
    
    return response()->json(['status' => 'deleted']);
});

Route::get('/quick-responses', function () {
    return QuickResponse::all();
});

// 2. Ruta para GUARDAR o ACTUALIZAR (POST)
// IMPORTANTE: Esta es la que te está dando el error 404
Route::post('/quick-responses/save', function (Request $request) {
    // Validamos que lleguen los datos
    $data = $request->validate([
        'title' => 'required',
        'body'  => 'required',
    ]);

    $response = QuickResponse::updateOrCreate(
        ['id' => $request->id], // Si viene ID, actualiza; si no, crea uno nuevo
        [
            'title' => $request->title,
            'body'  => $request->body
        ]
    );

    return response()->json($response);
});
Route::get('/inventory/products', function (Illuminate\Http\Request $request) {
    $search = $request->query('search');

    if ($search) {
        // Buscamos productos que coincidan con el nombre
        return \App\Models\Product::where('name', 'LIKE', "%{$search}%")
            ->orderBy('name', 'asc')
            ->get();
    }

    // Si no hay búsqueda, devolvemos todo (o los primeros 10)
    return \App\Models\Product::orderBy('name', 'asc')->get();
});