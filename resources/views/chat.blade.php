<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Don Guando Dash</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-100 h-screen flex overflow-hidden">

    <!-- PRIMERA COLUMNA: Lista de Contactos -->
    <div class="w-1/4 bg-white border-r flex flex-col shadow-lg z-10">
        <div class="p-4 bg-red-700 text-white shadow-md">
            <h2 class="font-bold text-xl uppercase tracking-wider text-center">Don Guando</h2>
        </div>

        <div class="p-4 border-b flex justify-around bg-gray-50">
            <button onclick="showSection('chats')" class="text-blue-600 font-bold flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                Chats
            </button>
            <button onclick="openInventory()" class="text-gray-500 hover:text-blue-600 font-bold flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                Inventario
            </button>
        </div>
        <!-- Buscador de contactos -->
<div class="p-3 border-b bg-white">
    <input type="text" id="search-contacts" 
           placeholder="🔍 Buscar por nombre o número..." 
           class="w-full text-sm border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-red-500">
</div>
<!-- Botón de exportación en el menú de 3 puntitos -->
<button onclick="exportContacts()" class="flex items-center space-x-2 w-full text-left px-4 py-3 text-sm hover:bg-gray-100 transition-colors">
    <span>📊</span> <span>Exportar contactos</span>
</button>
<!-- Filtros de etiquetas -->
<div id="tag-filters-container" class="p-3 border-b bg-gray-50 hidden">
    <p class="text-[10px] font-bold text-gray-500 mb-2 uppercase tracking-wider">📌 FILTRAR POR ETIQUETA</p>
    <div id="tag-filters-list" class="flex flex-wrap gap-1">
        <button onclick="filterByTag('')" class="text-[9px] px-2 py-1 rounded-full bg-gray-200 hover:bg-gray-300 transition-all">
            Todos
        </button>
    </div>
</div>
        <div id="contact-list" class="flex-1 overflow-y-auto">
            @foreach($contacts as $contact)
                <div onclick="loadChat({{ $contact->id }}, '{{ $contact->name }}', {{ $contact->is_intervened ? 'true' : 'false' }})"
                     class="p-4 border-b hover:bg-gray-50 cursor-pointer transition flex justify-between items-center contact-card">
                    <div>
                        <p class="font-bold text-gray-800">{{ $contact->name }}</p>
                                    @if($contact->is_pinned)
                <span class="text-xs text-blue-500" title="Chat anclado">📌</span>
            @endif
                        <p class="text-xs text-gray-500">{{ $contact->whatsapp_id }}</p>
                        <!-- Después del número de teléfono -->
<div class="text-[9px] text-green-600 mt-1">
    🤖 IA: {{ $contact->ia_messages_count ?? 0 }}
