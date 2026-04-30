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
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ExportController;
use App\Models\Category;
use App\Models\QuickCommand;
use App\Models\Promotion;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Ruta principal
Route::get('/dashboard', function () {
    $contacts = Contact::with('messages')->latest()->get();
    return view('chat', compact('contacts'));
})->name('dashboard');

Route::get('/', function () {
    return redirect('/dashboard');
});

// Importar productos
Route::get('/import-products', [ProductImportController::class, 'showForm']);
Route::post('/import-products', [ProductImportController::class, 'import'])->name('import.products');

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

    $updated = DB::table('contacts')->where('id', $contactId)->update([
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

Route::post('/chat/toggle-pin/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    $contact->is_pinned = !$contact->is_pinned;
    $contact->save();
    return response()->json(['is_pinned' => $contact->is_pinned]);
});

Route::get('/chat/ia-stats/{contactId}', function ($contactId) {
    $contact = Contact::findOrFail($contactId);
    return response()->json([
        'count' => $contact->ia_messages_count ?? 0,
        'ia_active' => $contact->is_intervened == 0
    ]);
});

/*
|--------------------------------------------------------------------------
| Rutas de Contactos
|--------------------------------------------------------------------------
*/

Route::get('/contacts/ordered', function () {
    $contacts = Contact::with(['messages' => function($q) {
        $q->latest()->limit(1);
    }])->get();
    
    $contacts = $contacts->sortByDesc(function($contact) {
        $lastMessage = $contact->messages->first();
        return [
            $contact->is_pinned ? 1 : 0,
            $lastMessage ? $lastMessage->created_at : $contact->created_at
        ];
    });
    
    return response()->json($contacts->values());
});

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

Route::get('/contacts/by-tag/{tagId}', function ($tagId) {
    $tag = Tag::with('contacts.messages')->findOrFail($tagId);
    return $tag->contacts()->with('messages')->latest()->get();
});

/*
|--------------------------------------------------------------------------
| Rutas de Etiquetas (Tags)
|--------------------------------------------------------------------------
*/

Route::get('/contacts/{id}/tags', function ($id) {
    $contact = Contact::with('tags')->find($id);
    return response()->json($contact ? $contact->tags : []);
});

Route::post('/contacts/{id}/tags', function (Request $request, $id) {
    $contact = Contact::findOrFail($id);
    
    if ($request->tag_id) {
        $tag = Tag::findOrFail($request->tag_id);
    } else {
        $tag = Tag::firstOrCreate(
            ['name' => strtoupper($request->name)],
            ['color' => '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)]
        );
    }
    
    $contact->tags()->syncWithoutDetaching([$tag->id]);
    return response()->json($tag);
});

Route::delete('/contacts/{contactId}/tags/{tagId}', function ($contactId, $tagId) {
    $contact = Contact::findOrFail($contactId);
    $contact->tags()->detach($tagId);
    return response()->json(['status' => 'success']);
});

// Gestión de tags (admin)
Route::get('/tags/all', function () {
    return Tag::orderBy('name', 'asc')->get();
});

Route::get('/admin/tags', function () {
    return Tag::orderBy('name', 'asc')->get();
});

Route::post('/admin/tags', function (Request $request) {
    $tag = Tag::create([
        'name' => strtoupper(trim($request->name)),
        'color' => $request->color ?? '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT)
    ]);
    return response()->json($tag);
});

Route::put('/admin/tags/{id}', function (Request $request, $id) {
    $tag = Tag::findOrFail($id);
    $tag->update([
        'name' => strtoupper(trim($request->name)),
        'color' => $request->color
    ]);
    return response()->json($tag);
});

Route::delete('/admin/tags/{id}', function ($id) {
    $tag = Tag::findOrFail($id);
    $tag->contacts()->detach();
    $tag->delete();
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
    
    $response = Http::post($n8nUrl, [
        'name' => $request->input('name'),
        'phone' => $request->input('phone'),
        'body' => $request->input('body'),
    ]);
    
    return response()->json([
        'success' => $response->successful(),
        'status' => $response->status(),
        'data' => $response->json()
    ]);
});


// ========== SISTEMA DE COMANDOS (NUEVO, TOTALMENTE INDEPENDIENTE) ==========

// Categorías
Route::get('/cmd/categories', function () {
    return App\Models\Category::with('quickCommands')->orderBy('order')->get();
});

Route::post('/cmd/categories', function (Request $request) {
    $category = App\Models\Category::create([
        'name' => $request->name,
        'icon' => $request->icon ?? '📁',
        'slug' => \Str::slug($request->name),
        'order' => $request->order ?? 0
    ]);
    return response()->json($category);
});

Route::put('/cmd/categories/{id}', function (Request $request, $id) {
    $category = App\Models\Category::findOrFail($id);
    $category->update($request->only(['name', 'icon', 'order']));
    return response()->json($category);
});

Route::delete('/cmd/categories/{id}', function ($id) {
    $category = App\Models\Category::findOrFail($id);
    $category->quickCommands()->delete();
    $category->delete();
    return response()->json(['status' => 'success']);
});

// Comandos (respuestas rápidas del sistema de comandos)
Route::get('/cmd/commands', function () {
    return App\Models\QuickCommand::with('category')->orderBy('order')->get();
});

Route::get('/cmd/commands/{id}', function ($id) {
    return App\Models\QuickCommand::with('category')->findOrFail($id);
});

Route::post('/cmd/commands/save', function (Request $request) {
    $request->validate([
        'title' => 'required',
        'body' => 'required',
    ]);

    $command = App\Models\QuickCommand::updateOrCreate(
        ['id' => $request->id],
        [
            'category_id' => $request->category_id,
            'command' => $request->command,
            'title' => $request->title,
            'body' => $request->body,
            'order' => $request->order ?? 0
        ]
    );

    return response()->json($command);
});

Route::delete('/cmd/commands/delete/{id}', function ($id) {
    $command = App\Models\QuickCommand::findOrFail($id);
    $command->delete();
    return response()->json(['status' => 'success']);
});
Route::get('/cmd/categories', function () {
    $categories = Category::with('quickCommands')->orderBy('order')->get();
    \Log::info('Categorías devueltas:', $categories->toArray());
    return $categories;
});
// Obtener una categoría específica (para el delete)
Route::get('/cmd/categories/{id}', function ($id) {
    return Category::with('quickCommands')->findOrFail($id);
});


// ========== SISTEMA DE PROMOCIONES (PDF e IMÁGENES) ==========

// Obtener todas las promociones
Route::get('/promotions', function () {
    return Promotion::orderBy('order')->get();
});

// Obtener una promoción específica
Route::get('/promotions/{id}', function ($id) {
    return Promotion::findOrFail($id);
});


// Eliminar promoción
Route::delete('/promotions/delete/{id}', function ($id) {
    $promotion = Promotion::findOrFail($id);
    $promotion->delete();
    return response()->json(['status' => 'success']);
});

// ========== ENVÍO DE PROMOCIONES A N8N ==========

Route::post('/api/send-promo', function (Request $request) {
    $phone = $request->input('phone');
    $promotionId = $request->input('promotion_id');
    $caption = $request->input('caption', '');
    
    $promotion = Promotion::with('file')->findOrFail($promotionId);
    $file = $promotion->file;
    
    if (!$file) {
        return response()->json([
            'success' => false,
            'message' => 'Archivo no encontrado'
        ], 404);
    }
    
    // URL pública de tu servidor
    $fileUrl = url('/api/file/' . $file->id);
    $mediaType = str_starts_with($file->mime_type, 'image/') ? 'image' : 'document';
    
    // Enviar a n8n
    $n8nPromoUrl = 'https://malacological-nathalie-unhermitic.ngrok-free.dev/webhook-test/send-promo';
    
    $response = Http::post($n8nPromoUrl, [
        'phone' => $phone,
        'file_url' => $fileUrl,
        'file_name' => $file->original_name,
        'mime_type' => $file->mime_type,
        'media_type' => $mediaType,
        'caption' => $caption ?: $promotion->caption
    ]);
    
    return response()->json([
        'success' => $response->successful(),
        'status' => $response->status(),
        'message' => $response->successful() ? 'Promoción enviada' : 'Error en n8n'
    ]);
});

// ========== GESTIÓN DE ARCHIVOS PARA PROMOCIONES ==========

// Subir archivo
Route::post('/files/upload', function (Request $request) {
    $request->validate([
        'file' => 'required|file|max:95000', // 60MB en KB
        'type' => 'required|in:pdf,image'
    ]);
    
    // Validar tipo MIME según el tipo seleccionado
    $uploadedFile = $request->file('file');
    $mimeType = $uploadedFile->getMimeType();
    
    if ($request->type === 'pdf') {
        if ($mimeType !== 'application/pdf') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se permiten archivos PDF en esta sección.'
            ], 400);
        }
    } else {
        if (!str_starts_with($mimeType, 'image/')) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se permiten imágenes (JPEG, PNG, GIF) en esta sección.'
            ], 400);
        }
    }
    
    $uploadedFile = $request->file('file');
    $originalName = $uploadedFile->getClientOriginalName();
    $mimeType = $uploadedFile->getMimeType();
    $size = $uploadedFile->getSize();
    
    // Generar nombre único
    $extension = $uploadedFile->getClientOriginalExtension();
    $savedAs = uniqid() . '_' . time() . '.' . $extension;
    $directory = $request->type === 'pdf' ? 'promotions/pdf' : 'promotions/images';
    
    // Guardar archivo
    $path = $uploadedFile->storeAs($directory, $savedAs, 'public');
    
    // Guardar en BD
    $file = File::create([
        'original_name' => $originalName,
        'saved_as' => $path,
        'mime_type' => $mimeType,
        'size' => $size
    ]);
    
    return response()->json($file);
});

