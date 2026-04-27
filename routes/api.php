<?php

use Illuminate\Support\Facades\Route;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Tag;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estas rutas son para servicios externos (n8n, webhooks)
| Prefijo: /api/
*/

// Webhook principal de WhatsApp (n8n envía aquí)
Route::post('/webhook', [App\Http\Controllers\WhatsAppController::class, 'receive']);

// Sincronizar estado de intervención desde n8n
Route::post('/sync-intervention', function (Request $request) {
    $contact = Contact::where('whatsapp_id', $request->whatsapp_id)->first();
    if ($contact) {
        $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
        $contact->is_intervened = $status;
        $contact->save();
        return response()->json(['status' => 'updated', 'new_state' => $contact->is_intervened]);
    }
    return response()->json(['status' => 'not_found'], 404);
});

// Sincronizar datos de pedido desde n8n
Route::post('/sync-order-data', function (Request $request) {
    $contact = Contact::where('whatsapp_id', $request->whatsapp_id)->first();
    if ($contact) {
        $contact->update([
            'producto'  => $request->producto,
            'cantidad'  => $request->cantidad,
            'direccion' => $request->direccion
        ]);
        return response()->json(['status' => 'success']);
    }
    return response()->json(['status' => 'not_found'], 404);
});

// Verificar estado de intervención (para n8n)
Route::get('/check-status/{whatsappId}', function ($whatsappId) {
    $contact = Contact::where('whatsapp_id', $whatsappId)->first();
    return response()->json([
        'is_intervened' => $contact ? (bool)$contact->is_intervened : false
    ]);
});

// API para que la IA obtenga el inventario
Route::get('/inventory-for-ia', function () {
    $products = \App\Models\Product::all();
    
    $inventario_texto = "";
    foreach ($products as $product) {
        $inventario_texto .= "- {$product->name}: S/ {$product->price} | ";
        $inventario_texto .= "Stock: {$product->stock} {$product->unit} | ";
        $inventario_texto .= "Ideal para: {$product->beneficio} | ";
        $inventario_texto .= "Tip: {$product->psicologia_venta}\n";
    }
    
    return response()->json([
        'inventario' => $inventario_texto,
        'productos' => $products
    ]);
});

// API para que la IA registre pedidos
Route::post('/order-from-ia', function (Request $request) {
    $contact = Contact::firstOrCreate(
        ['whatsapp_id' => $request->whatsapp_id],
        ['name' => $request->cliente ?? 'Cliente IA']
    );
    
    $contact->update([
        'producto' => $request->producto,
        'cantidad' => $request->cantidad,
        'direccion' => $request->direccion
    ]);
    
    if ($request->count_ia) {
        $contact->incrementIaCount();
    }
    
    return response()->json(['status' => 'success']);
});