</div>
                    </div>
                    
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $contact->is_intervened ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                        {{ $contact->is_intervened ? 'MANUAL' : 'AUTO' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

<!-- SEGUNDA COLUMNA: Chat Principal -->
<div class="w-2/4 flex flex-col bg-white border-r">
    <div id="chat-header" class="p-4 bg-gray-50 flex justify-between items-center border-b shadow-sm relative">
        <div class="flex items-center space-x-2">
            <span id="contact-name-header" class="font-bold text-gray-700 text-lg">Selecciona un cliente</span>
            <div id="contact-tags-container" class="flex gap-1"></div>
            <button id="btn-add-tag" onclick="showTagModal()" class="hidden text-gray-400 hover:text-green-500">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M12 4v16m8-8H4" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="flex items-center space-x-4">
            <button id="btn-show-order" onclick="toggleOrderPanel()" class="hidden p-2 text-yellow-600 hover:bg-yellow-100 rounded-full transition-colors">📋</button>
            <button id="btn-intervene" onclick="toggleIntervention()" class="hidden px-4 py-1 rounded-full text-[10px] font-black shadow-sm transition-all duration-300">IA ON</button>

            <div class="relative inline-block text-left">
                <button onclick="toggleMenu()" id="btn-menu" class="hidden p-2 text-gray-500 hover:bg-gray-200 rounded-full transition-colors">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                </button>
                <div id="dropdown-menu" class="hidden absolute right-0 mt-2 w-48 bg-white border rounded-lg shadow-xl z-50 overflow-hidden">
                    <div class="py-1 text-gray-700">
                        <button onclick="togglePinChatFromMenu()" id="btn-pin-chat" class="flex items-center space-x-2 w-full text-left px-4 py-3 text-sm hover:bg-gray-100 transition-colors">
                            <span>📌</span> <span id="pin-chat-text">Anclar chat</span>
                        </button>
                        <div class="border-t"></div>
                        <button onclick="clearChat()" class="flex items-center space-x-2 w-full text-left px-4 py-3 text-sm hover:bg-gray-100 transition-colors italic">
                            <span>🧹</span> <span>Vaciar chat</span>
                        </button>
                        <button onclick="deleteContact()" class="flex items-center space-x-2 w-full text-left px-4 py-3 text-sm text-red-600 hover:bg-red-50 font-bold border-t transition-colors">
                            <span>🗑️</span> <span>Eliminar contacto</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="chat-messages" class="flex-1 p-6 overflow-y-auto flex flex-col space-y-4 shadow-inner">
        <div class="flex flex-col items-center justify-center h-full opacity-30">
            <p class="text-lg font-bold">Carnicería Don Guando</p>
            <p class="text-sm">Selecciona un chat para ver los mensajes</p>
        </div>
    </div>

    <!-- Área de entrada de mensajes con sistema de comandos -->
<div class="border-t bg-white">
    <div class="flex items-center gap-2 px-3 py-2">
        <div id="message-input-container" class="flex-1 relative">
            <input type="text" id="message-input" placeholder="Escribe un mensaje... Escribe / para ver comandos" 
                   class="w-full border-0 focus:ring-0 text-sm py-2 outline-none">
            
            <!-- Panel de respuestas rápidas (aparece al escribir /) -->
            <div id="quick-replies-panel" class="hidden">
                <div class="sticky top-0 bg-gradient-to-r from-blue-500 to-purple-500 text-white p-2 rounded-t-lg">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">⚡</span>
                        <span class="text-xs font-bold uppercase">Respuestas Rápidas</span>
                        <span class="text-[9px] ml-auto">Escribe / + comando</span>
                    </div>
                </div>
                <div class="p-2 border-b bg-gray-50">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">📱 Comandos</span>
                    </div>
                    <div id="quick-commands-list" class="flex flex-wrap gap-2"></div>
                </div>
                <div id="quick-replies-categories" class="divide-y max-h-48 overflow-y-auto"></div>
                <div class="sticky bottom-0 p-2 border-t bg-gray-50 text-center">
                    <button onclick="openCmdConfigAndClose()" class="text-[10px] text-blue-500 hover:underline font-bold">⚙️ Administrar respuestas rápidas</button>
                </div>
            </div>
            
            <!-- Sugerencias de comandos (aparece al escribir /palabra) -->
            <div id="command-suggestions" class="hidden"></div>
        </div>
        
        <!-- Botón rayo para abrir configuración -->
        <button onclick="openCmdConfig()" id="btn-cmd-config" class="hidden p-2 text-yellow-500 hover:bg-yellow-100 rounded-full transition-colors" title="Configurar comandos rápidos">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        
        <button onclick="sendMessage()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-1 rounded-full text-sm font-bold transition-colors">
            Enviar
        </button>
    </div>
</div>
    
    <!-- Panel de respuestas rápidas (oculto por defecto) -->
    <div id="quick-replies-panel" class="hidden border-t bg-gray-50 max-h-64 overflow-y-auto">
        <!-- Comandos rápidos -->
        <div class="p-2 border-b bg-white">
            <div class="flex items-center gap-2 mb-2">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">📱 Comandos rápidos</span>
                <span class="text-[8px] text-gray-300">Escribe / en el chat</span>
            </div>
            <div id="quick-commands-list" class="flex flex-wrap gap-2">
                <span class="text-[9px] text-gray-400">Cargando comandos...</span>
            </div>
        </div>
        
        <!-- Respuestas rápidas por categoría -->
        <div id="quick-replies-categories" class="divide-y">
            <div class="text-center text-gray-400 text-xs py-4">Cargando respuestas rápidas...</div>
        </div>
    </div>
    
    <!-- Sugerencias de comandos (aparece al escribir /) -->
    <div id="command-suggestions" class="hidden absolute bottom-full left-0 right-0 bg-white border rounded-t-lg shadow-lg max-h-48 overflow-y-auto z-50"></div>
</div>
   </div> 

    <!-- TERCERA COLUMNA: Panel de Ventas -->
    <div class="w-1/4 flex flex-col bg-white p-4">
        <h3 class="text-sm font-black text-gray-400 uppercase mb-4 tracking-widest">⚡ Panel de Ventas</h3>
        
        <div class="mb-6">
            <input type="text" id="chat-product-search" placeholder="Buscar carne..." 
                   class="w-full text-xs border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-red-500">
            
            <div id="chat-product-results" class="mt-2 space-y-2 max-h-48 overflow-y-auto">
                <!-- Resultados de búsqueda se insertan aquí -->
            </div>
        </div>

        <hr class="mb-4">

        <div id="quick-messages-area" class="hidden">
            <h4 class="text-[10px] font-bold text-gray-400 mb-2 uppercase">Mensajes para: <span id="selected-product-name" class="text-red-600"></span></h4>
            <div id="quick-messages-list" class="space-y-2">
                <!-- Mensajes rápidos se insertan aquí -->
            </div>
            
            <button onclick="openConfigQuickMessages()" class="mt-4 text-[10px] text-blue-500 hover:underline w-full text-center font-bold">
                ⚙️ Editar Mensajes Rápidos
            </button>
        </div>
    </div>

    <!-- Panel de Pedido (flotante) -->
    <div id="order-panel" class="hidden absolute right-0 top-0 h-full w-72 bg-yellow-50 border-l p-4 shadow-xl z-40">
        <div class="flex justify-between items-center border-b border-yellow-200 pb-3 mb-4">
            <h3 class="font-bold text-red-800 text-xs tracking-widest uppercase">📋 Ficha de Pedido</h3>
            <button onclick="toggleOrderPanel()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        
        <div class="space-y-4">
            <div>
                <label class="text-[10px] text-yellow-600 font-bold uppercase">Producto</label>
                <p id="info-producto" class="text-sm font-bold text-gray-800 bg-white p-2 rounded border border-yellow-100">---</p>
            </div>
            <div>
                <label class="text-[10px] text-yellow-600 font-bold uppercase">Cantidad</label>
                <p id="info-cantidad" class="text-sm font-bold text-gray-800 bg-white p-2 rounded border border-yellow-100">---</p>
            </div>
            <div>
                <label class="text-[10px] text-yellow-600 font-bold uppercase">Dirección</label>
                <p id="info-direccion" class="text-sm font-bold text-gray-800 bg-white p-2 rounded border border-yellow-100 italic break-words select-all cursor-text min-h-[40px]">---</p>
            </div>
        </div>

        <button onclick="clearOrderInfo()" class="mt-8 w-full py-2 bg-yellow-200 hover:bg-red-600 hover:text-white text-red-800 text-xs font-bold rounded-lg transition-all">
            🔄 Vaciar para nuevo pedido
        </button>
    </div>

    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    </script>
    <script src="{{ asset('chat.js') }}"></script>

<!-- Modal Inventario -->
<div id="modal-inventory" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-4xl h-[80vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        
        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
            <h2 class="text-xl font-black text-gray-800 uppercase">📦 Inventario Don Guando</h2>
            <button onclick="closeInventory()" class="text-gray-400 hover:text-red-500 text-3xl font-bold">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <div class="flex justify-between mb-4">
                <input type="text" id="inventory-search" placeholder="Buscar producto..." 
                       class="border rounded-lg px-4 py-2 w-64 focus:outline-none focus:ring-2 focus:ring-red-500">
                <div class="flex gap-2">
                    <a href="/import-products" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-bold text-sm transition-colors">
                        📥 Importar
                    </a>
                    <button onclick="openProductForm()" 
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold transition-colors">
                        + Nuevo
                    </button>
                </div>
            </div>

<table class="min-w-full bg-white border">
    <thead class="bg-gray-100 text-[10px] uppercase font-bold text-gray-500">
        <tr>
            <th class="px-4 py-2 text-left">Producto</th>
            <th class="px-4 py-2 text-left">Precio</th>
            <th class="px-4 py-2 text-left">Stock</th>
            <th class="px-4 py-2 text-left">Beneficio/Uso</th>
            <th class="px-4 py-2 text-left">Psicología de Venta</th>
            <th class="px-4 py-2 text-center">Acción</th>
        </tr>
    </thead>
    <tbody id="inventory-table-body">
        <!-- Filas de inventario se insertan aquí -->
    </tbody>
</table>
        </div>
    </div>
</div>

    <!-- Modal Formulario Producto -->
    <div id="modal-product-form" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
            <h3 class="text-xl font-black text-gray-800 mb-4 uppercase">🛒 Nuevo Producto</h3>
            
            <form id="product-form" class="space-y-4">
                <input type="hidden" id="p-id">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">NOMBRE DEL CORTE</label>
                    <input type="text" id="p-name" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none" placeholder="Ej: Picaña" required>
                </div>
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1">PRECIO (S/)</label>
                        <input type="number" step="0.01" id="p-price" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none" required>
                    </div>
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-gray-500 mb-1">STOCK</label>
                        <input type="number" id="p-stock" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none" required>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">UNIDAD</label>
                    <select id="p-unit" class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 outline-none">
                        <option value="kg">Kilogramo (kg)</option>
                        <option value="unidad">Unidad</option>
                        <option value="paquete">Paquete</option>
                    </select>
                </div>
                <div>
    <label class="block text-xs font-bold text-gray-500 mb-1">BENEFICIO/USO</label>
    <textarea id="p-beneficio" rows="2" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500" 
              placeholder="Ej: Ideal para asados, alto en proteínas..."></textarea>
</div>
<div>
    <label class="block text-xs font-bold text-gray-500 mb-1">PSICOLOGÍA DE VENTA</label>
    <textarea id="p-psicologia" rows="2" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-red-500" 
              placeholder="Ej: Precio irresistible para ocasiones especiales..."></textarea>
</div>
                
                <div class="flex gap-2 pt-4">
                    <button type="button" onclick="closeProductForm()" class="flex-1 px-4 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
                    <button type="submit" class="flex-1 bg-red-600 text-white px-4 py-2 rounded-lg font-bold shadow-md hover:bg-red-700">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
<div id="modal-quick-config" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6 border-t-4 border-blue-600">
        <h3 class="text-xl font-black text-gray-800 mb-1 uppercase text-center">⚙️ Configurar Plantilla</h3>
        <p class="text-[10px] text-gray-400 mb-4 text-center font-bold uppercase">Haz clic en las etiquetas para insertarlas</p>
        
        <div class="flex justify-center gap-2 mb-4">
            <button type="button" onclick="insertTag('{producto}')" 
                    class="bg-purple-100 text-purple-700 px-3 py-1 rounded-md border border-purple-300 text-xs font-bold hover:bg-purple-200 transition-all">
                { producto }
            </button>
            <button type="button" onclick="insertTag('{precio}')" 
                    class="bg-green-100 text-green-700 px-3 py-1 rounded-md border border-green-300 text-xs font-bold hover:bg-green-200 transition-all">
                { precio }
            </button>
        </div>

        <form id="quick-response-form"> <input type="hidden" id="q-id">
            <div>
                <label class="block text-[10px] font-black text-gray-500 mb-1 uppercase">Título del Atajo</label>
                <input type="text" id="q-title" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ej: Precio e invitación">
            </div>
            <div>
                <label class="block text-[10px] font-black text-gray-500 mb-1 uppercase">Cuerpo del Mensaje</label>
                <textarea id="q-body" rows="4" class="w-full border rounded-lg px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-500" 
                          placeholder="Escribe aquí tu mensaje..."></textarea>
            </div>
            
            <div class="flex gap-2">
                <button type="button" onclick="closeConfigQuickMessages()" class="flex-1 py-2 text-gray-400 font-bold hover:bg-gray-50 rounded-lg">Cancelar</button>
                <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg font-bold shadow-lg hover:bg-blue-700 transition-all">Guardar Plantilla</button>
            </div>
        </form>
    </div>
</div>


<!-- Modal Gestión de Tags -->
<div id="modal-tags-manager" class="hidden fixed inset-0 z-[80] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        <div class="p-4 bg-gradient-to-r from-purple-600 to-indigo-600 text-white flex justify-between items-center">
            <h3 class="text-lg font-black uppercase">🏷️ Gestión de Etiquetas</h3>
            <button onclick="closeTagsManager()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="p-4 border-b">
            <div class="flex gap-2">
                <input type="text" id="new-tag-name" placeholder="Nueva etiqueta..." 
                       class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
                <input type="color" id="new-tag-color" value="#6b21f5" 
                       class="w-12 h-10 rounded border cursor-pointer">
                <button onclick="createTag()" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-bold">
                    + Agregar
                </button>
            </div>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4 max-h-96">
            <div id="tags-list" class="flex flex-wrap gap-2">
                <!-- Lista de tags se carga aquí -->
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end">
            <button onclick="closeTagsManager()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal Configuración de Comandos (SISTEMA DE COMANDOS) -->
<div id="modal-cmd-config" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
        <div class="p-4 bg-gradient-to-r from-green-600 to-teal-600 text-white flex justify-between items-center rounded-t-2xl">
            <h3 class="text-lg font-black uppercase">⚡ Configurar Comandos Rápidos</h3>
            <button onclick="closeCmdConfig()" class="text-white hover:text-gray-200 text-2xl">&times;</button>
        </div>
        
        <div class="flex border-b">
            <button onclick="showCmdTab('categories')" id="cmd-tab-categories" class="flex-1 py-2 font-bold text-sm hover:bg-gray-100 transition-colors border-b-2 border-green-500 text-green-600">
                📁 Categorías
            </button>
            <button onclick="showCmdTab('commands')" id="cmd-tab-commands" class="flex-1 py-2 font-bold text-sm hover:bg-gray-100 transition-colors text-gray-500">
                💬 Comandos
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-4">
            <!-- Panel de Categorías -->
            <div id="cmd-categories-panel">
                <!-- Formulario para agregar nueva categoría -->
                <div class="bg-gray-50 rounded-lg p-3 mb-4 border">
                    <p class="text-xs font-bold text-gray-500 mb-2 uppercase">➕ Agregar nueva categoría</p>
                    <div class="flex gap-2">
                        <input type="text" id="cmd-new-category-name" placeholder="Nombre (ej: Carnes, Pollos, Cerdo)" 
                               class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <input type="text" id="cmd-new-category-icon" placeholder="Icono" value="🥩" 
                               class="w-20 border rounded-lg px-3 py-2 text-sm text-center">
                        <button onclick="createCmdCategory()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold text-sm">
                            + Agregar
                        </button>
                    </div>
                </div>
                
                <!-- Lista de categorías existentes -->
                <p class="text-xs font-bold text-gray-500 mb-2 uppercase">📋 Categorías existentes</p>
                <div id="cmd-categories-list" class="space-y-2 max-h-96 overflow-y-auto">
                    <div class="text-center text-gray-400 py-4">Cargando categorías...</div>
                </div>
            </div>
            
            <!-- Panel de Comandos -->
            <div id="cmd-commands-panel" class="hidden">
                <div class="flex gap-2 mb-4">
                    <select id="cmd-filter-category" class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                        <option value="">Todas las categorías</option>
                    </select>
                    <button onclick="openCmdCommandForm()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold">
                        + Nuevo Comando
                    </button>
                </div>
                <div id="cmd-commands-list" class="space-y-2 max-h-96 overflow-y-auto"></div>
            </div>
        </div>
        
        <div class="p-4 border-t bg-gray-50 flex justify-end">
            <button onclick="closeCmdConfig()" class="px-4 py-2 text-gray-500 hover:bg-gray-100 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal para editar comando individual -->
<div id="modal-cmd-command-form" class="hidden fixed inset-0 z-[110] flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl p-6">
        <h3 class="text-xl font-black text-gray-800 mb-4 uppercase" id="cmd-form-title">Nuevo Comando</h3>
        <input type="hidden" id="cmd-edit-id">
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Categoría</label>
            <select id="cmd-edit-category" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500">
                <option value="">Seleccionar categoría</option>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Comando (ej: /cerdo)</label>
            <input type="text" id="cmd-command" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
                   placeholder="/comando">
            <p class="text-[9px] text-gray-400 mt-1">Escribe / + comando en el chat</p>
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Título</label>
            <input type="text" id="cmd-title" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
                   placeholder="Título visible en el panel">
        </div>
        
        <div class="mb-3">
            <label class="block text-xs font-bold text-gray-500 mb-1">Mensaje</label>
            <textarea id="cmd-body" rows="4" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" 
                      placeholder="Contenido del mensaje a enviar..."></textarea>
        </div>
        
        <div class="flex gap-2 pt-2">
            <button onclick="closeCmdCommandForm()" class="flex-1 py-2 text-gray-500 font-bold hover:bg-gray-100 rounded-lg">Cancelar</button>
            <button onclick="saveCmdCommand()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-bold">Guardar</button>
        </div>
    </div>
</div>
</body>
</html>