// Listar archivos (para el selector)
Route::get('/files/list', function () {
    return File::orderBy('created_at', 'desc')->get();
});


// Actualizar promoción (ahora usa file_id)
Route::post('/promotions/save', function (Request $request) {
    try {
        \Log::info('=== INICIO GUARDAR PROMOCIÓN ===');
        \Log::info('Datos recibidos:', $request->all());
        
        // Verificar que el archivo existe
        if ($request->file_id) {
            $fileExists = File::find($request->file_id);
            \Log::info('Verificando file_id:', [
                'file_id' => $request->file_id,
                'exists' => $fileExists ? 'SÍ' : 'NO'
            ]);
            
            if (!$fileExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo seleccionado no existe en la base de datos'
                ], 400);
            }
        }
        
        $validator = validator($request->all(), [
            'type' => 'required|in:pdf,image',
            'command' => 'required',
            'title' => 'required',
            'file_id' => 'required|exists:files,id'
        ]);
        
        if ($validator->fails()) {
            \Log::error('Validación falló:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = [
            'type' => $request->type,
            'command' => $request->command,
            'title' => $request->title,
            'caption' => $request->caption,
            'file_id' => $request->file_id,
            'order' => $request->order ?? 0
        ];
        
        \Log::info('Datos a guardar:', $data);
        
        $promotion = Promotion::updateOrCreate(
            ['id' => $request->id],
            $data
        );
        
        \Log::info('Promoción guardada con ID: ' . $promotion->id);
        \Log::info('=== FIN GUARDAR PROMOCIÓN ===');
        
        return response()->json(['success' => true, 'promotion' => $promotion]);
        
    } catch (\Exception $e) {
        \Log::error('ERROR EN PROMOTIONS/SAVE:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});
Route::delete('/files/delete/{id}', function ($id) {
    try {
        \Log::info('=== ELIMINAR ARCHIVO ===', ['file_id' => $id]);
        
        $file = File::findOrFail($id);
        
        // Verificar si está siendo usado
        $usageCount = $file->promotions()->count();
        if ($usageCount > 0) {
            \Log::warning('Archivo en uso, no se puede eliminar', [
                'file_id' => $id,
                'uso_en_promociones' => $usageCount
            ]);
            
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar. El archivo está siendo usado por {$usageCount} promoción(es)."
            ], 400);
        }
        
        // Eliminar archivo físico
        $fullPath = Storage::disk('public')->path($file->saved_as);
        if (file_exists($fullPath)) {
            unlink($fullPath);
            \Log::info('Archivo físico eliminado: ' . $fullPath);
        }
        
        // Eliminar registro
        $file->delete();
        
        \Log::info('Archivo eliminado correctamente');
        
        return response()->json(['success' => true]);
        
    } catch (\Exception $e) {
        \Log::error('ERROR AL ELIMINAR ARCHIVO:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
});
// ========== API PARA N8N (OBTENER ARCHIVO POR ID) ==========
Route::get('/api/file/{id}', function ($id) {
    $file = File::findOrFail($id);
    $fullPath = Storage::disk('public')->path($file->saved_as);
    
    if (!file_exists($fullPath)) {
        return response()->json(['error' => 'File not found'], 404);
    }
    
    return response()->file($fullPath, [
        'Content-Type' => $file->mime_type,
        'Content-Disposition' => 'inline; filename="' . $file->original_name . '"'
    ]);
});
Route::put('/files/rename/{id}', function ($id, Request $request) {
    $file = File::findOrFail($id);
    
    // Obtener extensión original
    $extension = pathinfo($file->saved_as, PATHINFO_EXTENSION);
    $newSavedAs = 'promotions/' . ($file->mime_type === 'application/pdf' ? 'pdf' : 'images') . '/' . \Str::slug($request->name) . '_' . time() . '.' . $extension;
    
    // Renombrar archivo físico
    Storage::disk('public')->move($file->saved_as, $newSavedAs);
    
    // Actualizar base de datos
    $file->original_name = $request->name;
    $file->saved_as = $newSavedAs;
    $file->save();
    
    return response()->json(['success' => true]);
});
// Exportar contactos a Excel
Route::get('/export/contacts', [ExportController::class, 'exportContacts'])->name('export.contacts');
Route::get('/export/contacts/filtered', [ExportController::class, 'exportFiltered'])->name('export.filtered');